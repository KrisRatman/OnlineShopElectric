<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'images',
            'brand',
            'category.parent',
            'attributeValues' => fn ($q) => $q->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
                ->orderBy('attributes.sort')
                ->select('attribute_values.*'),
            'attributeValues.attribute',
        ]);

        $related = Product::query()
            ->active()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->where('stock', '>', 0)
            ->with(['mainImage', 'brand'])
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('products.show', compact('product', 'related'));
    }
}
