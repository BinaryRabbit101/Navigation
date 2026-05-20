<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WebsiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'url' => ['required', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => [
                $this->isMethod('post') ? 'nullable' : 'sometimes',
                'image',
                'mimes:jpg,jpeg,png,webp,svg',
                'max:4096',
            ],
        ];
    }
}
