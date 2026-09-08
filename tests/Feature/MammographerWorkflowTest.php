<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\MammogramFinding;
use App\Models\PatientHistoryRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Mammographer side of the patient file (business feedback #4–#6):
 *   #4 open the file and review the initial registration form;
 *   #5 record and submit the mammography findings;
 *   #6 come back days later to upload and send the report — the file stays
 *      reachable until the report has actually gone out.
 */
class MammographerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    private function mammographer(): User
    {
        return User::where('email', 'n.khalid@focp.ae')->firstOrFail();
    }

    private function nurse(): User
    {
        return User::where('email', 's.nuaimi@focp.ae')->firstOrFail();
    }

    /** A case registered by the nurse and routed straight to the mammographer. */
    private function routedCase(): PatientHistoryRecord
    {
        $this->actingAs($this->nurse())->post('/nurse/record', [
            'action'            => 'submit',
            'full_name'         => 'Mammo Patient',
            'mobile1'           => '+971500000030',
            'consent'           => '1',
            'patient_signature' => 'data:image/png;base64,iVBORw0KGgo=',
            'assign_role'       => 'mammographer',
            'assignee_id'       => $this->mammographer()->id,
        ])->assertRedirect();

        return PatientHistoryRecord::latest('id')->firstOrFail();
    }

    public function test_mammographer_queue_separates_assigned_cases_from_ones_awaiting_a_report(): void
    {
        $record = $this->routedCase();

        $this->actingAs($this->mammographer())->get('/mammographer/queue')
            ->assertOk()
            ->assertSee('Assigned to me')
            ->assertSee('Awaiting the mammography report')
            ->assertSee($record->ref_no);
    }

    public function test_mammographer_can_open_the_initial_patient_form_read_only(): void
    {
        $record = $this->routedCase();

        $this->actingAs($this->mammographer())
            ->get("/mammographer/record/{$record->id}/history")
            ->assertOk()
            ->assertSee('Mammo Patient')
            ->assertSee(__('pc.patient_file_readonly'), false) // the read-only banner for this file
            ->assertSee('<fieldset disabled', false);          // the whole form is rendered disabled
    }

    public function test_mammographer_drafts_then_submits_the_findings(): void
    {
        $record = $this->routedCase();
        $mammo  = $this->mammographer();

        $this->actingAs($mammo)->get("/mammographer/record/{$record->id}/findings")->assertOk();

        // A draft saves whatever is filled in so far.
        $this->actingAs($mammo)->put("/mammographer/record/{$record->id}/findings", [
            'action'         => 'draft',
            'findings_right' => 'No discrete mass.',
        ])->assertRedirect();

        $finding = $record->fresh()->mammogramFinding;
        $this->assertSame(MammogramFinding::DRAFT, $finding->status);
        $this->assertNull($finding->submitted_at);

        // Submitting requires the clinically meaningful fields.
        $this->actingAs($mammo)->put("/mammographer/record/{$record->id}/findings", [
            'action'         => 'submit',
            'findings_right' => 'No discrete mass.',
        ])->assertSessionHasErrors(['exam_date', 'modality', 'birads_right', 'birads_left', 'impression', 'recommendation']);

        $this->actingAs($mammo)->put("/mammographer/record/{$record->id}/findings", [
            'action'         => 'submit',
            'exam_date'      => now()->toDateString(),
            'modality'       => 'both',
            'breast_density' => 'b',
            'birads_right'   => '2',
            'birads_left'    => '4',
            'findings_right' => 'Benign calcifications.',
            'findings_left'  => 'Irregular 9mm mass, upper outer quadrant.',
            'impression'     => 'Suspicious left-sided finding.',
            'recommendation' => 'biopsy',
        ])->assertRedirect(route('mammographer.record', $record));

        $finding = $record->fresh()->mammogramFinding;
        $this->assertSame(MammogramFinding::SUBMITTED, $finding->status);
        $this->assertNotNull($finding->submitted_at);
        $this->assertSame($this->mammographer()->id, $finding->mammographer_id);
        // The more serious side drives the overall assessment.
        $this->assertSame('4', $finding->highestBirads());
    }

    public function test_the_file_stays_open_for_a_late_report_even_after_the_admin_closes_the_case(): void
    {
        $record = $this->routedCase();
        $mammo  = $this->mammographer();

        // The mammographer picks the case up, then the admin closes it before the
        // formal report is ready (#6: the report can arrive days later).
        $this->actingAs($mammo)->get("/mammographer/record/{$record->id}")->assertOk();
        $record->update(['status' => PatientHistoryRecord::COMPLETED]);

        // It is no longer "assigned", but it is still listed and still writable.
        $this->actingAs($mammo)->get('/mammographer/queue')->assertOk()->assertSee($record->ref_no);
        $this->actingAs($mammo)->get("/mammographer/record/{$record->id}")->assertOk();

        $this->actingAs($mammo)->put("/mammographer/record/{$record->id}", [
            'full_name' => 'Mammo Patient',
            'mobile1'   => '+971500000030',
            'report'    => UploadedFile::fake()->create('mammogram.pdf', 40, 'application/pdf'),
        ])->assertRedirect();

        $record = $record->fresh();
        $this->assertNotNull($record->mammogram_report_path);
        $this->assertNotNull($record->report_uploaded_at);

        // Sending the report must NOT reopen a case the admin already closed.
        $this->actingAs($mammo)->post("/mammographer/record/{$record->id}/send")->assertRedirect();

        $record = $record->fresh();
        $this->assertNotNull($record->report_sent_at);
        $this->assertSame(PatientHistoryRecord::COMPLETED, $record->status);
    }

    public function test_once_the_report_is_sent_the_file_is_read_only(): void
    {
        $record = $this->routedCase();
        $mammo  = $this->mammographer();

        $this->actingAs($mammo)->put("/mammographer/record/{$record->id}", [
            'full_name' => 'Mammo Patient',
            'mobile1'   => '+971500000030',
            'report'    => UploadedFile::fake()->create('mammogram.pdf', 40, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($mammo)->post("/mammographer/record/{$record->id}/send")->assertRedirect();

        // Further edits and findings are refused, but the file still opens.
        $this->actingAs($mammo)->put("/mammographer/record/{$record->id}", [
            'full_name' => 'Changed Name',
            'mobile1'   => '+971500000099',
        ])->assertRedirect();

        $this->assertSame('Mammo Patient', $record->fresh()->patient->full_name);

        $this->actingAs($mammo)->put("/mammographer/record/{$record->id}/findings", [
            'action'     => 'draft',
            'impression' => 'Late edit',
        ])->assertRedirect();

        $this->assertNull($record->fresh()->mammogramFinding);
    }

    public function test_a_mammographer_from_another_clinic_is_refused(): void
    {
        $record = $this->routedCase();

        $sharjah = Clinic::where('code', 'SHJ-FIX-01')->firstOrFail();
        $other   = User::create(['name' => 'Other Mammographer', 'email' => 'other.m@focp.ae', 'password' => bcrypt('password')]);
        $other->syncRoles(['mammographer']);
        $other->syncPermissions(['manage_mammograms', 'record_mammogram_findings']);
        $other->clinics()->sync([$sharjah->id]);

        $this->actingAs($other)->get("/mammographer/record/{$record->id}")->assertForbidden();
        $this->actingAs($other)->get("/mammographer/record/{$record->id}/history")->assertForbidden();
        $this->actingAs($other)->get("/mammographer/record/{$record->id}/findings")->assertForbidden();
    }
}
