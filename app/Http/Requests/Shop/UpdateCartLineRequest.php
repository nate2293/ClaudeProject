<?php

namespace App\Http\Requests\Shop;

use App\Cart\Cart;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCartLineRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config('shop.max_quantity_per_line')],
        ];
    }

    /** Allow lowering any quantity, but only raise it as far as the stock goes. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $line = app(Cart::class)->line((string) $this->route('line'));

                if (! $line || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $quantity = $this->integer('quantity');

                if ($quantity > $line->quantity && $quantity > $line->available) {
                    $validator->errors()->add('quantity', $line->available === 0
                        ? 'Sorry, this has sold out.'
                        : "Only {$line->available} left.");
                }
            },
        ];
    }
}
