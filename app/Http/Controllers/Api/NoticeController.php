<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoticeRequest;
use App\Models\Notice;
use App\Services\NoticeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class NoticeController extends Controller
{
    /**
     * سرویس مدیریت اطلاعیه‌ها
     *
     * @var NoticeService
     */
    protected $noticeService;
    
    /**
     * سازنده کلاس
     *
     * @param NoticeService $noticeService
     */
    public function __construct(NoticeService $noticeService)
    {
        $this->noticeService = $noticeService;
        
        // فقط ادمین‌ها یا کاربران با نقش "مدیر محتوا" به تمام API ها دسترسی دارند
        // کاربران عادی فقط می‌توانند اطلاعیه‌های منتشر شده را ببینند
        $this->middleware(['auth:sanctum', 'admin'])->except(['index', 'show']);
    }
    
    /**
     * نمایش لیست اطلاعیه‌ها
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $forAdmin = $request->has('forAdmin') && $request->input('forAdmin') === 'true' && 
                  (Auth::check() && (Auth::user()->isAdmin() || Auth::user()->hasLocalRole('content_manager')));
        
        // اگر برای کاربر عادی است و در کش موجود است، از کش برگشت دهیم
        if (!$forAdmin && !$request->has('user_id') && Cache::has('published_notices')) {
            $notices = Cache::get('published_notices');
        } else {
            // فیلتر ها
            $filters = [
                'type' => $request->input('type'),
                'status' => $request->input('status'),
                'sort_by' => $request->input('sort_by', 'publish_at'),
                'sort_order' => $request->input('sort_order', 'desc'),
            ];
            
            // اگر کاربر وارد شده، اطلاعیه‌های مخصوص او را هم نمایش دهیم
            if (Auth::check() && !$forAdmin) {
                $filters['user_id'] = Auth::id();
            }
            
            $notices = $this->noticeService->listNotices($filters, $forAdmin, $request->input('per_page', 15));
            
            // اگر برای کاربر عادی است، نتیجه را در کش ذخیره کنیم
            if (!$forAdmin && !$request->has('user_id')) {
                Cache::put('published_notices', $notices, now()->addHours(1));
            }
        }
        
        return response()->json([
            'status' => true,
            'data' => $notices,
        ]);
    }
    
    /**
     * ذخیره یک اطلاعیه جدید
     *
     * @param NoticeRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(NoticeRequest $request)
    {
        $data = $request->validated();
        
        $notice = $this->noticeService->createNotice($data);
        
        if (!$notice) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در ایجاد اطلاعیه.',
            ], 500);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'اطلاعیه با موفقیت ایجاد شد.',
            'data' => $notice,
        ], 201);
    }
    
    /**
     * نمایش یک اطلاعیه
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $forAdmin = $request->has('forAdmin') && $request->input('forAdmin') === 'true' && 
                  (Auth::check() && (Auth::user()->isAdmin() || Auth::user()->hasLocalRole('content_manager')));
        
        $userId = Auth::check() ? Auth::id() : null;
        
        $notice = $this->noticeService->getNotice($id, $forAdmin, $userId);
        
        if (!$notice) {
            return response()->json([
                'status' => false,
                'message' => 'اطلاعیه مورد نظر یافت نشد.',
            ], 404);
        }
        
        return response()->json([
            'status' => true,
            'data' => $notice,
        ]);
    }
    
    /**
     * به‌روزرسانی یک اطلاعیه
     *
     * @param NoticeRequest $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(NoticeRequest $request, $id)
    {
        $data = $request->validated();
        
        $notice = $this->noticeService->updateNotice($id, $data);
        
        if (!$notice) {
            return response()->json([
                'status' => false,
                'message' => 'اطلاعیه مورد نظر یافت نشد یا به‌روزرسانی با خطا مواجه شد.',
            ], 404);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'اطلاعیه با موفقیت به‌روزرسانی شد.',
            'data' => $notice,
        ]);
    }
    
    /**
     * انتشار یک اطلاعیه
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function publish(Request $request, $id)
    {
        $request->validate([
            'publish_at' => 'nullable|date|after_or_equal:now',
        ], [
            'publish_at.date' => 'فرمت زمان انتشار نامعتبر است.',
            'publish_at.after_or_equal' => 'زمان انتشار نمی‌تواند قبل از زمان فعلی باشد.',
        ]);
        
        $publishAt = $request->has('publish_at') ? Carbon::parse($request->input('publish_at')) : null;
        
        $notice = $this->noticeService->publishNotice($id, $publishAt);
        
        if (!$notice) {
            return response()->json([
                'status' => false,
                'message' => 'اطلاعیه مورد نظر یافت نشد.',
            ], 404);
        }
        
        return response()->json([
            'status' => true,
            'message' => $publishAt ? 'اطلاعیه برای انتشار در تاریخ ' . $publishAt->format('Y-m-d H:i') . ' زمان‌بندی شد.' : 'اطلاعیه با موفقیت منتشر شد.',
            'data' => $notice,
        ]);
    }
    
    /**
     * آرشیو کردن یک اطلاعیه
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function archive($id)
    {
        $notice = $this->noticeService->archiveNotice($id);
        
        if (!$notice) {
            return response()->json([
                'status' => false,
                'message' => 'اطلاعیه مورد نظر یافت نشد.',
            ], 404);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'اطلاعیه با موفقیت آرشیو شد.',
            'data' => $notice,
        ]);
    }
    
    /**
     * حذف یک اطلاعیه
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $result = $this->noticeService->deleteNotice($id);
        
        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => 'اطلاعیه مورد نظر یافت نشد یا حذف با خطا مواجه شد.',
            ], 404);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'اطلاعیه با موفقیت حذف شد.',
        ]);
    }
    
    /**
     * دریافت تعداد اطلاعیه‌های خوانده نشده برای کاربر
     *
     * @return \Illuminate\Http\Response
     */
    public function getUnreadCount()
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'کاربر وارد نشده است.',
            ], 401);
        }
        
        $count = $this->noticeService->getUnreadCount(Auth::id());
        
        return response()->json([
            'status' => true,
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }
}
