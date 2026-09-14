<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage transactions');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'occurred_on' => ['required', 'date'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*' => ['file', 'max:8192', 'mimes:pdf,jpg,jpeg,png,webp,heic'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documents.*.max' => 'Chaque document doit faire moins de 8 Mo.',
            'documents.*.mimes' => 'Formats acceptes : PDF, JPG, PNG, WEBP, HEIC.',
        ];
    }
}
