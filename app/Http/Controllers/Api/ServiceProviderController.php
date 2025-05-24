<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceProviderStoreRequest;
use App\Http\Requests\ServiceProviderUpdateRequest;
use App\Http\Requests\ServiceProviderStatusUpdateRequest;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderStatusHistory;
use App\Models\ServiceProviderDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Notifications\NewServiceProviderRegistered;

class ServiceProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $query = ServiceProvider::with(['documents', 'user']);

        // Apply filters
        if ($request->filled('filter')) {
            switch ($request->filter) {
                case 'active':
                    $query->filterByStatus('active');
                    break;
                case 'inactive':
                    $query->filterByStatus('inactive');
                    break;
                case 'pending':
                    $query->filterByStatus('pending');
                    break;
                case 'rejected':
                    $query->filterByStatus('rejected');
                    break;
                case 'today':
                    $query->createdToday();
                    break;
            }
        }

        // Apply search
        if ($request->filled('query')) {
            $query->search($request->query);
        }

        // Apply sorting
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $serviceProviders = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $serviceProviders,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ServiceProviderStoreRequest $request)
    {
        // Validated data
        $validated = $request->validated();

        // Create the service provider
        $serviceProvider = new ServiceProvider();
        $serviceProvider->name = $validated['name'];
        $serviceProvider->email = $validated['email'];
        $serviceProvider->phone = $validated['phone'] ?? null;
        $serviceProvider->type = $validated['type'];
        $serviceProvider->address = $validated['address'] ?? null;
        $serviceProvider->description = $validated['description'] ?? null;
        $serviceProvider->website = $validated['website'] ?? null;
        $serviceProvider->status = 'pending';
        $serviceProvider->last_activity_at = now();
        $serviceProvider->save();

        // Upload and store documents
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $fileName = time() . '_' . Str::slug($document->getClientOriginalName());
                $filePath = $document->storeAs(
                    'service_provider_documents/' . $serviceProvider->id,
                    $fileName,
                    'public'
                );

                ServiceProviderDocument::create([
                    'service_provider_id' => $serviceProvider->id,
                    'name' => $document->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_type' => $document->getClientMimeType(),
                    'status' => 'pending',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Service provider created successfully',
            'data' => $serviceProvider->load('documents'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $serviceProvider = ServiceProvider::with(['documents', 'user', 'statusHistories.admin'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $serviceProvider,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ServiceProviderUpdateRequest $request, string $id)
    {
        $serviceProvider = ServiceProvider::findOrFail($id);

        // Validated data
        $validated = $request->validated();

        // Update basic info
        $fieldsToUpdate = array_intersect_key($validated, array_flip([
            'name',
            'email',
            'phone',
            'type',
            'address',
            'description',
            'website',
        ]));

        $serviceProvider->update($fieldsToUpdate);
        $serviceProvider->updateLastActivity();

        // Upload and store new documents if any
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $fileName = time() . '_' . Str::slug($document->getClientOriginalName());
                $filePath = $document->storeAs(
                    'service_provider_documents/' . $serviceProvider->id,
                    $fileName,
                    'public'
                );

                ServiceProviderDocument::create([
                    'service_provider_id' => $serviceProvider->id,
                    'name' => $document->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_type' => $document->getClientMimeType(),
                    'status' => 'pending',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Service provider updated successfully',
            'data' => $serviceProvider->fresh()->load('documents'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $serviceProvider = ServiceProvider::findOrFail($id);

        // Delete all documents
        foreach ($serviceProvider->documents as $document) {
            Storage::disk('public')->delete($document->file_path);
        }

        // Delete the service provider (soft delete)
        $serviceProvider->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service provider deleted successfully',
        ]);
    }

    /**
     * Update the status of the service provider.
     */
    public function updateStatus(ServiceProviderStatusUpdateRequest $request, string $id)
    {
        $serviceProvider = ServiceProvider::findOrFail($id);
        $previousStatus = $serviceProvider->status;
        $newStatus = $request->status;

        // No change, return early
        if ($previousStatus === $newStatus) {
            return response()->json([
                'success' => true,
                'message' => 'Status remains unchanged',
                'data' => $serviceProvider,
            ]);
        }

        // Update the status
        $serviceProvider->status = $newStatus;
        $serviceProvider->admin_id = Auth::id();
        $serviceProvider->save();

        // Record the status change
        ServiceProviderStatusHistory::create([
            'service_provider_id' => $serviceProvider->id,
            'admin_id' => Auth::id(),
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'description' => $request->description,
        ]);

        // Update the last activity
        $serviceProvider->updateLastActivity();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => $serviceProvider->fresh()->load('documents'),
        ]);
    }

    /**
     * Download a document.
     */
    public function downloadDocument(string $id)
    {
        $document = ServiceProviderDocument::findOrFail($id);

        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return response()->download(
            Storage::disk('public')->path($document->file_path),
            $document->name
        );
    }

    /**
     * Delete a document.
     */
    public function deleteDocument(string $id)
    {
        $document = ServiceProviderDocument::findOrFail($id);

        // Delete the file
        Storage::disk('public')->delete($document->file_path);

        // Delete the record
        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully',
        ]);
    }

    /**
     * ثبت نام خدمات‌دهندگان - متد مخصوص API
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // اعتبارسنجی ورودی‌ها
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:service_providers,email',
            'phone' => 'required|string|max:20',
            'national_code' => 'nullable|string|max:10',
            'business_license' => 'nullable|string|max:100',
            'category' => 'required|in:commercial,connectyar',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'website' => 'nullable|url',
            'password' => 'required|string|min:8',
            'documents' => 'array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی ورودی‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        // ایجاد کاربر جدید
        $user = User::create([
            'first_name' => explode(' ', $request->name)[0] ?? $request->name,
            'last_name' => count(explode(' ', $request->name)) > 1 ? explode(' ', $request->name, 2)[1] : '',
            'email' => $request->email,
            'phone_number' => $request->phone,
            'password' => Hash::make($request->password),
            'registration_date' => now(),
            'is_active' => true,
            'is_admin' => false,
        ]);

        // اختصاص نقش خدمات‌دهنده به کاربر
        $user->assignRole('service_provider');

        // ایجاد خدمات‌دهنده جدید
        $serviceProvider = new ServiceProvider();
        $serviceProvider->user_id = $user->user_id;
        $serviceProvider->name = $request->name;
        $serviceProvider->email = $request->email;
        $serviceProvider->phone = $request->phone;
        $serviceProvider->national_code = $request->national_code;
        $serviceProvider->business_license = $request->business_license;
        $serviceProvider->category = $request->category;
        $serviceProvider->address = $request->address;
        $serviceProvider->description = $request->description;
        $serviceProvider->website = $request->website;
        $serviceProvider->status = 'pending';
        $serviceProvider->last_activity_at = now();
        $serviceProvider->save();

        // آپلود و ذخیره مدارک
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $index => $document) {
                $documentType = $request->input('document_types.' . $index, 'national_card');
                $fileName = time() . '_' . Str::slug($document->getClientOriginalName());
                $filePath = $document->storeAs(
                    'service_provider_documents/' . $serviceProvider->id,
                    $fileName,
                    'public'
                );

                ServiceProviderDocument::create([
                    'service_provider_id' => $serviceProvider->id,
                    'document_type' => $documentType,
                    'file_path' => $filePath,
                    'status' => 'pending',
                ]);
            }
        }

        // اطلاع‌رسانی به ادمین‌ها درباره ثبت نام جدید
        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new NewServiceProviderRegistered($serviceProvider));
        }

        return response()->json([
            'success' => true,
            'message' => 'حساب با موفقیت ایجاد شد. منتظر تایید مدارک خود باشید.',
            'data' => [
                'service_provider' => $serviceProvider->load('documents'),
                'user' => $user
            ],
        ], 201);
    }

    /**
     * دریافت لیست خدمات‌دهندگان با فیلتر
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilteredProviders(Request $request)
    {
        // بررسی دسترسی
        if (!$request->user()->can('service_providers.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }
        
        // دریافت پارامترهای فیلتر
        $type = $request->input('type'); // نوع خدمات‌دهنده (business/connectyar)
        $city = $request->input('city'); // شهر
        $categoryId = $request->input('category_id'); // دسته‌بندی
        $limit = $request->input('limit', 10);
        
        // ایجاد کوئری
        $query = ServiceProvider::query()
            ->where('status', 'active');
            
        // اعمال فیلترها
        if ($type) {
            $query->where('type', $type);
        }
        
        if ($city) {
            $query->where('city', $city);
        }
        
        if ($categoryId) {
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }
        
        // دریافت نتایج با اطلاعات مورد نیاز
        $providers = $query->select('id', 'name', 'type', 'city', 'profile_image')
            ->orderBy('rating', 'desc')
            ->paginate($limit);
            
        return response()->json([
            'data' => $providers->items(),
            'total_pages' => $providers->lastPage(),
            'current_page' => $providers->currentPage()
        ]);
    }
}
