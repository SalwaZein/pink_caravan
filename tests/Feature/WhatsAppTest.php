<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\QueueToken;
use App\Models\User;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * WhatsApp Business API through Outreachable (Meta Cloud API v19.0): every
 * business-initiated message is an approved template sent to
 * …/{phone_number_id}/messages with the bearer token. No real request is ever
 * made here — the API is faked.
 */
class WhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://crmapi.outreachable.online/api/meta/v19.0/1340290365829987/messages';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** Point the driver at the (faked) API with the given approved templates. */
    private function configure(array $templates = []): void
    {
        config([
            'services.whatsapp.url'       => self::URL,
            'services.whatsapp.token'     => 'test-token',
            'services.whatsapp.language'  => 'en',
            'services.whatsapp.templates' => $templates,
        ]);
    }

    private function acceptEverything(): void
    {
        Http::fake(['crmapi.outreachable.online/*' => Http::response([
            'messaging_product' => 'whatsapp',
            'contacts'          => [['input' => '971501234567', 'wa_id' => '971501234567']],
            'messages'          => [['id' => 'wamid.TEST123']],
        ], 200)]);
    }

    private function issue(string $name, string $number): QueueToken
    {
        $this->actingAs(User::where('email', 'a.rahman@focp.ae')->firstOrFail())->post('/queue', [
            'clinic_id'       => Clinic::where('code', 'DXB-MOB-01')->firstOrFail()->id,
            'patient_name'    => $name,
            'whatsapp_number' => $number,
        ])->assertRedirect();

        return QueueToken::latest('id')->firstOrFail();
    }

    /** The text values of a sent template's body parameters. */
    private static function bodyParams(Request $request): array
    {
        return array_column($request['template']['components'][0]['parameters'], 'text');
    }

    public function test_a_queue_token_is_sent_as_an_approved_template(): void
    {
        $this->configure(['queue_issued' => 'pc_queue_token']);
        $this->acceptEverything();

        $token = $this->issue('Amina Yousef', '050 123 4567');

        $this->assertStringStartsWith('sent', $token->fresh()->delivery['issued']);

        $clinic = Clinic::where('code', 'DXB-MOB-01')->firstOrFail();

        Http::assertSent(fn (Request $request) => $request->url() === self::URL
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['messaging_product'] === 'whatsapp'
            && $request['recipient_type'] === 'individual'
            && $request['to'] === '971501234567'            // international, digits only
            && $request['type'] === 'template'
            && $request['template']['name'] === 'pc_queue_token'
            && $request['template']['language'] === ['policy' => 'deterministic', 'code' => 'en']
            && self::bodyParams($request) === [$clinic->name, 'DXB-MOB-01-001', '1', '0']);
    }

    public function test_the_next_visitor_gets_the_turn_approaching_template_when_one_enters(): void
    {
        $this->configure([
            'queue_issued'      => 'pc_queue_token',
            'queue_approaching' => 'pc_queue_turn_approaching',
        ]);
        $this->acceptEverything();

        $first  = $this->issue('Amina Yousef', '0501234567');
        $second = $this->issue('Fatima Noor', '0509998877');

        $this->actingAs(User::where('email', 'a.rahman@focp.ae')->firstOrFail())
            ->post("/queue/{$first->id}/enter")->assertRedirect();

        $this->assertStringStartsWith('sent', $second->fresh()->delivery['approaching']);

        Http::assertSent(fn (Request $request) => $request['template']['name'] === 'pc_queue_turn_approaching'
            && $request['to'] === '971509998877'
            && self::bodyParams($request)[1] === 'DXB-MOB-01-002');
    }

    public function test_a_message_whose_template_is_not_approved_yet_is_not_sent(): void
    {
        $this->configure([]); // API connected, but no template names set
        Http::fake();

        $token = $this->issue('Amina Yousef', '0501234567');

        $this->assertStringStartsWith('no_template', $token->fresh()->delivery['issued']);
        Http::assertNothingSent();
    }

    public function test_an_api_rejection_is_reported_as_failed_with_the_reason_logged(): void
    {
        Log::spy();
        $this->configure(['queue_issued' => 'pc_queue_token']);
        Http::fake(['crmapi.outreachable.online/*' => Http::response([
            'error' => ['message' => '(#132001) Template name does not exist in the translation', 'code' => 132001],
        ], 400)]);

        $token = $this->issue('Amina Yousef', '0501234567');

        $this->assertStringStartsWith('failed', $token->fresh()->delivery['issued']);
        Log::shouldHaveReceived('warning')->withArgs(fn ($message) => str_contains($message, '132001'));
    }

    public function test_without_credentials_messages_are_only_logged(): void
    {
        Http::fake();

        $this->assertSame('logged', WhatsApp::send('0501234567', 'queue_called', ['clinic_name' => 'X', 'token_code' => 'X-001'], 'text'));
        $this->assertSame('skipped', WhatsApp::send(null, 'queue_called', [], 'text'));
        Http::assertNothingSent();
    }

    public function test_patient_report_notifications_fill_their_templates_in_order(): void
    {
        $this->configure(['report_ready' => 'pc_report_ready', 'radiology_report' => 'pc_radiology_report']);
        $this->acceptEverything();

        $status = WhatsApp::send('+971501234567', 'radiology_report', [
            'portal_link'  => 'https://pink-caravan-demo.onrender.com/patient',
            'patient_name' => "Fatima\nAl Suwaidi",                  // new lines are not allowed in variables
            'report_ref'   => 'PC-2026-000001',
        ], 'text');

        $this->assertSame('sent', $status);

        Http::assertSent(fn (Request $request) => $request['template']['name'] === 'pc_radiology_report'
            && self::bodyParams($request) === ['Fatima Al Suwaidi', 'PC-2026-000001', 'https://pink-caravan-demo.onrender.com/patient']);
    }

    public function test_the_login_code_uses_an_authentication_template_with_the_copy_code_button(): void
    {
        $this->configure(['patient_otp' => 'pc_login_code']);

        $payload = WhatsApp::payload('+971501234567', 'patient_otp', 'pc_login_code', ['code' => '4821']);

        $this->assertSame('pc_login_code', $payload['template']['name']);
        $this->assertSame([['type' => 'text', 'text' => '4821']], $payload['template']['components'][0]['parameters']);
        $this->assertSame([
            'type'       => 'button',
            'sub_type'   => 'url',
            'index'      => 0,
            'parameters' => [['type' => 'text', 'text' => '4821']],
        ], $payload['template']['components'][1]);
    }

    public function test_an_unknown_message_key_is_a_programming_error(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        WhatsApp::send('0501234567', 'not_a_message', [], 'text');
    }
}
