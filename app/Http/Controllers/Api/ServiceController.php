<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    /**
     * Check if the authenticated user has admin role.
     *
     * @return \Illuminate\Http\Response|null
     */
    private function checkAdminAccess()
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }
        
        return null;
    }

    /**
     * Display a listing of the services.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Check admin access
        $checkAccess = $this->checkAdminAccess();
        if ($checkAccess) {
            return $checkAccess;
        }
        
        $query = Service::query();

        // Filter by status if provided
        if ($request->has('filter') && in_array($request->filter, ['active', 'inactive'])) {
            $query->where('status', $request->filter);
        }

        // Paginate the results
        $limit = $request->input('limit', 10);
        $services = $query->paginate($limit);

        return response()->json([
            'data' => $services->items(),
            'total_pages' => $services->lastPage(),
            'current_page' => $services->currentPage(),
        ]);
    }

    /**
     * Store a newly created service in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check admin access
        $checkAccess = $this->checkAdminAccess();
        if ($checkAccess) {
            return $checkAccess;
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'delivery_type' => ['required', Rule::in(['single', 'multiple'])],
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,category_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service = Service::create([
            'name' => $request->name,
            'description' => $request->description,
            'delivery_type' => $request->delivery_type,
            'price' => $request->price,
            'status' => 'active',
            'category_id' => $request->category_id,
        ]);

        return response()->json([
            'message' => 'خدمت با موفقیت ایجاد شد.',
            'service_id' => $service->service_id
        ], 201);
    }

    /**
     * Update the specified service in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $serviceId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $serviceId)
    {
        // Check admin access
        $checkAccess = $this->checkAdminAccess();
        if ($checkAccess) {
            return $checkAccess;
        }
        
        $service = Service::findOrFail($serviceId);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'delivery_type' => ['sometimes', 'required', Rule::in(['single', 'multiple'])],
            'price' => 'sometimes|required|numeric|min:0',
            'category_id' => 'sometimes|required|exists:categories,category_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service->update($request->only([
            'name', 'description', 'delivery_type', 'price', 'category_id'
        ]));

        return response()->json([
            'message' => 'خدمت با موفقیت بروزرسانی شد.'
        ]);
    }

    /**
     * Toggle the status of the specified service.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $serviceId
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus(Request $request, $serviceId)
    {
        // Check admin access
        $checkAccess = $this->checkAdminAccess();
        if ($checkAccess) {
            return $checkAccess;
        }
        
        $service = Service::findOrFail($serviceId);

        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service->update([
            'status' => $request->status
        ]);

        // If the service is deactivated, notify the service providers
        if ($request->status === 'inactive') {
            // Retrieve all service providers using this service
            $serviceProviders = ServiceProviderService::where('service_id', $serviceId)
                ->where('is_active', true)
                ->with('serviceProvider')
                ->get();
                
            // Here you would implement notification logic
            // For example, send emails or push notifications to each service provider
            // This could be implemented using Laravel Notifications or a custom notification service
        }

        return response()->json([
            'message' => 'وضعیت خدمات با موفقیت تغییر کرد.'
        ]);
    }

    /**
     * Get active service providers for a specific service.
     *
     * @param  int  $serviceId
     * @return \Illuminate\Http\Response
     */
    public function getActiveServiceProviders($serviceId)
    {
        // Check admin access
        $checkAccess = $this->checkAdminAccess();
        if ($checkAccess) {
            return $checkAccess;
        }
        
        $service = Service::findOrFail($serviceId);

        $serviceProviders = ServiceProviderService::where('service_id', $serviceId)
            ->with('serviceProvider')
            ->get()
            ->map(function ($item) {
                return [
                    'service_provider_id' => $item->serviceProvider->service_provider_id,
                    'service_provider_name' => $item->serviceProvider->name,
                    'is_active' => $item->is_active,
                ];
            });

        return response()->json([
            'data' => $serviceProviders
        ]);
    }
} 