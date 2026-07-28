<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Emirates ID card reader — dev mock.
 *
 * A browser cannot read a smart card directly, so in production the registration
 * form calls a LOCAL reader bridge (the official Emirates ID Toolkit / a small
 * agent on the clinic device) at services.emirates_id.reader_url. That bridge
 * must return the normalised contract below. When no reader_url is configured
 * the form falls back to this mock so the flow is testable without hardware.
 *
 * Normalised response contract (what the real bridge must also return):
 * {
 *   "success": true,
 *   "data": {
 *     "emirates_id":   "784-1990-1234567-1",
 *     "full_name_en":  "Full Name",
 *     "full_name_ar":  "الاسم الكامل",
 *     "date_of_birth": "1990-05-14",   // ISO yyyy-mm-dd
 *     "nationality":   "Emirati",       // English demonym
 *     "gender":        "F",             // M | F
 *     "card_number":   "123456789",
 *     "expiry_date":   "2030-05-14"
 *   }
 * }
 */
class EmiratesIdController extends Controller
{
    public function read(): JsonResponse
    {
        // Static sample so the nurse can see the auto-fill work end to end.
        return response()->json([
            'success' => true,
            'data' => [
                'emirates_id'   => '784-1990-1234567-1',
                'full_name_en'  => 'Aisha Al Marri',
                'full_name_ar'  => 'عائشة المري',
                'date_of_birth' => '1990-05-14',
                'nationality'   => 'Emirati',
                'gender'        => 'F',
                'card_number'   => '123456789',
                'expiry_date'   => '2030-05-14',
            ],
        ]);
    }
}
