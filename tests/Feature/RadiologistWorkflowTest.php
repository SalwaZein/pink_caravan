<?php

namespace Tests\Feature;

use App\Mail\RadiologyReportMail;
use App\Models\Clinic;
use App\Models\PatientHistoryRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The radiologist's single "Patient Report" tab (business feedback round 3):
 * it shows what the mammographer filed — patient number, name, email, the PC
 * number she entered by hand and her findings — takes the final PDF report from
 * the radiology medical system, and sends that PDF to the patient.
 */
class RadiologistWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    /** A case screened by the mammographer and handed to the radiologist. */
    private function screenedCase(): PatientHistoryRecord
    {
        $mammo = $this->user('n.khalid@focp.ae');

        $this->actingAs($this->user('s.nuaimi@focp.ae'))->post('/nurse/record', $this->registrationPayload([
            'full_name'   => 'Radiology Patient',
            'email'       => 'radiology.patient@example.com',
            'mobile1'     => '+971500000040',
            'assign_role' => 'mammographer',
            'assignee_id' => $mammo->id,
        ]))->assertRedirect();

        $record = PatientHistoryRecord::latest('id')->firstOrFail();

        $this->actingAs($mammo)->put("/mammographer/record/{$record->id}/findings", [
            'action'           => 'submit',
            'manual_pc_number' => 'PC-7731',
            'findings'         => 'Grouped microcalcifications, left upper outer quadrant.',
            'radiologist_id'   => $this->user('h.marri@focp.ae')->id,
        ])->assertRedirect();

        return $record->fresh();
    }

    public function test_the_patient_report_tab_lists_studies_assigned_to_this_radiologist(): void
    {
        $record = $this->screenedCase();

        $this->actingAs($this->user('h.marri@focp.ae'))->get('/radiologist/reports')
            ->assertOk()
            ->assertSee('Patient Report')
            ->assertSee('Awaiting my report')
            ->assertSee($record->ref_no)
            ->assertSee('Radiology Patient')
            ->assertSee('PC-7731');
    }

    public function test_the_report_page_shows_everything_the_mammographer_filed(): void
    {
        $record = $this->screenedCase();

        $this->actingAs($this->user('h.marri@focp.ae'))->get("/radiologist/reports/{$record->id}")
            ->assertOk()
            ->assertSee($record->patient->pc_number)                                  // patient number
            ->assertSee('Radiology Patient')                                          // full name
            ->assertSee('radiology.patient@example.com')                              // email
            ->assertSee('PC-7731')                                                    // PC number, entered by hand
            ->assertSee('Grouped microcalcifications, left upper outer quadrant.')    // findings
            ->assertSee('Final report (PDF)');
    }

    public function test_radiologist_uploads_the_final_pdf_and_sends_it_to_the_patient(): void
    {
        Mail::fake();

        $record = $this->screenedCase();
        $radiologist = $this->user('h.marri@focp.ae');

        // Nothing to send before the report is attached.
        $this->actingAs($radiologist)->post("/radiologist/reports/{$record->id}/send")->assertRedirect();
        $this->assertNull($record->fresh()->radiology_report_sent_at);

        // Only a PDF is accepted.
        $this->actingAs($radiologist)->post("/radiologist/reports/{$record->id}", [
            'report' => UploadedFile::fake()->create('scan.png', 20, 'image/png'),
        ])->assertSessionHasErrors('report');

        $this->actingAs($radiologist)->post("/radiologist/reports/{$record->id}", [
            'report' => UploadedFile::fake()->create('report.pdf', 200, 'application/pdf'),
        ])->assertRedirect(route('radiologist.report', $record));

        $record = $record->fresh();
        $this->assertNotNull($record->radiology_report_path);
        $this->assertNotNull($record->radiology_report_uploaded_at);
        Storage::assertExists($record->radiology_report_path);

        // The attached PDF streams back to staff.
        $this->actingAs($radiologist)->get("/radiologist/reports/{$record->id}/file")
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        // Sending mails the PDF to the patient and returns the case to the clinic admin.
        $this->actingAs($radiologist)->post("/radiologist/reports/{$record->id}/send")
            ->assertRedirect(route('radiologist.reports'));

        $record = $record->fresh();
        $this->assertNotNull($record->radiology_report_sent_at);
        $this->assertSame(PatientHistoryRecord::RETURNED, $record->status);

        Mail::assertSent(RadiologyReportMail::class, fn ($m) => $m->hasTo('radiology.patient@example.com'));

        // It moves out of the waiting list into the sent one.
        $this->actingAs($radiologist)->get('/radiologist/reports')
            ->assertOk()->assertSee('Report sent');
    }

    public function test_sending_a_late_report_does_not_reopen_a_closed_case(): void
    {
        $record = $this->screenedCase();
        $radiologist = $this->user('h.marri@focp.ae');

        $record->update(['status' => PatientHistoryRecord::COMPLETED]);

        $this->actingAs($radiologist)->post("/radiologist/reports/{$record->id}", [
            'report' => UploadedFile::fake()->create('report.pdf', 60, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($radiologist)->post("/radiologist/reports/{$record->id}/send")->assertRedirect();

        $record = $record->fresh();
        $this->assertNotNull($record->radiology_report_sent_at);
        $this->assertSame(PatientHistoryRecord::COMPLETED, $record->status);
    }

    public function test_a_radiologist_only_sees_their_own_studies(): void
    {
        $record = $this->screenedCase();

        $sharjah = Clinic::where('code', 'SHJ-FIX-01')->firstOrFail();
        $other = User::create(['name' => 'Dr. Other Radiologist', 'email' => 'other.rad@focp.ae', 'password' => bcrypt('password')]);
        $other->syncRoles(['radiologist']);
        $other->syncPermissions(['manage_radiology']);
        $other->clinics()->sync([$sharjah->id]);

        $this->actingAs($other)->get('/radiologist/reports')->assertOk()->assertDontSee($record->ref_no);
        $this->actingAs($other)->get("/radiologist/reports/{$record->id}")->assertForbidden();

        // A role without the capability cannot reach the tab at all.
        $this->actingAs($this->user('s.nuaimi@focp.ae'))->get('/radiologist/reports')->assertForbidden();
    }

    public function test_radiologist_lands_on_the_patient_report_tab_after_signing_in(): void
    {
        $this->post('/login', ['email' => 'h.marri@focp.ae', 'password' => 'password'])
            ->assertRedirect(route('radiologist.reports'));
    }
}
