<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade as PDF;
use App\Models\Service;
use App\Models\ServiceProviderService;

class ExportController extends Controller
{
    /**
     * Export service providers to Excel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function exportToExcel(Request $request)
    {
        $query = ServiceProvider::with('documents');

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

        // Get data
        $serviceProviders = $query->get();

        // Create a new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'Email');
        $sheet->setCellValue('D1', 'Phone');
        $sheet->setCellValue('E1', 'Type');
        $sheet->setCellValue('F1', 'Status');
        $sheet->setCellValue('G1', 'Registration Date');
        $sheet->setCellValue('H1', 'Last Activity');
        $sheet->setCellValue('I1', 'Documents');

        // Add data rows
        $row = 2;
        foreach ($serviceProviders as $provider) {
            $sheet->setCellValue('A' . $row, $provider->id);
            $sheet->setCellValue('B' . $row, $provider->name);
            $sheet->setCellValue('C' . $row, $provider->email);
            $sheet->setCellValue('D' . $row, $provider->phone);
            $sheet->setCellValue('E' . $row, $provider->type);
            $sheet->setCellValue('F' . $row, $provider->status);
            $sheet->setCellValue('G' . $row, $provider->created_at->format('Y-m-d H:i:s'));
            $sheet->setCellValue('H' . $row, $provider->last_activity_at ? $provider->last_activity_at->format('Y-m-d H:i:s') : 'N/A');

            // Add document count
            $sheet->setCellValue('I' . $row, $provider->documents->count());

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create Excel file
        $writer = new Xlsx($spreadsheet);
        $filename = 'services-providers-report-' . date('Y-m-d-His') . '.xlsx';
        $filepath = storage_path('app/public/exports/' . $filename);

        // Create directory if it doesn't exist
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        // Save the file
        $writer->save($filepath);

        // Return the download response
        return response()->download($filepath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * دانلود گزارش ثبت نام خدمات‌دهندگان
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportRegistration(Request $request)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }
        
        // محدوده زمانی
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->subMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();
        
        // فیلتر وضعیت
        $status = $request->input('status', 'all');
        
        // فیلتر دسته‌بندی
        $category = $request->input('category', 'all');
        
        // ساخت کوئری
        $query = ServiceProvider::query()
            ->with(['documents'])
            ->whereBetween('created_at', [$startDate, $endDate]);
            
        // اعمال فیلترها
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($category !== 'all') {
            $query->where('category', $category);
        }
        
        $serviceProviders = $query->get();
        
        // ایجاد فایل اکسل
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('گزارش ثبت نام');
        
        // تنظیم هدر فارسی
        $sheet->setCellValue('A1', 'شناسه');
        $sheet->setCellValue('B1', 'نام');
        $sheet->setCellValue('C1', 'ایمیل');
        $sheet->setCellValue('D1', 'تلفن');
        $sheet->setCellValue('E1', 'کد ملی');
        $sheet->setCellValue('F1', 'شماره جواز');
        $sheet->setCellValue('G1', 'دسته‌بندی');
        $sheet->setCellValue('H1', 'وضعیت');
        $sheet->setCellValue('I1', 'تعداد مدارک');
        $sheet->setCellValue('J1', 'تاریخ ثبت نام');
        $sheet->setCellValue('K1', 'آخرین فعالیت');

        // پر کردن داده‌ها
        $row = 2;
        foreach ($serviceProviders as $provider) {
            $sheet->setCellValue('A' . $row, $provider->id);
            $sheet->setCellValue('B' . $row, $provider->name);
            $sheet->setCellValue('C' . $row, $provider->email);
            $sheet->setCellValue('D' . $row, $provider->phone);
            $sheet->setCellValue('E' . $row, $provider->national_code);
            $sheet->setCellValue('F' . $row, $provider->business_license);
            $sheet->setCellValue('G' . $row, $provider->category == 'commercial' ? 'تجاری' : 'کانکت یار');
            $sheet->setCellValue('H' . $row, $this->translateStatus($provider->status));
            $sheet->setCellValue('I' . $row, $provider->documents->count());
            $sheet->setCellValue('J' . $row, $provider->created_at->format('Y-m-d H:i:s'));
            $sheet->setCellValue('K' . $row, $provider->last_activity_at ? Carbon::parse($provider->last_activity_at)->format('Y-m-d H:i:s') : '-');

            $row++;
        }
        
        // تنظیم عرض ستون‌ها
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // ایجاد فایل اکسل
        $writer = new Xlsx($spreadsheet);
        $filename = 'service-provider-registrations-' . date('Y-m-d-His') . '.xlsx';
        $filepath = storage_path('app/public/exports/' . $filename);
        
        // ایجاد پوشه اگر وجود ندارد
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        // ذخیره فایل
        $writer->save($filepath);
        
        // دانلود فایل
        return response()->download($filepath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
    
    /**
     * ترجمه وضعیت به فارسی
     * 
     * @param string $status
     * @return string
     */
    private function translateStatus($status)
    {
        switch ($status) {
            case 'pending':
                return 'در انتظار بررسی';
            case 'approved':
                return 'تایید شده';
            case 'rejected':
                return 'رد شده';
            default:
                return $status;
        }
    }

    /**
     * Export services to Excel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function exportServices(Request $request)
    {
        // Check admin access
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }
        
        $query = Service::query()->with('category');
        
        // Apply status filter
        if ($request->filled('filter') && in_array($request->filter, ['active', 'inactive'])) {
            $query->where('status', $request->filter);
        }
        
        // Apply category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        $services = $query->get();
        
        // Create Excel file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('گزارش خدمات');
        
        // Set headers
        $sheet->setCellValue('A1', 'شناسه');
        $sheet->setCellValue('B1', 'نام خدمت');
        $sheet->setCellValue('C1', 'دسته‌بندی');
        $sheet->setCellValue('D1', 'نوع ارائه');
        $sheet->setCellValue('E1', 'قیمت (تومان)');
        $sheet->setCellValue('F1', 'وضعیت');
        $sheet->setCellValue('G1', 'تعداد ارائه‌دهندگان');
        $sheet->setCellValue('H1', 'تاریخ ایجاد');
        
        // Add data rows
        $row = 2;
        foreach ($services as $service) {
            $sheet->setCellValue('A' . $row, $service->service_id);
            $sheet->setCellValue('B' . $row, $service->name);
            $sheet->setCellValue('C' . $row, $service->category ? $service->category->name : '-');
            $sheet->setCellValue('D' . $row, $service->delivery_type == 'single' ? 'تکی' : 'چندتایی');
            $sheet->setCellValue('E' . $row, number_format($service->price));
            $sheet->setCellValue('F' . $row, $service->status == 'active' ? 'فعال' : 'غیرفعال');
            
            // Count service providers
            $providerCount = ServiceProviderService::where('service_id', $service->service_id)
                ->where('is_active', true)
                ->count();
            
            $sheet->setCellValue('G' . $row, $providerCount);
            $sheet->setCellValue('H' . $row, $service->created_at->format('Y-m-d H:i:s'));
            
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Create Excel file
        $writer = new Xlsx($spreadsheet);
        $filename = 'services-report-' . date('Y-m-d-His') . '.xlsx';
        $filepath = storage_path('app/public/exports/' . $filename);
        
        // Create directory if it doesn't exist
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        // Save the file
        $writer->save($filepath);
        
        // Return the download response
        return response()->download($filepath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
