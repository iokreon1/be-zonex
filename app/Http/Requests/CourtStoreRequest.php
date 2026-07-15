<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourtStoreRequest extends FormRequest
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
            'venue_id' => ['required', 'string', 'exists:venues,id'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:badminton,futsal,tennis,basketball,volleyball'],
            'price_per_hour' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:2048'],
            'primary_image_index' => ['nullable', 'integer', 'min:0', 'required_with:images'],
        ];
    }
}
