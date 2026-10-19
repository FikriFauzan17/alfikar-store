<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $statistics = [
            'publishedProducts' => Product::query()->published()->count(),
            'outOfStockProducts' => Product::query()->where('stock', 0)->count(),
            'articles' => Article::query()->count(),
        ];

        if (auth()->user()->can('access-admin')) {
            $statistics['users'] = User::query()->count();
        }

        return view('admin.dashboard', ['statistics' => $statistics]);
    }
}
