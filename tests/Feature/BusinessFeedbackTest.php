<?php

namespace Tests\Feature;

use App\Mail\ReportReadyMail;
use App\Models\Clinic;
use App\Models\PatientHistoryRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the July 2026 business-feedback changes end to end:
 * registration form new fields, doctor CBE result, mammographer role +
 * post-campaign report upload/send.
 */
class BusinessFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_key_pages_render_for_each_role(): void
    {
        $this->actingAs($this->user('s.nuaimi@focp.ae'))->get('/nurse/record')->assertOk();
        $this->actingAs($this->user('n.khalid@focp.ae'))->get('/mammographer/queue')->assertOk();
        $this->actingAs($this->user('anish@focp.ae'))->get('/super/users/create')
            ->assertOk()->assertSee('Mammographer');
    }

    public function test_emirates_id_reader_mock_returns_card_data(): void
    {
        $this->actingAs($this->user('s.nuaimi@focp.ae'))
            ->getJson('/tools/emirates-id/read')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['emirates_id', 'full_name_en', 'date_of_birth', 'nationality']]);
    }

    public function test_full_registration_exam_and_report_flow(): void
    {
        Storage::fake('local');
        Mail::fake();

        $nurse = $this->user('s.nuaimi@focp.ae');
        $dubai = Clinic::where('code', 'DXB-MOB-01')->firstOrFail();

        // 1. Nurse submits the registration with all the new fields.
        // The PC number is no longer captured by the nurse — the mammographer enters it later (step 4).
        $this->actingAs($nurse)->post('/nurse/record', [
            'action'           => 'submit',
            'emirates_id'      => '784-1990-1234567-1',
            'full_name'        => 'Test Patient',
            'email'            => 'patient@example.com',
            'mobile1'          => '+971500000000',
            'breast_implant'   => 'no',
            'cbe_result'       => 'normal',
            'personal'         => ['biopsy' => 'yes'],
            'personal_notes'   => ['biopsy' => 'left side 2019'],
            'family'           => ['deg1' => ['relationship' => 'Parent', 'age' => '45']],
            'consent'          => '1',
            'patient_signature'=> 'data:image/png;base64,iVBORw0KGgo=',
            'signed_at'        => now()->toDateString(),
        ])->assertRedirect(route('nurse.queue'));

        $record = PatientHistoryRecord::latest('id')->firstOrFail();
        $this->assertSame('submitted', $record->status);
        $this->assertSame('784-1990-1234567-1', $record->patient->emirates_id);
        $this->assertSame('no', $record->breast_implant);
        $this->assertSame('yes', $record->personal_history['biopsy']);
        $this->assertSame('left side 2019', $record->personal_history_notes['biopsy']);
        $this->assertSame('Parent', $record->family_history['deg1']['relationship']);
        $this->assertSame(45, $record->family_history['deg1']['age']);
        $this->assertNotEmpty($record->patient_signature);

        // Registration must be blocked without a signature.
        $this->actingAs($nurse)->post('/nurse/record', [
            'action'    => 'submit',
            'full_name' => 'No Signature',
            'mobile1'   => '+971500000001',
            'consent'   => '1',
        ])->assertSessionHasErrors('patient_signature');

        // 2. Clinic admin assigns the case to a Dubai doctor.
        $admin  = $this->user('mariam.s@focp.ae');
        $doctor = User::role('doctor')
            ->whereHas('clinics', fn ($q) => $q->where('clinics.id', $dubai->id))
            ->firstOrFail();

        $this->actingAs($admin)
            ->post('/clinic/assign', ['record_id' => $record->id, 'role' => 'doctor', 'assignee_id' => $doctor->id])
            ->assertRedirect();

        $record->refresh();
        $this->assertSame('assigned', $record->status);
        $this->assertSame('doctor', $record->assigned_role);
        $this->assertSame($doctor->id, $record->assigned_doctor_id);

        // 3. Doctor sees the Form 1 review + CBE result control, then submits.
        $this->actingAs($doctor)->get("/doctor/exam/{$record->id}")
            ->assertOk()
            ->assertSee('Patient registration (Form 1)')
            ->assertSee('Clinical Breast Examination Result');

        $this->actingAs($doctor)->put("/doctor/exam/{$record->id}", [
            'action'         => 'submit',
            'cbe_result'     => 'abnormal',
            'recommendation' => 'Refer to mammogram',
            'symptoms'       => [],
            'signs'          => [],
            'pins'           => '[]',
        ])->assertRedirect();

        // Doctor finished → the case returns to the clinic admin.
        $record->refresh();
        $this->assertSame('returned', $record->status);
        $this->assertSame('abnormal', $record->examination->cbe_result);
        $this->assertSame('abnormal', $record->examination->result);

        // Doctor can download the generated report PDF from the Completed tab.
        $this->actingAs($doctor)->get("/doctor/exam/{$record->id}/report")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // Report has a verification code; the bilingual document view renders and shows it.
        $report = $record->report()->first();
        $this->assertNotNull($report->verify_code);
        $this->actingAs($doctor)->get("/reports/{$record->id}/document")
            ->assertOk()
            ->assertSee($report->verify_code)
            ->assertSee('Clinical Breast Examination Report');

        // Public verification: correct code + ref → valid (masked, no clinical data); wrong → invalid.
        $this->get('/verify')->assertOk()->assertSee('Report verification');
        $this->postJson('/verify/check', ['code' => $report->verify_code, 'ref' => $record->ref_no])
            ->assertOk()->assertJsonPath('valid', true)->assertJsonPath('data.ref', $record->ref_no);
        $this->postJson('/verify/check', ['code' => 'V-ZZZZ-ZZZZ', 'ref' => $record->ref_no])
            ->assertOk()->assertJsonPath('valid', false);

        // 4. Clinic admin routes the returned case on to a mammographer.
        $mammo = $this->user('n.khalid@focp.ae');

        // A mammographer cannot open a case that has not been assigned to them.
        $this->actingAs($mammo)->get("/mammographer/record/{$record->id}")->assertForbidden();

        $this->actingAs($admin)
            ->post('/clinic/assign', ['record_id' => $record->id, 'role' => 'mammographer', 'assignee_id' => $mammo->id])
            ->assertRedirect();

        $record->refresh();
        $this->assertSame('assigned', $record->status);
        $this->assertSame('mammographer', $record->assigned_role);
        $this->assertSame($mammo->id, $record->mammographer_id);

        // Now the mammographer uploads the mammogram report and sends it.
        $this->actingAs($mammo)->get('/mammographer/queue')->assertOk()->assertSee($record->ref_no);
        $this->actingAs($mammo)->get("/mammographer/record/{$record->id}")->assertOk();

        $this->actingAs($mammo)->put("/mammographer/record/{$record->id}", [
            'manual_pc_number' => 'PC-MANUAL-001',
            'full_name'        => 'Test Patient',
            'email'            => 'patient@example.com',
            'mobile1'          => '+971500000000',
            'report'           => UploadedFile::fake()->create('mammo.pdf', 120, 'application/pdf'),
        ])->assertRedirect();

        $record->refresh();
        $this->assertNotNull($record->mammogram_report_path);
        $this->assertNotNull($record->report_uploaded_at);
        $this->assertSame($mammo->id, $record->mammographer_id);
        Storage::assertExists($record->mammogram_report_path);

        $this->actingAs($mammo)->post("/mammographer/record/{$record->id}/send")
            ->assertRedirect(route('mammographer.queue'));

        // Report sent → the case returns to the clinic admin once more.
        $record->refresh();
        $this->assertSame('returned', $record->status);
        $this->assertNotNull($record->report_sent_at);
        $this->assertSame('PC-MANUAL-001', $record->patient->manual_pc_number);

        // Patient is notified across all three channels (email really sent; SMS/WhatsApp logged as stub).
        Mail::assertSent(ReportReadyMail::class, fn ($m) => $m->hasTo('patient@example.com'));
        $delivery = $record->report()->first()->delivery;
        $this->assertSame('sent', $delivery['email']);
        $this->assertSame('logged', $delivery['sms']);
        $this->assertSame('logged', $delivery['whatsapp']);

        // 5. Clinic admin closes the case.
        $this->actingAs($admin)->post("/clinic/complete/{$record->id}")->assertRedirect();
        $record->refresh();
        $this->assertSame('completed', $record->status);
    }

    public function test_clinic_admin_registers_patient_from_id_and_hands_to_nurse(): void
    {
        $admin = $this->user('mariam.s@focp.ae');
        $nurse = $this->user('s.nuaimi@focp.ae');

        // The clinic admin gets the same full record sheet the nurse uses.
        $this->actingAs($admin)->get('/clinic/register')
            ->assertOk()
            ->assertSee('Patient History')
            ->assertSee('Assign the case');

        // Here they capture the Emirates-ID demographics only and hand the case to a nurse
        // to complete the rest of the profile (saved as a draft).
        $this->actingAs($admin)->post('/clinic/register', [
            'action'      => 'draft',
            'full_name'   => 'Aisha Al Marri',
            'emirates_id' => '784-1990-1234567-1',
            'dob'         => '1990-05-14',
            'nationality' => 'Emirati',
            'emirate'     => 'dubai',
            'mobile1'     => '+971501112233',
            'assign_role' => 'nurse',
            'assignee_id' => $nurse->id,
        ])->assertRedirect(route('clinic.queue'));

        $record = PatientHistoryRecord::latest('id')->firstOrFail();
        $this->assertSame('draft', $record->status);
        $this->assertSame($nurse->id, $record->nurse_id);
        $this->assertSame('Aisha Al Marri', $record->patient->full_name);
        $this->assertSame('784-1990-1234567-1', $record->patient->emirates_id);
        $this->assertSame('Emirati', $record->patient->nationality);
        $this->assertSame($admin->id, $record->patient->registered_by);

        // The nurse picks it up from the queue and continues it, ID data pre-filled.
        $this->actingAs($nurse)->get("/nurse/record/{$record->id}/edit")
            ->assertOk()->assertSee('Aisha Al Marri')->assertSee('784-1990-1234567-1');

        // The clinic admin can view the entered data read-only.
        $this->actingAs($admin)->get("/clinic/record/{$record->id}")
            ->assertOk()->assertSee('Aisha Al Marri')->assertSee('784-1990-1234567-1');
    }

    public function test_registration_can_route_the_case_straight_to_a_doctor(): void
    {
        $nurse  = $this->user('s.nuaimi@focp.ae');
        $dubai  = Clinic::where('code', 'DXB-MOB-01')->firstOrFail();
        $doctor = User::role('doctor')
            ->whereHas('clinics', fn ($q) => $q->where('clinics.id', $dubai->id))
            ->firstOrFail();

        // The nurse registers the full profile and assigns the doctor in one step.
        $this->actingAs($nurse)->get('/nurse/record')->assertOk()->assertSee('Assign the case');

        $this->actingAs($nurse)->post('/nurse/record', [
            'action'           => 'submit',
            'full_name'        => 'Direct To Doctor',
            'mobile1'          => '+971500000010',
            'consent'          => '1',
            'patient_signature'=> 'data:image/png;base64,iVBORw0KGgo=',
            'assign_role'      => 'doctor',
            'assignee_id'      => $doctor->id,
        ])->assertRedirect(route('nurse.queue'));

        $record = PatientHistoryRecord::latest('id')->firstOrFail();
        $this->assertSame('assigned', $record->status);
        $this->assertSame('doctor', $record->assigned_role);
        $this->assertSame($doctor->id, $record->assigned_doctor_id);

        // It lands in that doctor queue without the admin having to route it.
        $this->actingAs($doctor)->get('/doctor/assigned')->assertOk()->assertSee($record->ref_no);
    }

    public function test_clinic_admin_registers_full_profile_and_routes_to_mammographer(): void
    {
        $admin = $this->user('mariam.s@focp.ae');
        $mammo = $this->user('n.khalid@focp.ae');

        $this->actingAs($admin)->post('/clinic/register', [
            'action'           => 'submit',
            'full_name'        => 'Full Profile Patient',
            'emirates_id'      => '784-1988-7654321-2',
            'dob'              => '1988-02-03',
            'nationality'      => 'Emirati',
            'emirate'          => 'dubai',
            'marital_status'   => 'married',
            'mobile1'          => '+971500000011',
            'email'            => 'full@example.com',
            'age_at_menarche'  => 13,
            'breast_implant'   => 'no',
            'personal'         => ['hrt' => 'yes'],
            'personal_notes'   => ['hrt' => 'since 2021'],
            'family'           => ['deg2' => ['relationship' => 'Aunt', 'age' => '52']],
            'cbe_result'       => 'normal',
            'consent'          => '1',
            'patient_signature'=> 'data:image/png;base64,iVBORw0KGgo=',
            'signed_at'        => now()->toDateString(),
            'assign_role'      => 'mammographer',
            'assignee_id'      => $mammo->id,
        ])->assertRedirect(route('clinic.queue'));

        $record = PatientHistoryRecord::latest('id')->firstOrFail();

        // The whole profile is captured — not just the ID demographics.
        $this->assertSame('married', $record->patient->marital_status);
        $this->assertSame('full@example.com', $record->patient->email);
        $this->assertSame(13, $record->age_at_menarche);
        $this->assertSame('yes', $record->personal_history['hrt']);
        $this->assertSame('since 2021', $record->personal_history_notes['hrt']);
        $this->assertSame('Aunt', $record->family_history['deg2']['relationship']);
        $this->assertNotEmpty($record->patient_signature);

        // ...and it is routed straight to the mammographer.
        $this->assertSame('assigned', $record->status);
        $this->assertSame('mammographer', $record->assigned_role);
        $this->assertSame($mammo->id, $record->mammographer_id);
        $this->actingAs($mammo)->get('/mammographer/queue')->assertOk()->assertSee($record->ref_no);
    }

    public function test_registration_rejects_an_assignee_from_another_clinic(): void
    {
        $nurse = $this->user('s.nuaimi@focp.ae');
        $dubai = Clinic::where('code', 'DXB-MOB-01')->firstOrFail();

        $outsider = User::role('doctor')
            ->whereDoesntHave('clinics', fn ($q) => $q->where('clinics.id', $dubai->id))
            ->firstOrFail();

        $this->actingAs($nurse)->post('/nurse/record', [
            'action'           => 'submit',
            'full_name'        => 'Wrong Clinic',
            'mobile1'          => '+971500000012',
            'consent'          => '1',
            'patient_signature'=> 'data:image/png;base64,iVBORw0KGgo=',
            'assign_role'      => 'doctor',
            'assignee_id'      => $outsider->id,
        ])->assertSessionHasErrors('assignee_id');
    }
}
