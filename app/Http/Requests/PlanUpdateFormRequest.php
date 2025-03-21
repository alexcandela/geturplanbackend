<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanUpdateFormRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:700',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'url' => 'required|url',
            'categories' => 'required|string',
            'principal_image' => 'image|mimes:jpeg,png,jpg,svg|max:5120',
            'secondary_images' => 'array|max:4',
            'secondary_images.*' => 'image|mimes:jpeg,png,jpg,svg|max:5120', 
            'imagesToDelete' => 'string'
        ];
    }
}
