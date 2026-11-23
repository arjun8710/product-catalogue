<?php

namespace App\Http\Requests;

use App\Catalogue\Product;
use App\Enquiries\Enquiry;
use Illuminate\Foundation\Http\FormRequest;

final class StoreEnquiryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'productId' => ['required', 'integer', 'min:1'],
            // Name ends up in an email header (Reply-To), so reject line breaks outright.
            'name' => ['required', 'string', 'max:100', 'not_regex:/[\r\n]/'],
            'email' => ['required', 'string', 'max:254', 'email:rfc,strict'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-.\s]+$/'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.not_regex' => 'The name may not contain line breaks.',
            'phone.regex' => 'The phone number may only contain digits, spaces and + ( ) - . characters.',
        ];
    }

    public function toEnquiry(Product $product): Enquiry
    {
        return new Enquiry(
            product: $product,
            name: $this->string('name')->toString(),
            email: $this->string('email')->toString(),
            phone: $this->filled('phone') ? $this->string('phone')->toString() : null,
            message: $this->filled('message') ? $this->string('message')->toString() : null,
        );
    }
}
