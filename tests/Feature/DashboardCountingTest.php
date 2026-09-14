<?php

namespace Tests\Feature;

use App\Models\PatientHistoryRecord;
use App\Models\User;
use App\Support\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Business feedback round 3, dashboard counting: a submitted report is what counts.
 * The mammographer submitting the Mammography Screening counts as a mammogram; the
 * doctor submitting the Clinical Examination counts as a clinical examination.
 */
class DashboardCountingTest extends TestCase
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

    private function dubaiDoctor(): User
    {
        return $this->user('l.hassan@focp.ae');
    }

    /** Register a submitted case routed to the given role + person. */
    private function caseFor(string $role, User $assignee, string $name, string $mobile): PatientHistoryRecord
    {
        $this->actingAs($this->user('s.nuaimi@focp.ae'))->post('/nurse/record', $this->registrationPayload([
            'full_name'   => $name,
            'mobile1'     => $mobile,
            'assign_role' => $role,
            'assignee_id' => $assignee->id,
        ]))->assertRedirect();

        return PatientHistoryRecord::latest('id')->firstOrFail();
    }

    public function test_a_submitted_mammography_screening_counts_as_a_mammogram(): void
    {
        $mammo = $this->user('n.khalid@focp.ae');
        $record = $this->caseFor('mammographer', $mammo, 'Counted Mammogram', '+971500000050');

        // Registered and assigned, but nothing submitted yet — nothing counted.
        $this->assertSame(0, DashboardService::stats()['mammograms']);

        // A draft screening is not a report either.
        $this->actingAs($mammo)->put("/mammographer/record/{$record->id}/findings", [
            'action'   => 'draft',
            'findings' => 'Work in progress.',
        ])->assertRedirect();

        $this->assertSame(0, DashboardService::stats()['mammograms']);

        // Submitting it is what counts.
        $this->actingAs($mammo)->put("/mammographer/record/{$record->id}/findings", [
            'action'           => 'submit',
            'manual_pc_number' => 'PC-9001',
            'findings'         => 'Normal study, no suspicious finding.',
            'radiologist_id'   => $this->user('h.marri@focp.ae')->id,
        ])->assertRedirect();

        $this->assertSame(1, DashboardService::stats()['mammograms']);
    }

    public function test_a_submitted_clinical_examination_counts_as_a_clinical_examination(): void
    {
        $doctor = $this->dubaiDoctor();
        $record = $this->caseFor('doctor', $doctor, 'Counted Exam', '+971500000051');

        $this->assertSame(0, DashboardService::stats()['clinicalExams']);

        // A drafted examination is not a report.
        $this->actingAs($doctor)->put("/doctor/exam/{$record->id}", [
            'action' => 'draft', 'cbe_result' => 'normal',
            'symptoms' => [], 'signs' => [], 'pins' => '[]',
        ])->assertRedirect();

        $this->assertSame(0, DashboardService::stats()['clinicalExams']);

        $this->actingAs($doctor)->put("/doctor/exam/{$record->id}", [
            'action' => 'submit', 'cbe_result' => 'normal',
            'recommendation' => 'Routine screening — normal',
            'symptoms' => [], 'signs' => [], 'pins' => '[]',
        ])->assertRedirect();

        $this->assertSame(1, DashboardService::stats()['clinicalExams']);
    }

    public function test_both_counts_are_shown_on_the_dashboard_and_clinic_reports(): void
    {
        $this->actingAs($this->user('anish@focp.ae'))->get('/super/dashboard')
            ->assertOk()
            ->assertSee('Mammograms')
            ->assertSee('Clinical examinations');

        $this->actingAs($this->user('mariam.s@focp.ae'))->get('/clinic/reports')
            ->assertOk()
            ->assertSee('Mammograms')
            ->assertSee('Clinical examinations');
    }
}
