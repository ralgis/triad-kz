<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Product;
use App\Rules\PhoneRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the public POST /contacts/submit/ and the product-card
 * "Запросить цену" modal. Optional product_id links the lead to a
 * specific product card; null = generic contact form.
 */
final class ContactFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = (string) $this->input('phone', '');
        if ($phone !== '') {
            $this->merge(['phone' => PhoneRule::normalize($phone)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', new PhoneRule],
            'email' => ['nullable', 'email', 'max:120'],
            'message' => ['nullable', 'string', 'max:2000'],
            'product_id' => ['nullable', 'integer', 'exists:'.Product::class.',id'],
        ];
    }
}
