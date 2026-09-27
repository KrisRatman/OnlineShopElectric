<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $categories = Category::query()
            ->active()
            ->roots()
            ->with(['children' => fn ($q) => $q->active()])
            ->orderBy('sort')
            ->get();

        $featured = Product::query()
            ->active()
            ->where('is_featured', true)
            ->with(['mainImage', 'brand'])
            ->orderByDesc('stock')
            ->limit(8)
            ->get();

        $latest = Product::query()
            ->active()
            ->where('stock', '>', 0)
            ->with(['mainImage', 'brand'])
            ->latest('id')
            ->limit(4)
            ->get();

        return view('home', compact('categories', 'featured', 'latest'));
    }
}
