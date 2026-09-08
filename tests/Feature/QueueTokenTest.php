<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\QueueToken;
use App\Models\User;
use App\Support\Messenger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * WhatsApp token & queue management (business feedback #7): the volunteer desk
 * issues tokens per clinic per day, and the queue advances with automatic
 * WhatsApp messages — including the "your turn is approaching" heads-up that
 * fires when the previous visitor walks into the clinic.
 */
class QueueTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function volunteer(): User
    {
        return User::where('email', 'a.rahman@focp.ae')->firstOrFail();
    }

    private function dubai(): Clinic
    {
        return Clinic::where('code', 'DXB-MOB-01')->firstOrFail();
    }

    private function issue(string $name, string $number = '0501234567'): QueueToken
    {
        $this->actingAs($this->volunteer())->post('/queue', [
            'clinic_id'       => $this->dubai()->id,
            'patient_name'    => $name,
            'whatsapp_number' => $number,
        ])->assertRedirect();

        return QueueToken::latest('id')->firstOrFail();
    }

    public function test_volunteer_lands_on_the_queue_desk_and_can_open_it(): void
    {
        $volunteer = $this->volunteer();

        $this->assertSame('queue.index', $volunteer->homeRoute());
        $this->assertTrue($volunteer->can('manage_queue'));
        $this->assertFalse($volunteer->can('fill_record_sheet'));

        $this->actingAs($volunteer)->get('/queue')->assertOk()->assertSee('Queue desk');
    }

    public function test_issuing_a_token_numbers_it_per_clinic_and_messages_the_visitor(): void
    {
        Log::spy();

        $first  = $this->issue('Amina Yousef');
        $second = $this->issue('Fatima Noor', '00971509998877');

        $this->assertSame(1, $first->number);
        $this->assertSame(2, $second->number);
        $this->assertSame('DXB-MOB-01-001', $first->code);
        $this->assertSame(QueueToken::WAITING, $first->status);
        $this->assertSame($this->volunteer()->id, $first->issued_by);

        // Local numbers are normalised to international form before sending.
        $this->assertSame('+971501234567', $first->whatsapp_number);
        $this->assertSame('+971509998877', $second->whatsapp_number);

        // No gateway configured in tests → the WhatsApp message is logged (stub).
        $this->assertStringStartsWith('logged', $first->fresh()->delivery['issued']);
        Log::shouldHaveReceived('info')->withArgs(
            fn ($message) => str_contains($message, '[stub whatsapp]') && str_contains($message, 'DXB-MOB-01-001')
        );
    }

    public function test_a_second_clinic_starts_its_own_numbering(): void
    {
        $this->issue('Amina Yousef');

        $sharjah = Clinic::where('code', 'SHJ-FIX-01')->firstOrFail();
        $admin   = User::where('email', 'anish@focp.ae')->firstOrFail(); // super admin: every clinic

        $this->actingAs($admin)->post('/queue', [
            'clinic_id'       => $sharjah->id,
            'patient_name'    => 'Hessa Ali',
            'whatsapp_number' => '0509999999',
        ])->assertRedirect();

        $token = QueueToken::latest('id')->firstOrFail();

        $this->assertSame(1, $token->number);
        $this->assertSame('SHJ-FIX-01-001', $token->code);
    }

    public function test_marking_a_visitor_as_entered_warns_the_next_one_automatically(): void
    {
        $first  = $this->issue('Amina Yousef');
        $second = $this->issue('Fatima Noor', '0502223344');
        $third  = $this->issue('Hessa Ali', '0503334455');

        $this->actingAs($this->volunteer())
            ->post("/queue/{$first->id}/enter")
            ->assertRedirect();

        $this->assertSame(QueueToken::IN_CLINIC, $first->fresh()->status);

        // Only the very next waiting visitor is warned — not the whole queue.
        $this->assertSame(QueueToken::NOTIFIED, $second->fresh()->status);
        $this->assertNotNull($second->fresh()->notified_at);
        $this->assertArrayHasKey('approaching', $second->fresh()->delivery);

        $this->assertSame(QueueToken::WAITING, $third->fresh()->status);
    }

    public function test_calling_and_closing_a_token_moves_it_through_the_board(): void
    {
        $token = $this->issue('Amina Yousef');

        $this->actingAs($this->volunteer())->post("/queue/{$token->id}/call")->assertRedirect();
        $this->assertSame(QueueToken::CALLED, $token->fresh()->status);
        $this->assertNotNull($token->fresh()->called_at);

        $this->actingAs($this->volunteer())
            ->post("/queue/{$token->id}/close", ['outcome' => QueueToken::SERVED])
            ->assertRedirect();

        $token = $token->fresh();
        $this->assertSame(QueueToken::SERVED, $token->status);
        $this->assertNotNull($token->served_at);
        $this->assertFalse($token->isOpen());
    }

    public function test_a_volunteer_cannot_run_a_queue_for_a_clinic_they_are_not_assigned_to(): void
    {
        $sharjah = Clinic::where('code', 'SHJ-FIX-01')->firstOrFail();

        $this->actingAs($this->volunteer())->post('/queue', [
            'clinic_id'       => $sharjah->id,
            'patient_name'    => 'Hessa Ali',
            'whatsapp_number' => '0509999999',
        ])->assertForbidden();

        $this->assertSame(0, QueueToken::count());
    }

    public function test_clinical_staff_without_the_queue_permission_are_refused(): void
    {
        $doctor = User::where('email', 'l.hassan@focp.ae')->firstOrFail();

        $this->actingAs($doctor)->get('/queue')->assertForbidden();
    }

    public function test_numbers_are_normalised_consistently(): void
    {
        $this->assertSame('+971501234567', Messenger::normalise('050 123 4567'));
        $this->assertSame('+971501234567', Messenger::normalise('00971501234567'));
        $this->assertSame('+971501234567', Messenger::normalise('+971 50 123 4567'));
        $this->assertNull(Messenger::normalise(''));
        $this->assertNull(Messenger::normalise(null));
    }
}
