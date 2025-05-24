<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ServiceProviderReview;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    protected $reviewService;

    /**
     * سازنده کلاس
     * 
     * @param ReviewService $reviewService
     */
    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * ثبت نظر جدید برای خدمات‌دهنده
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitReview(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,order_id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'اطلاعات وارد شده نامعتبر است',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->reviewService->submitReview(
            $user->user_id,
            $request->order_id,
            $request->rating,
            $request->comment
        );

        if (!$result['status']) {
            return response()->json($result, 400);
        }

        return response()->json($result, 201);
    }

    /**
     * دریافت نظرات تایید شده یک خدمات‌دهنده
     *
     * @param Request $request
     * @param int $serviceProviderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getServiceProviderReviews(Request $request, $serviceProviderId)
    {
        $options = [
            'per_page' => $request->get('per_page', 10),
            'sort_by' => $request->get('sort_by', 'created_at'),
            'sort_order' => $request->get('sort_order', 'desc'),
        ];

        $reviews = $this->reviewService->getProviderApprovedReviews($serviceProviderId, $options);

        return response()->json([
            'status' => true,
            'data' => $reviews,
        ]);
    }

    /**
     * دریافت لیست نظرات با امکان فیلتر (برای ادمین)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listReviews(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => false,
                'message' => 'شما دسترسی به این بخش را ندارید',
            ], 403);
        }

        $filterParams = [
            'status' => $request->get('status'),
            'provider_id' => $request->get('provider_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'rating' => $request->get('rating'),
            'sort_by' => $request->get('sort_by', 'created_at'),
            'sort_order' => $request->get('sort_order', 'desc'),
            'per_page' => $request->get('per_page', 15),
            'page' => $request->get('page', 1),
        ];

        $reviews = $this->reviewService->listReviews($filterParams);

        return response()->json([
            'status' => true,
            'data' => $reviews,
        ]);
    }

    /**
     * دریافت جزئیات یک نظر
     *
     * @param int $reviewId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReviewDetails($reviewId)
    {
        $user = Auth::user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => false,
                'message' => 'شما دسترسی به این بخش را ندارید',
            ], 403);
        }

        $result = $this->reviewService->getReviewDetails($reviewId);

        if (!$result['status']) {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }

    /**
     * تایید نظر توسط ادمین
     *
     * @param Request $request
     * @param int $reviewId
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveReview($reviewId)
    {
        $user = Auth::user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => false,
                'message' => 'شما دسترسی به این بخش را ندارید',
            ], 403);
        }

        $result = $this->reviewService->approveReview($reviewId, $user->user_id);

        if (!$result['status']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * رد نظر توسط ادمین
     *
     * @param Request $request
     * @param int $reviewId
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectReview(Request $request, $reviewId)
    {
        $user = Auth::user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => false,
                'message' => 'شما دسترسی به این بخش را ندارید',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'اطلاعات وارد شده نامعتبر است',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->reviewService->rejectReview($reviewId, $user->user_id, $request->reason);

        if (!$result['status']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * بررسی امکان ثبت نظر برای یک سفارش
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function canSubmitReview(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,order_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'اطلاعات وارد شده نامعتبر است',
                'errors' => $validator->errors(),
            ], 422);
        }

        $canSubmit = ServiceProviderReview::canSubmitReview($request->order_id, $user->user_id);

        return response()->json([
            'status' => true,
            'can_submit' => $canSubmit,
        ]);
    }
} 