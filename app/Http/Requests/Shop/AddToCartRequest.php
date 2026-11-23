<?php

namespace App\Http\Requests\Shop;

use App\Cart\Cart;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AddToCartRequest extends FormRequest
{
    private ?Product $product = null;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'colour' => ['required', 'string'],
            'size' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config('shop.max_quantity_per_line')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'colour.required' => 'Please choose a colour.',
            'size.required' => 'Please choose a size.',
        ];
    }

    /** Make sure the chosen colour and size are offered and in stock. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $product = $this->product();

                if (! $product->hasColour($this->string('colour')->toString())) {
                    $validator->errors()->add('colour', 'That colour is not available.');
                }

                if (! $product->hasSize($this->string('size')->toString())) {
                    $validator->errors()->add('size', 'That size is not available.');
                }

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->checkStock($validator, $product);
            },
        ];
    }

    private function checkStock(Validator $validator, Product $product): void
    {
        $cart = app(Cart::class);
        $colour = $this->string('colour')->toString();
        $size = $this->string('size')->toString();
        $available = $cart->availableFor($product, $colour, $size);
        $inBag = $cart->quantityOf($product, $colour, $size);

        if ($available === 0) {
            $validator->errors()->add('size', 'Sorry, that size is sold out in this colour.');
        } elseif ($inBag + $this->integer('quantity') > $available) {
            $validator->errors()->add('quantity', $inBag > 0
                ? "Only {$available} left, and you already have {$inBag} in your bag."
                : "Only {$available} left in this size.");
        }
    }

    public function product(): Product
    {
        return $this->product ??= Product::findOrFail($this->integer('product_id'));
    }
}
