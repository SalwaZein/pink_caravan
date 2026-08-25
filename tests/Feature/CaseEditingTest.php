<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\PatientHistoryRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Case data stays editable by the nurse, the doctor and the clinic admin for as
 * long as the case is open. Marking it completed is what freezes it.
 */
class CaseEditingTest extends TestCase
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

    private function doctor(): User
    {
        $dubai = Clinic::where('code', 'DXB-MOB-01')->firstOrFail();

        return User::role('doctor')
            ->whereHas('clinics', fn ($q) => $q->where('clinics.id', $dubai->id))
            ->firstOrFail();
    }

    /** Register a submitted case routed straight to the Dubai doctor. */
    private function openCase(): PatientHistoryRecord
    {
        $this->actingAs($this->user('s.nuaimi@focp.ae'))->post('/nurse/record', [
            'action'           => 'submit',
            'full_name'        => 'Editable Patient',
            'mobile1'          => '+971500000020',
            'consent'          => '1',
            'patient_signature'=> 'data:image/png;base64,iVBORw0KGgo=',
            'assign_role'      => 'doctor',
            'assignee_id'      => $this->doctor()->id,
        ])->assertRedirect();

        return PatientHistoryRecord::latest('id')->firstOrFail();
    }

    /** The nurse form posts the full profile; this is the minimum valid payload. */
    private function recordPayload(array $overrides = []): array
    {
        return array_merge([
            'action'    => 'draft',
            'full_name' => 'Editable Patient',
            'mobile1'   => '+971500000020',
        ], $overrides);
    }

    public function test_nurse_and_clinic_admin_can_edit_a_case_that_has_left_draft(): void
    {
        $record = $this->openCase();
        $this->assertSame('assigned', $record->status);

        // The nurse corrects the patient details after submission.
        $this->actingAs($this->user('s.nuaimi@focp.ae'))
            ->put("/nurse/record/{$record->id}", $this->recordPayload([
                'full_name' => 'Corrected By Nurse',
                'mobile1'   => '+971500000021',
            ]))->assertRedirect(route('nurse.queue'));

        $record->refresh();
        $this->assertSame('Corrected By Nurse', $record->patient->full_name);
        $this->assertSame('+971500000021', $record->patient->mobile1);

        // Editing must NOT shunt the case backwards out of the doctor's queue.
        $this->assertSame('assigned', $record->status);
        $this->assertSame('doctor', $record->assigned_role);
        $this->assertSame($this->doctor()->id, $record->assigned_doctor_id);

        // The clinic admin can edit the same open case.
        $this->actingAs($this->user('mariam.s@focp.ae'))
            ->put("/nurse/record/{$record->id}", $this->recordPayload([
                'full_name' => 'Corrected By Admin',
            ]))->assertRedirect(route('clinic.queue'));

        $record->refresh();
        $this->assertSame('Corrected By Admin', $record->patient->full_name);
        $this->assertSame('assigned', $record->status);
    }

    public function test_doctor_can_correct_a_submitted_examination(): void
    {
        $record = $this->openCase();
        $doctor = $this->doctor();

        $this->actingAs($doctor)->put("/doctor/exam/{$record->id}", [
            'action' => 'submit', 'cbe_result' => 'normal',
            'recommendation' => 'Routine screening', 'symptoms' => [], 'signs' => [], 'pins' => '[]',
        ])->assertRedirect();

        $record->refresh();
        $this->assertSame('returned', $record->status);
        $this->assertSame('submitted', $record->examination->status);

        // Re-opening the exam is allowed while the case is open, and re-submitting
        // updates the examination instead of being rejected as locked.
        $this->actingAs($doctor)->get("/doctor/exam/{$record->id}")->assertOk();

        $this->actingAs($doctor)->put("/doctor/exam/{$record->id}", [
            'action' => 'submit', 'cbe_result' => 'abnormal',
            'recommendation' => 'Refer to mammogram', 'symptoms' => [], 'signs' => [], 'pins' => '[]',
        ])->assertRedirect();

        $record->refresh();
        $this->assertSame('abnormal', $record->examination->cbe_result);
        $this->assertSame('Refer to mammogram', $record->examination->recommendation);
    }

    public function test_correcting_an_exam_does_not_pull_the_case_out_of_another_queue(): void
    {
        $record = $this->openCase();
        $doctor = $this->doctor();
        $admin  = $this->user('mariam.s@focp.ae');
        $mammo  = $this->user('n.khalid@focp.ae');

        $this->actingAs($doctor)->put("/doctor/exam/{$record->id}", [
            'action' => 'submit', 'cbe_result' => 'abnormal',
            'recommendation' => 'Refer to mammogram', 'symptoms' => [], 'signs' => [], 'pins' => '[]',
        ])->assertRedirect();

        // Admin routes the returned case on to the mammographer.
        $this->actingAs($admin)->post('/clinic/assign', [
            'record_id' => $record->id, 'role' => 'mammographer', 'assignee_id' => $mammo->id,
        ])->assertRedirect();

        $this->assertSame('mammographer', $record->fresh()->assigned_role);

        // A late correction by the doctor updates the exam but leaves the case
        // sitting in the mammographer's queue.
        $this->actingAs($doctor)->put("/doctor/exam/{$record->id}", [
            'action' => 'submit', 'cbe_result' => 'abnormal',
            'recommendation' => 'Refer to ultrasound', 'symptoms' => [], 'signs' => [], 'pins' => '[]',
        ])->assertRedirect();

        $record->refresh();
        $this->assertSame('Refer to ultrasound', $record->examination->recommendation);
        $this->assertSame('assigned', $record->status);
        $this->assertSame('mammographer', $record->assigned_role);
    }

    public function test_a_completed_case_is_frozen_for_all_three_roles(): void
    {
        $record = $this->openCase();
        $doctor = $this->doctor();
        $admin  = $this->user('mariam.s@focp.ae');
        $nurse  = $this->user('s.nuaimi@focp.ae');

        $this->actingAs($doctor)->put("/doctor/exam/{$record->id}", [
            'action' => 'submit', 'cbe_result' => 'normal',
            'recommendation' => 'Routine screening', 'symptoms' => [], 'signs' => [], 'pins' => '[]',
        ])->assertRedirect();

        $this->actingAs($admin)->post("/clinic/complete/{$record->id}")->assertRedirect();
        $this->assertSame('completed', $record->fresh()->status);

        // The record sheet still opens — read-only — for the nurse and the admin.
        foreach ([$nurse, $admin] as $staff) {
            $this->actingAs($staff)->get("/nurse/record/{$record->id}/edit")
                ->assertOk()
                ->assertSee('This case is completed')
                ->assertDontSee('Save changes');
        }

        // ...and writes are refused.
        $this->actingAs($nurse)
            ->put("/nurse/record/{$record->id}", $this->recordPayload(['full_name' => 'Should Not Save']))
            ->assertRedirect(route('nurse.queue'));

        $this->assertSame('Editable Patient', $record->fresh()->patient->full_name);

        // The doctor is bounced out of the examination too.
        $this->actingAs($doctor)->get("/doctor/exam/{$record->id}")
            ->assertRedirect(route('doctor.completed'));

        $this->actingAs($doctor)->put("/doctor/exam/{$record->id}", [
            'action' => 'submit', 'cbe_result' => 'abnormal',
            'recommendation' => 'Refer to mammogram', 'symptoms' => [], 'signs' => [], 'pins' => '[]',
        ])->assertRedirect(route('doctor.completed'));

        $this->assertSame('normal', $record->fresh()->examination->cbe_result);
    }
}
