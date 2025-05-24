<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Exports\DocumentsExport;
use App\Events\DocumentStatusChanged;
use App\Notifications\DocumentRejected;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceProviderDocumentsController extends Controller
{
    /**
     * نمایش لیست مدارک یک خدمات‌دهنده
     *
     * @param int $id شناسه خدمات‌دهنده
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(int $id)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }
        
        // یافتن خدمات‌دهنده
        $serviceProvider = ServiceProvider::with('documents')->findOrFail($id);
        
        $documents = $serviceProvider->documents->map(function ($document) {
            return [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'file_path' => $document->file_path,
                'status' => $document->status,
                'description' => $document->description,
                'created_at' => Carbon::parse($document->created_at)->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::parse($document->updated_at)->format('Y-m-d H:i:s'),
                'download_url' => url('/api/service-providers/' . $document->service_provider_id . '/documents/' . $document->id . '/download'),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'service_provider' => [
                    'id' => $serviceProvider->id,
                    'name' => $serviceProvider->name,
                    'email' => $serviceProvider->email,
                    'phone' => $serviceProvider->phone,
                    'national_code' => $serviceProvider->national_code,
                    'business_license' => $serviceProvider->business_license,
                    'status' => $serviceProvider->status,
                ],
                'documents' => $documents
            ]
        ]);
    }
    
    /**
     * تغییر وضعیت یک مدرک (تایید یا رد)
     *
     * @param Request $request
     * @param int $id شناسه خدمات‌دهنده
     * @param int $documentId شناسه مدرک
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, int $id, int $documentId)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }
        
        // اعتبارسنجی ورودی‌ها
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'description' => 'nullable|string|max:500',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی ورودی‌ها',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // یافتن مدرک
        $document = ServiceProviderDocument::where('service_provider_id', $id)
            ->where('id', $documentId)
            ->firstOrFail();
        
        // بروزرسانی وضعیت
        $oldStatus = $document->status;
        $document->status = $request->status;
        $document->description = $request->description ?? '';
        $document->updated_by = Auth::id();
        $document->save();
        
        // اگر مدرک رد شده، به خدمات‌دهنده اطلاع بده
        if ($request->status === 'rejected' && $oldStatus !== 'rejected') {
            $serviceProvider = ServiceProvider::findOrFail($id);
            $serviceProvider->notify(new DocumentRejected($document));
        }
        
        // بررسی اگر تمام مدارک تایید شده، وضعیت خدمات‌دهنده را فعال کن
        $this->updateServiceProviderStatus($id);
        
        // رویداد تغییر وضعیت مدرک را منتشر کن
        event(new DocumentStatusChanged($document));
        
        return response()->json([
            'success' => true,
            'message' => $request->status === 'approved' 
                ? 'مدرک با موفقیت تایید شد.' 
                : 'مدرک رد شد و به کاربر اطلاع داده شد.'
        ]);
    }
    
    /**
     * دانلود فایل مدرک
     *
     * @param int $id شناسه خدمات‌دهنده
     * @param int $documentId شناسه مدرک
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(int $id, int $documentId)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }
        
        // یافتن مدرک
        $document = ServiceProviderDocument::where('service_provider_id', $id)
            ->where('id', $documentId)
            ->firstOrFail();
        
        // بررسی وجود فایل
        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'فایل مورد نظر یافت نشد.'
            ], 404);
        }
        
        // دانلود فایل
        return Storage::disk('public')->download($document->file_path);
    }
    
    /**
     * لیست خدمات‌دهندگان بر اساس وضعیت مدارک
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterProviders(Request $request)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }
        
        // پارامترهای ورودی
        $filter = $request->input('filter', 'pending');
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        
        // کوئری پایه
        $query = ServiceProvider::query();
        
        // اعمال فیلتر وضعیت
        if ($filter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($filter === 'approved') {
            $query->where('status', 'approved');
        } elseif ($filter === 'rejected') {
            $query->where('status', 'rejected');
        }
        
        // اعمال فیلتر جستجو
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('national_code', 'like', "%{$search}%");
            });
        }
        
        // تعداد مدارک تایید شده و رد شده با JOIN
        $query->leftJoin('service_provider_documents as approved_docs', function($join) {
            $join->on('service_providers.id', '=', 'approved_docs.service_provider_id')
                 ->where('approved_docs.status', '=', 'approved');
        })
        ->leftJoin('service_provider_documents as rejected_docs', function($join) {
            $join->on('service_providers.id', '=', 'rejected_docs.service_provider_id')
                 ->where('rejected_docs.status', '=', 'rejected');
        })
        ->select([
            'service_providers.*',
            DB::raw('COUNT(DISTINCT approved_docs.id) as approved_documents_count'),
            DB::raw('COUNT(DISTINCT rejected_docs.id) as rejected_documents_count'),
        ])
        ->groupBy('service_providers.id');
        
        // صفحه‌بندی نتایج
        $serviceProviders = $query->paginate($limit);
        
        return response()->json([
            'success' => true,
            'data' => $serviceProviders->items(),
            'total_pages' => $serviceProviders->lastPage(),
            'current_page' => $serviceProviders->currentPage(),
            'total' => $serviceProviders->total()
        ]);
    }
    
    /**
     * دانلود گزارش مدارک به صورت Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function exportDocuments(Request $request)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }
        
        try {
            // پارامترهای فیلتر
            $status = $request->input('status');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            
            // کوئری پایه
            $query = ServiceProviderDocument::query()
                ->join('service_providers', 'service_providers.id', '=', 'service_provider_documents.service_provider_id')
                ->select([
                    'service_provider_documents.*',
                    'service_providers.name as provider_name',
                    'service_providers.email as provider_email',
                    'service_providers.phone as provider_phone',
                ]);
                
            // اعمال فیلترها
            if ($status) {
                $query->where('service_provider_documents.status', $status);
            }
            
            if ($fromDate) {
                $query->whereDate('service_provider_documents.created_at', '>=', $fromDate);
            }
            
            if ($toDate) {
                $query->whereDate('service_provider_documents.created_at', '<=', $toDate);
            }
            
            // دریافت نتایج
            $documents = $query->get();
            
            // تولید فایل Excel
            $fileName = 'documents-report-' . date('Y-m-d') . '.xlsx';
            Excel::store(new DocumentsExport($documents), 'public/exports/' . $fileName);
            
            return response()->json([
                'success' => true,
                'message' => 'گزارش با موفقیت تولید شد.',
                'download_url' => url('/storage/exports/' . $fileName)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در تولید گزارش: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * آپلود مدرک جدید یا بروزرسانی مدرک موجود
     *
     * @param Request $request
     * @param int $id شناسه خدمات‌دهنده
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDocument(Request $request, int $id)
    {
        // اعتبارسنجی ورودی‌ها
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|in:national_card,business_license,photo',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // حداکثر 5MB
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی ورودی‌ها',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // بررسی وجود خدمات‌دهنده
        $serviceProvider = ServiceProvider::findOrFail($id);
        
        // آپلود فایل
        $file = $request->file('file');
        $fileName = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
        $filePath = $file->storeAs('service_provider_documents/' . $id, $fileName, 'public');
        
        // ذخیره یا بروزرسانی رکورد مدرک
        $document = ServiceProviderDocument::updateOrCreate(
            [
                'service_provider_id' => $id,
                'document_type' => $request->document_type,
            ],
            [
                'file_path' => $filePath,
                'status' => 'pending',
                'description' => '',
            ]
        );
        
        // بروزرسانی وضعیت خدمات‌دهنده به pending
        $serviceProvider->status = 'pending';
        $serviceProvider->save();
        
        return response()->json([
            'success' => true,
            'message' => 'مدرک با موفقیت آپلود شد.',
            'document' => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'file_path' => $document->file_path,
                'status' => $document->status,
                'created_at' => $document->created_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }
    
    /**
     * بروزرسانی وضعیت خدمات‌دهنده بر اساس وضعیت مدارک
     *
     * @param int $serviceProviderId شناسه خدمات‌دهنده
     * @return void
     */
    private function updateServiceProviderStatus(int $serviceProviderId)
    {
        // دریافت خدمات‌دهنده و تمامی مدارک او
        $serviceProvider = ServiceProvider::findOrFail($serviceProviderId);
        $documents = ServiceProviderDocument::where('service_provider_id', $serviceProviderId)->get();
        
        // اگر مدرکی وجود نداشت
        if ($documents->isEmpty()) {
            return;
        }
        
        // شمارش تعداد مدارک رد شده و تایید شده
        $rejectedCount = $documents->where('status', 'rejected')->count();
        $approvedCount = $documents->where('status', 'approved')->count();
        $totalCount = $documents->count();
        
        // بروزرسانی وضعیت خدمات‌دهنده
        if ($rejectedCount > 0) {
            // اگر حتی یک مدرک رد شده باشد، وضعیت خدمات‌دهنده rejected
            $serviceProvider->status = 'rejected';
        } elseif ($approvedCount === $totalCount) {
            // اگر تمام مدارک تایید شده باشند، وضعیت خدمات‌دهنده approved
            $serviceProvider->status = 'approved';
        } else {
            // در غیر این صورت وضعیت همچنان pending
            $serviceProvider->status = 'pending';
        }
        
        $serviceProvider->save();
    }
}
