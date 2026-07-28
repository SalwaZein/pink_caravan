<?php

namespace App\Http\Requests;

use App\Enums\ClinicType;
use App\Enums\Emirate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'code'           => ['required', 'string', 'max:255', Rule::unique('clinics', 'code')],
            'type'           => ['required', new Enum(ClinicType::class)],
            'emirate'        => ['required', new Enum(Emirate::class)],
            'address'        => ['nullable', 'string', 'max:255'],
            'daily_capacity' => ['nullable', 'integer', 'min:0'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone'  => ['nullable', 'string', 'max:255'],
            'is_active'      => ['boolean'],
            'staff'          => ['array'],
            'staff.*'        => ['integer', 'exists:users,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
