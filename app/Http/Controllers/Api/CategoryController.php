<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * دریافت لیست تمام دسته‌بندی‌ها
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = Category::where('is_active', true)
            ->orderBy('order', 'asc')
            ->get(['category_id', 'name', 'icon', 'description']);

        return response()->json([
            'status' => 'success',
            'categories' => $categories
        ]);
    }

    /**
     * جستجوی دسته‌بندی‌ها
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $query = $request->input('query', '');
        
        $categories = Category::where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhere('keywords', 'LIKE', "%{$query}%");
            })
            ->orderBy('order', 'asc')
            ->get(['category_id', 'name', 'icon', 'description']);

        return response()->json([
            'status' => 'success',
            'query' => $query,
            'categories' => $categories,
            'count' => $categories->count()
        ]);
    }

    /**
     * دریافت دسته‌بندی‌های پرطرفدار
     *
     * @return \Illuminate\Http\Response
     */
    public function popular()
    {
        // این متد دسته‌بندی‌های پرطرفدار را براساس تعداد خدمات یا محبوبیت برمی‌گرداند
        $categories = Category::where('is_active', true)
            ->join(DB::raw('(SELECT category_id, COUNT(*) as service_count FROM services GROUP BY category_id) as service_stats'), 
                  'categories.category_id', '=', 'service_stats.category_id')
            ->orderBy('service_stats.service_count', 'desc')
            ->take(10)
            ->get(['categories.category_id', 'categories.name', 'categories.icon', 'categories.description']);

        return response()->json([
            'status' => 'success',
            'categories' => $categories
        ]);
    }

    /**
     * دریافت جزئیات یک دسته‌بندی به همراه زیر دسته‌ها
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = Category::with(['subCategories' => function($query) {
                $query->where('is_active', true);
            }])
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'category' => $category
        ]);
    }
}
