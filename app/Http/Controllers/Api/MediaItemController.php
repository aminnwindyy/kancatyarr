<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaItemRequest;
use App\Models\MediaItem;
use App\Services\MediaItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class MediaItemController extends Controller
{
    protected $mediaItemService;

    /**
     * سازنده کلاس
     *
     * @param MediaItemService $mediaItemService
     */
    public function __construct(MediaItemService $mediaItemService)
    {
        $this->mediaItemService = $mediaItemService;
    }

    /**
     * دریافت فهرست آیتم‌ها با فیلتر نوع
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $type = $request->input('type');
            $activeOnly = $request->input('active_only', true);
            $position = $request->input('position');
            
            $filters = [
                'active_only' => $activeOnly,
                'position' => $position,
            ];
            
            $items = $this->mediaItemService->list($type, $filters);
            
            return response()->json([
                'status' => true,
                'data' => $items,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در دریافت فهرست آیتم‌ها: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * دریافت بنرها یا اسلایدرها براساس موقعیت
     * 
     * @param Request $request
     * @param string $position
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByPosition(Request $request, $position)
    {
        try {
            if (!in_array($position, MediaItem::getAllowedPositions())) {
                return response()->json([
                    'status' => false,
                    'message' => 'موقعیت نامعتبر است.',
                ], 400);
            }
            
            $type = $request->input('type', null); // می‌تواند 'banner' یا 'slider' باشد
            
            $query = MediaItem::currentlyActive()
                ->ofPosition($position)
                ->ordered();
                
            // اگر نوع مشخص شده باشد، فقط آن نوع را فیلتر می‌کنیم
            if ($type && in_array($type, MediaItem::getAllowedTypes())) {
                $query->ofType($type);
            }
            
            $items = $query->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => $item->type,
                    'title' => $item->title,
                    'image_url' => $item->image_url,
                    'link' => $item->link,
                    'order' => $item->order,
                    'position' => $item->position,
                    'provider' => $item->provider,
                    'script_code' => $item->provider !== 'custom' ? $item->script_code : null,
                ];
            });
            
            return response()->json([
                'status' => true,
                'data' => $items,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در دریافت آیتم‌ها: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * نمایش جزئیات یک آیتم
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $item = $this->mediaItemService->getById($id);
            
            if (!$item) {
                return response()->json([
                    'status' => false,
                    'message' => 'آیتم مورد نظر یافت نشد.',
                ], 404);
            }
            
            return response()->json([
                'status' => true,
                'data' => $item,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در دریافت جزئیات آیتم: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ایجاد آیتم جدید
     *
     * @param MediaItemRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(MediaItemRequest $request)
    {
        try {
            // فقط برای نوع custom نیاز به آپلود تصویر داریم
            if ($request->provider === 'custom' && !$request->hasFile('image')) {
                return response()->json([
                    'status' => false,
                    'message' => 'برای بنر اختصاصی، تصویر آپلود نشده است.',
                ], 422);
            }
            
            $data = $request->validated();
            $file = $request->hasFile('image') ? $request->file('image') : null;
            
            $mediaItem = $this->mediaItemService->create($data, $file);
            
            return response()->json([
                'status' => true,
                'message' => $data['type'] === 'banner' ? 'بنر با موفقیت ایجاد شد.' : 'اسلایدر با موفقیت ایجاد شد.',
                'data' => $mediaItem,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در ایجاد آیتم: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * به‌روزرسانی آیتم
     *
     * @param MediaItemRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(MediaItemRequest $request, $id)
    {
        try {
            $item = $this->mediaItemService->getById($id);
            
            if (!$item) {
                return response()->json([
                    'status' => false,
                    'message' => 'آیتم مورد نظر یافت نشد.',
                ], 404);
            }
            
            $data = $request->validated();
            $file = $request->hasFile('image') ? $request->file('image') : null;
            
            $mediaItem = $this->mediaItemService->update($id, $data, $file);
            
            return response()->json([
                'status' => true,
                'message' => $mediaItem->type === 'banner' ? 'بنر با موفقیت به‌روزرسانی شد.' : 'اسلایدر با موفقیت به‌روزرسانی شد.',
                'data' => $mediaItem,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در به‌روزرسانی آیتم: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف آیتم
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $item = $this->mediaItemService->getById($id);
            
            if (!$item) {
                return response()->json([
                    'status' => false,
                    'message' => 'آیتم مورد نظر یافت نشد.',
                ], 404);
            }
            
            $type = $item->type;
            $this->mediaItemService->delete($id);
            
            return response()->json([
                'status' => true,
                'message' => $type === 'banner' ? 'بنر با موفقیت حذف شد.' : 'اسلایدر با موفقیت حذف شد.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در حذف آیتم: ' . $e->getMessage(),
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
            $item = $this->mediaItemService->getById($id);
            
            if (!$item) {
                return response()->json([
                    'status' => false,
                    'message' => 'آیتم مورد نظر یافت نشد.',
                ], 404);
            }
            
            $request->validate([
                'is_active' => 'required|boolean',
            ]);
            
            $isActive = (bool) $request->input('is_active');
            $mediaItem = $this->mediaItemService->toggleStatus($id, $isActive);
            
            $statusText = $isActive ? 'فعال' : 'غیرفعال';
            $typeText = $mediaItem->type === 'banner' ? 'بنر' : 'اسلایدر';
            
            return response()->json([
                'status' => true,
                'message' => "{$typeText} با موفقیت {$statusText} شد.",
                'data' => $mediaItem,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در تغییر وضعیت آیتم: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * دریافت اسلایدرهای فعال
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSliders(Request $request)
    {
        try {
            $position = $request->input('position', 'main_slider');
            
            $sliders = MediaItem::ofType('slider')
                ->currentlyActive()
                ->when($position, function ($query, $position) {
                    return $query->ofPosition($position);
                })
                ->ordered()
                ->get()
                ->map(function ($slider) {
                    return [
                        'id' => $slider->id,
                        'title' => $slider->title,
                        'image_url' => $slider->image_url,
                        'link' => $slider->link,
                        'order' => $slider->order,
                        'position' => $slider->position,
                    ];
                });
            
            return response()->json([
                'status' => true,
                'data' => $sliders,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در دریافت اسلایدرها: ' . $e->getMessage(),
            ], 500);
        }
    }
}
