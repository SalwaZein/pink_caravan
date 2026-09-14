<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The local .env carries the real WhatsApp credentials. Tests must never
        // message a real number: the API starts unconfigured (stub) and any HTTP
        // call a test has not explicitly faked is refused.
        config(['services.whatsapp.url' => null, 'services.whatsapp.token' => null]);
        Http::preventStrayRequests();
    }

    /**
     * A complete Patient History & Record Sheet (Form 3) payload.
     *
     * Every field on the registration form is mandatory when it is filed (business
     * feedback round 3), so tests that submit a record post the whole thing. Pass
     * `$overrides` for whatever the test is actually about; pass
     * `['action' => 'draft']` for a deliberately partial save.
     */
    protected function registrationPayload(array $overrides = []): array
    {
        $personal = [];
        foreach (['lumpectomy', 'biopsy', 'hyperplasia', 'hrt', 'personal_bc', 'ovarian', 'fam_ovarian', 'fam_male_bc', 'implant'] as $item) {
            $personal[$item] = 'no';
        }

        return array_merge([
            'action'            => 'submit',
            'emirates_id'       => '784-1990-1234567-1',
            'full_name'         => 'Test Patient',
            'dob'               => '1990-05-14',
            'nationality'       => 'Emirati',
            'emirate'           => 'dubai',
            'marital_status'    => 'married',
            'mobile1'           => '+971500000000',
            'mobile2'           => '+971500000099',
            'email'             => 'patient@example.com',
            'age_at_menarche'   => 13,
            'lmp'               => '2026-08-01',
            'breast_implant'    => 'no',
            'personal'          => $personal,
            'personal_notes'    => [],
            // "none" at every degree = no family history of breast cancer.
            'family'            => [
                'deg1' => ['relationship' => 'none'],
                'deg2' => ['relationship' => 'none'],
                'deg3' => ['relationship' => 'none'],
            ],
            'cbe_result'        => 'normal',
            'last_mammogram'    => '2024-03-10',
            'consent'           => '1',
            'patient_signature' => 'data:image/png;base64,iVBORw0KGgo=',
            'signed_at'         => '2026-09-14',
        ], $overrides);
    }
}
