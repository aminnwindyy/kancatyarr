<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdSettingRequest;
use App\Models\AdSetting;
use App\Services\AdSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class AdSettingController extends Controller
{
    protected $adSettingService;

    /**
     * سازنده کلاس
     *
     * @param AdSettingService $adSettingService
     */
    public function __construct(AdSettingService $adSettingService)
    {
        $this->adSettingService = $adSettingService;
    }

    /**
     * دریافت فهرست تنظیمات تبلیغات با فیلتر
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'placement' => $request->input('placement'),
                'service' => $request->input('service'),
                'active_only' => $request->boolean('active_only'),
            ];
            
            // حذف کلیدهای با مقدار null
            $filters = array_filter($filters, function ($value) {
                return $value !== null;
            });
            
            $settings = $this->adSettingService->getAllSettings($filters);
            
            return response()->json([
                'status' => true,
                'data' => $settings,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در دریافت فهرست تنظیمات تبلیغات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * نمایش جزئیات یک تنظیم تبلیغات
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $setting = $this->adSettingService->getById($id);
            
            if (!$setting) {
                return response()->json([
                    'status' => false,
                    'message' => 'تنظیم مورد نظر یافت نشد.',
                ], 404);
            }
            
            return response()->json([
                'status' => true,
                'data' => $setting,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در دریافت جزئیات تنظیم تبلیغات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ایجاد تنظیم تبلیغات جدید
     *
     * @param AdSettingRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AdSettingRequest $request)
    {
        try {
            $user = Auth::user();

            if (!$user || !$user->is_admin) {
                return response()->json([
                    'status' => false,
                    'message' => 'شما دسترسی به این بخش را ندارید',
                ], 403);
            }
            
            $data = $request->validated();
            
            $adSetting = $this->adSettingService->create($data, $user->user_id);
            
            return response()->json([
                'status' => true,
                'message' => 'تنظیم تبلیغات با موفقیت ایجاد شد.',
                'data' => $adSetting,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در ایجاد تنظیم تبلیغات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * به‌روزرسانی تنظیم تبلیغات
     *
     * @param AdSettingRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(AdSettingRequest $request, $id)
    {
        try {
            $user = Auth::user();

            if (!$user || !$user->is_admin) {
                return response()->json([
                    'status' => false,
                    'message' => 'شما دسترسی به این بخش را ندارید',
                ], 403);
            }
            
            $setting = $this->adSettingService->getById($id);
            
            if (!$setting) {
                return response()->json([
                    'status' => false,
                    'message' => 'تنظیم مورد نظر یافت نشد.',
                ], 404);
            }
            
            $data = $request->validated();
            
            $adSetting = $this->adSettingService->update($id, $data, $user->user_id);
            
            return response()->json([
                'status' => true,
                'message' => 'تنظیم تبلیغات با موفقیت به‌روزرسانی شد.',
                'data' => $adSetting,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در به‌روزرسانی تنظیم تبلیغات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف تنظیم تبلیغات
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();

            if (!$user || !$user->is_admin) {
                return response()->json([
                    'status' => false,
                    'message' => 'شما دسترسی به این بخش را ندارید',
                ], 403);
            }
            
            $setting = $this->adSettingService->getById($id);
            
            if (!$setting) {
                return response()->json([
                    'status' => false,
                    'message' => 'تنظیم مورد نظر یافت نشد.',
                ], 404);
            }
            
            $this->adSettingService->delete($id);
            
            return response()->json([
                'status' => true,
                'message' => 'تنظیم تبلیغات با موفقیت حذف شد.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در حذف تنظیم تبلیغات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تغییر وضعیت فعال/غیرفعال
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $user = Auth::user();

            if (!$user || !$user->is_admin) {
                return response()->json([
                    'status' => false,
                    'message' => 'شما دسترسی به این بخش را ندارید',
                ], 403);
            }
            
            $request->validate([
                'is_active' => 'required|boolean',
            ]);
            
            $setting = $this->adSettingService->getById($id);
            
            if (!$setting) {
                return response()->json([
                    'status' => false,
                    'message' => 'تنظیم مورد نظر یافت نشد.',
                ], 404);
            }
            
            $isActive = (bool) $request->input('is_active');
            $adSetting = $this->adSettingService->toggleStatus($id, $isActive, $user->user_id);
            
            $statusText = $isActive ? 'فعال' : 'غیرفعال';
            
            return response()->json([
                'status' => true,
                'message' => "تنظیم تبلیغات با موفقیت {$statusText} شد.",
                'data' => $adSetting,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در تغییر وضعیت تنظیم تبلیغات: ' . $e->getMessage(),
            ], 500);
        }
    }
} 