<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
class CategoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => Media::url($this->image),
            'swatch' => $this->swatch,
            'productCount' => $this->whenCounted('products'),
            'href' => route('shop.category', $this->resource),
        ];
    }
}
