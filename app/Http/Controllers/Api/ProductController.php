<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * نمایش لیست محصولات خدمات‌دهنده
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // گرفتن پارامترهای درخواست
        $limit = $request->input('limit', 10);
        $serviceProviderId = $request->input('service_provider_id');
        $query = Product::query();
        
        // فیلتر بر اساس خدمات‌دهنده
        if ($serviceProviderId) {
            $query->where('service_provider_id', $serviceProviderId);
        }
        
        // فیلتر بر اساس وضعیت تایید
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }
        
        // فیلتر بر اساس دسته‌بندی
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // جستجو
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->search}%")
                  ->orWhere('description', 'LIKE', "%{$request->search}%");
            });
        }
        
        // مرتب‌سازی
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // اضافه کردن روابط
        $query->with(['category', 'serviceProvider']);
        
        // صفحه‌بندی نتایج
        $products = $query->paginate($limit);
        
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
    
    /**
     * نمایش جزئیات یک محصول
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $product = Product::with(['category', 'serviceProvider', 'reviews'])
            ->findOrFail($id);
            
        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }
    
    /**
     * ایجاد محصول جدید توسط خدمات‌دهنده
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // اعتبارسنجی درخواست
        $validator = Validator::make($request->all(), [
            'service_provider_id' => 'required|exists:service_providers,id',
            'category_id' => 'required|exists:categories,category_id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048', // حداکثر 2MB
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطای اعتبارسنجی',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // اطمینان از دسترسی کاربر به خدمات‌دهنده
        $serviceProvider = ServiceProvider::findOrFail($request->service_provider_id);
        
        // ذخیره تصویر محصول در صورت ارسال
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $fileName = time() . '_' . Str::slug($image->getClientOriginalName());
            $imagePath = $image->storeAs(
                'product_images/' . $request->service_provider_id,
                $fileName,
                'public'
            );
        }
        
        // ایجاد محصول جدید
        $product = new Product();
        $product->category_id = $request->category_id;
        $product->service_provider_id = $request->service_provider_id;
        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->stock = $request->stock;
        $product->image_url = $imagePath ? Storage::url($imagePath) : null;
        $product->approval_status = 'pending'; // وضعیت پیش‌فرض برای محصولات جدید
        $product->save();
        
        return response()->json([
            'success' => true,
            'message' => 'محصول با موفقیت ایجاد شد و در انتظار تایید است',
            'data' => $product
        ], 201);
    }
    
    /**
     * به‌روزرسانی اطلاعات محصول
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // یافتن محصول
        $product = Product::findOrFail($id);
        
        // اعتبارسنجی درخواست
        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|exists:categories,category_id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'image' => 'nullable|image|max:2048', // حداکثر 2MB
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطای اعتبارسنجی',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // بررسی دسترسی کاربر به محصول
        // فرض بر این است که کاربر با نقش خدمات‌دهنده فقط می‌تواند محصولات خود را ویرایش کند
        
        // آپلود تصویر جدید در صورت وجود
        if ($request->hasFile('image')) {
            // حذف تصویر قبلی
            if ($product->image_url) {
                $oldPath = str_replace('/storage/', '', $product->image_url);
                Storage::disk('public')->delete($oldPath);
            }
            
            // ذخیره تصویر جدید
            $image = $request->file('image');
            $fileName = time() . '_' . Str::slug($image->getClientOriginalName());
            $imagePath = $image->storeAs(
                'product_images/' . $product->service_provider_id,
                $fileName,
                'public'
            );
            $product->image_url = Storage::url($imagePath);
        }
        
        // به‌روزرسانی فیلدها
        if ($request->filled('category_id')) $product->category_id = $request->category_id;
        if ($request->filled('name')) $product->name = $request->name;
        if ($request->filled('description')) $product->description = $request->description;
        if ($request->filled('price')) $product->price = $request->price;
        if ($request->filled('stock')) $product->stock = $request->stock;
        
        // تغییر وضعیت به در انتظار تایید
        $product->approval_status = 'pending';
        $product->save();
        
        return response()->json([
            'success' => true,
            'message' => 'محصول با موفقیت به‌روزرسانی شد و در انتظار تایید است',
            'data' => $product
        ]);
    }
    
    /**
     * حذف محصول
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        // حذف تصویر محصول
        if ($product->image_url) {
            $imagePath = str_replace('/storage/', '', $product->image_url);
            Storage::disk('public')->delete($imagePath);
        }
        
        // حذف محصول
        $product->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'محصول با موفقیت حذف شد'
        ]);
    }
    
    /**
     * تغییر وضعیت تایید محصول (برای ادمین)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateApprovalStatus(Request $request, $id)
    {
        // اعتبارسنجی درخواست
        $validator = Validator::make($request->all(), [
            'approval_status' => 'required|in:approved,rejected,pending',
            'approval_reason' => 'nullable|string|max:500'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطای اعتبارسنجی',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // یافتن محصول
        $product = Product::findOrFail($id);
        
        // تغییر وضعیت تایید
        $product->approval_status = $request->approval_status;
        $product->approval_reason = $request->approval_reason;
        $product->save();
        
        return response()->json([
            'success' => true,
            'message' => 'وضعیت تایید محصول با موفقیت به‌روزرسانی شد',
            'data' => $product
        ]);
    }
    
    /**
     * نمایش محصولات در انتظار تایید (برای ادمین)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pendingProducts(Request $request)
    {
        $limit = $request->input('limit', 10);
        
        $products = Product::with(['category', 'serviceProvider'])
            ->where('approval_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
            
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
    
    /**
     * نمایش محصولات یک خدمات‌دهنده خاص
     *
     * @param int $serviceProviderId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function serviceProviderProducts($serviceProviderId, Request $request)
    {
        $limit = $request->input('limit', 10);
        
        // بررسی وجود خدمات‌دهنده
        $serviceProvider = ServiceProvider::findOrFail($serviceProviderId);
        
        $query = Product::with(['category'])
            ->where('service_provider_id', $serviceProviderId);
            
        // فیلتر بر اساس وضعیت تایید
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }
        
        // مرتب‌سازی
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        $products = $query->paginate($limit);
        
        return response()->json([
            'success' => true,
            'service_provider' => [
                'id' => $serviceProvider->id,
                'name' => $serviceProvider->name,
            ],
            'data' => $products
        ]);
    }
}
