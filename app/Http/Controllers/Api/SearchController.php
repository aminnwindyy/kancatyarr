<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Models\Order;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * جستجو در تمام محتوای سایت
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $query = $request->input('query');
        $results = [];

        // بررسی دسترسی کاربر برای هر نوع محتوا
        $user = $request->user();

        // جستجو در کاربران (فقط برای مدیرانی که دسترسی مدیریت کاربران دارند)
        if ($user->can('users.view')) {
            $users = User::where('first_name', 'like', "%{$query}%")
                ->orWhere('last_name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->limit(10)
                ->get(['user_id', 'first_name', 'last_name', 'email']);

            if ($users->count() > 0) {
                $results['users'] = [
                    'name' => 'کاربران',
                    'count' => $users->count(),
                    'items' => $users->map(function ($user) {
                        return [
                            'id' => $user->user_id,
                            'title' => $user->first_name . ' ' . $user->last_name,
                            'subtitle' => $user->email,
                            'url' => '/admin/users/' . $user->user_id,
                        ];
                    })
                ];
            }
        }

        // جستجو در محصولات (برای مدیرانی که دسترسی محصولات دارند)
        if ($user->can('products.view')) {
            $products = Product::where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->limit(10)
                ->get();

            if ($products->count() > 0) {
                $results['products'] = [
                    'name' => 'محصولات',
                    'count' => $products->count(),
                    'items' => $products->map(function ($product) {
                        return [
                            'id' => $product->product_id,
                            'title' => $product->name,
                            'subtitle' => substr($product->description, 0, 100),
                            'url' => '/admin/products/' . $product->product_id,
                        ];
                    })
                ];
            }
        }

        // جستجو در درخواست‌ها (برای مدیرانی که دسترسی مدیریت درخواست‌ها دارند)
        if ($user->can('requests.view')) {
            $tickets = SupportTicket::where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->limit(10)
                ->get();

            if ($tickets->count() > 0) {
                $results['tickets'] = [
                    'name' => 'درخواست‌ها',
                    'count' => $tickets->count(),
                    'items' => $tickets->map(function ($ticket) {
                        return [
                            'id' => $ticket->id,
                            'title' => $ticket->title,
                            'subtitle' => substr($ticket->description, 0, 100),
                            'url' => '/admin/requests/' . $ticket->id,
                        ];
                    })
                ];
            }
        }

        // در اینجا می‌توانید سایر جستجوها را بر اساس نیاز خود اضافه کنید

        return response()->json([
            'query' => $query,
            'results' => $results,
            'total_count' => array_sum(array_map(function ($category) {
                return $category['count'];
            }, $results))
        ]);
    }
}
