<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the Administration → Service Bookings review workflow:
 * new → approved → paid → completed, each action gated by its own permission,
 * attributed to an actor, and audited.
 */
class ServiceBookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::where('email', 'anish@focp.ae')->firstOrFail();
    }

    private function newBooking(): Booking
    {
        $b = Booking::create([
            'ref_no'         => Booking::nextRef((int) now()->format('Y')),
            'organisation'   => 'Test Org',
            'contact_person' => 'Tester',
            'email'          => 't@example.com',
            'mobile'         => '0500000000',
        ]);
        $b->services()->create(['service_type' => 'A']);

        return $b;
    }

    public function test_full_lifecycle_new_to_completed(): void
    {
        Storage::fake();
        $admin = $this->admin();
        $b = $this->newBooking();
        $this->assertSame(Booking::STATUS_NEW, $b->status);

        // List + detail render
        $this->actingAs($admin)->get('/super/bookings')->assertOk()->assertSee($b->ref_no);
        $this->actingAs($admin)->get("/super/bookings/{$b->id}")->assertOk();

        // Approve
        $this->actingAs($admin)->post("/super/bookings/{$b->id}/approve")->assertRedirect();
        $b->refresh();
        $this->assertSame(Booking::STATUS_APPROVED, $b->status);
        $this->assertSame($admin->id, $b->approved_by);
        $this->assertNotNull($b->approved_at);

        // Mark paid
        $this->actingAs($admin)->post("/super/bookings/{$b->id}/paid", [
            'payment_amount' => '1500.50', 'payment_reference' => 'TXN-99',
        ])->assertRedirect();
        $b->refresh();
        $this->assertSame(Booking::STATUS_PAID, $b->status);
        $this->assertSame('1500.50', (string) $b->payment_amount);
        $this->assertSame($admin->id, $b->paid_by);

        // Complete with PDF report
        $this->actingAs($admin)->post("/super/bookings/{$b->id}/complete", [
            'completion_report' => UploadedFile::fake()->create('report.pdf', 20, 'application/pdf'),
            'completion_notes'  => 'All done',
        ])->assertRedirect();
        $b->refresh();
        $this->assertSame(Booking::STATUS_COMPLETED, $b->status);
        $this->assertSame($admin->id, $b->completed_by);
        $this->assertNotNull($b->completion_report_path);
        Storage::assertExists($b->completion_report_path);

        // Report streams as PDF
        $this->actingAs($admin)->get("/super/bookings/{$b->id}/report")
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        // Audit trail recorded every transition
        $actions = \App\Models\AuditLog::where('entity_type', 'Booking')
            ->where('entity_id', $b->id)->pluck('action')->all();
        $this->assertEqualsCanonicalizing(
            ['booking.approved', 'booking.paid', 'booking.completed'],
            $actions,
        );
    }

    public function test_reject_path(): void
    {
        $admin = $this->admin();
        $b = $this->newBooking();

        $this->actingAs($admin)->post("/super/bookings/{$b->id}/reject", [
            'rejection_reason' => 'Out of catchment area',
        ])->assertRedirect();

        $b->refresh();
        $this->assertSame(Booking::STATUS_REJECTED, $b->status);
        $this->assertSame('Out of catchment area', $b->rejection_reason);
    }

    public function test_out_of_order_transition_is_blocked(): void
    {
        $admin = $this->admin();
        $b = $this->newBooking(); // still "new"

        // Cannot pay a booking that was never approved.
        $this->actingAs($admin)->post("/super/bookings/{$b->id}/paid")->assertStatus(422);
        $this->assertSame(Booking::STATUS_NEW, $b->fresh()->status);
    }

    public function test_each_action_requires_its_own_permission(): void
    {
        $b = $this->newBooking();

        // A user who can only review may open the panel but not act on it.
        $reviewer = User::factory()->create();
        $reviewer->givePermissionTo('review_bookings');

        $this->actingAs($reviewer)->get("/super/bookings/{$b->id}")->assertOk();
        $this->actingAs($reviewer)->post("/super/bookings/{$b->id}/approve")->assertForbidden();

        // Granting approve unlocks only that action.
        $reviewer->givePermissionTo('approve_bookings');
        $this->actingAs($reviewer)->post("/super/bookings/{$b->id}/approve")->assertRedirect();

        // Still cannot mark paid without the payment permission.
        $this->actingAs($reviewer)->post("/super/bookings/{$b->id}/paid")->assertForbidden();
    }

    public function test_reviewer_without_any_booking_permission_is_denied_the_panel(): void
    {
        $b = $this->newBooking();
        $nobody = User::factory()->create();

        $this->actingAs($nobody)->get('/super/bookings')->assertForbidden();
        $this->actingAs($nobody)->get("/super/bookings/{$b->id}")->assertForbidden();
    }

    public function test_dashboard_renders_and_reflects_metrics(): void
    {
        \Illuminate\Support\Facades\Storage::fake();
        $admin = $this->admin();

        // Two bookings: one completed with a paid amount, one left new.
        $a = $this->newBooking();
        $this->actingAs($admin)->post("/super/bookings/{$a->id}/approve");
        $this->actingAs($admin)->post("/super/bookings/{$a->id}/paid", ['payment_amount' => '2000']);
        $this->actingAs($admin)->post("/super/bookings/{$a->id}/complete", [
            'completion_report' => UploadedFile::fake()->create('r.pdf', 10, 'application/pdf'),
        ]);
        $this->newBooking(); // stays "new"

        $stats = \App\Support\BookingDashboardService::stats();
        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['completed']);
        $this->assertSame(1, $stats['pending']);
        $this->assertSame(2000.0, $stats['revenue']);
        $this->assertSame(50.0, $stats['completionRate']);

        $this->actingAs($admin)->get('/super/bookings/dashboard')
            ->assertOk()->assertSee(__('pc.booking_kpi_total'));
    }

    public function test_dashboard_requires_review_permission(): void
    {
        $nobody = User::factory()->create();
        $this->actingAs($nobody)->get('/super/bookings/dashboard')->assertForbidden();

        $reviewer = User::factory()->create();
        $reviewer->givePermissionTo('review_bookings');
        $this->actingAs($reviewer)->get('/super/bookings/dashboard')->assertOk();
    }

    public function test_dashboard_word_is_not_mistaken_for_a_booking_id(): void
    {
        // The /dashboard route must win over /{booking}; {booking} is numeric-only.
        $admin = $this->admin();
        $this->actingAs($admin)->get('/super/bookings/dashboard')->assertOk();
        $this->actingAs($admin)->get('/super/bookings/not-a-number')->assertNotFound();
    }
}
