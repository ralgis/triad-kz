<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CustomerType;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Rules\BinRule;
use App\Rules\PhoneRule;
use App\Services\Orders\CheckoutData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates and shapes the public POST /checkout/submit/ payload.
 *
 * Two-mode validation via Rule::requiredIf on `customer_type`:
 *   - individual → name + phone required, company/БИН skipped
 *   - legal      → name + phone + company_name + БИН required
 *
 * On success, controller calls `$request->toCheckoutData()` to get the
 * typed DTO that OrderService::create() expects.
 */
final class CheckoutFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public endpoint — anyone can post.
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = (string) $this->input('customer_phone', '');
        if ($phone !== '') {
            $this->merge(['customer_phone' => PhoneRule::normalize($phone)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isLegal = $this->input('customer_type') === CustomerType::Legal->value;

        return [
            'customer_type' => ['required', new Enum(CustomerType::class)],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:120'],
            'customer_phone' => ['required', new PhoneRule],

            'customer_company_name' => [Rule::requiredIf($isLegal), 'nullable', 'string', 'max:200'],
            'customer_bin' => [Rule::requiredIf($isLegal), 'nullable', new BinRule],

            'customer_address' => ['nullable', 'string', 'max:300'],

            'delivery_method' => ['required', new Enum(DeliveryMethod::class)],
            'delivery_address' => [
                Rule::requiredIf($this->input('delivery_method') === DeliveryMethod::Delivery->value),
                'nullable',
                'string',
                'max:300',
            ],

            'payment_method' => ['required', new Enum(PaymentMethod::class)],

            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_company_name.required' => 'Для юр.лица укажите название организации.',
            'customer_bin.required' => 'Для юр.лица укажите БИН.',
            'delivery_address.required' => 'При доставке по адресу укажите адрес.',
        ];
    }

    public function toCheckoutData(): CheckoutData
    {
        return new CheckoutData(
            customerType: CustomerType::from((string) $this->validated('customer_type')),
            customerName: (string) $this->validated('customer_name'),
            customerEmail: (string) $this->validated('customer_email'),
            customerPhone: (string) $this->validated('customer_phone'),
            customerCompanyName: $this->validated('customer_company_name'),
            customerBin: $this->validated('customer_bin'),
            customerAddress: $this->validated('customer_address'),
            deliveryMethod: DeliveryMethod::from((string) $this->validated('delivery_method')),
            deliveryAddress: $this->validated('delivery_address'),
            paymentMethod: PaymentMethod::from((string) $this->validated('payment_method')),
            comment: $this->validated('comment'),
        );
    }
}
