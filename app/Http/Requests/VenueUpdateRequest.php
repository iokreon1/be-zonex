<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VenueUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['sometimes', 'required', 'string'],
            'city' => ['sometimes', 'required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'featured_image' => ['nullable', 'image', 'max:2048'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'commission_rate' => ['sometimes', 'required', 'numeric', 'between:0,1'],
            'status' => ['sometimes', 'required', 'string', 'in:active,pending,suspended'],
        ];
    }
}
