<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\InventoryItem;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __invoke(): View
    {
        $cart = session('cart', []);
        $itemsByCategory = MenuItem::all()->groupBy('category');
        $categories = collect(['MEALS', 'SNACKS', 'DRINKS', 'EXTRAS'])
            ->mapWithKeys(fn (string $category) => [
                $category => $itemsByCategory->get($category, collect())->map(fn (MenuItem $item) => [
                    'key' => $item->item_key,
                    'name' => $item->name,
                    'price' => $item->price,
                    'options' => $item->options ?? [],
                ])->values(),
            ]);

        return view('pos', [
            'categories' => $categories,
            'cartCount' => array_sum($cart),
            'lowStockItems' => InventoryItem::where('low_stock_threshold', '>', 0)
                ->whereColumn('quantity', '<=', 'low_stock_threshold')
                ->orderBy('quantity')
                ->get(),
        ]);
    }
}
