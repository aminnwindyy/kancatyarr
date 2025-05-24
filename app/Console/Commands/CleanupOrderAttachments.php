<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderMessage;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupOrderAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cleanup-attachments {--days=30 : Number of days to keep attachments} {--dry-run : Run without actually deleting files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old attachments from completed/cancelled orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("Cleaning up attachments older than {$days} days...");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE: No files will be deleted.");
        }
        
        // یافتن سفارشات تکمیل شده یا لغو شده قدیمی
        $oldOrders = Order::whereIn('status', ['completed', 'cancelled', 'rejected'])
            ->where('updated_at', '<', Carbon::now()->subDays($days))
            ->get();
            
        $this->info("Found {$oldOrders->count()} old orders to process.");
        
        $totalFilesRemoved = 0;
        $totalSpaceFreed = 0;
        
        foreach ($oldOrders as $order) {
            $this->info("Processing order #{$order->order_id}...");
            
            // یافتن پیام‌های دارای فایل پیوست
            $messagesWithAttachments = OrderMessage::where('order_id', $order->order_id)
                ->whereNotNull('file_path')
                ->get();
            
            $this->info("  Found {$messagesWithAttachments->count()} messages with attachments.");
            
            foreach ($messagesWithAttachments as $message) {
                if (Storage::disk('public')->exists($message->file_path)) {
                    $fileSize = Storage::disk('public')->size($message->file_path);
                    
                    $this->info("  - Attachment: {$message->file_name} ({$this->formatSize($fileSize)})");
                    
                    if (!$dryRun) {
                        // حذف فایل
                        Storage::disk('public')->delete($message->file_path);
                        
                        // به‌روزرسانی رکورد پیام
                        $message->file_path = null;
                        $message->file_name = null;
                        $message->file_type = null;
                        $message->save();
                        
                        $this->info("    Deleted successfully.");
                    }
                    
                    $totalFilesRemoved++;
                    $totalSpaceFreed += $fileSize;
                }
            }
        }
        
        // ثبت لاگ عملیات پاکسازی
        \Log::channel('orders')->info('Attachments cleanup completed', [
            'old_orders_count' => $oldOrders->count(),
            'total_files_removed' => $totalFilesRemoved,
            'total_space_freed' => $totalSpaceFreed,
            'days_threshold' => $days,
            'dry_run' => $dryRun,
        ]);
        
        $this->info("Cleanup completed! Removed {$totalFilesRemoved} files, freed {$this->formatSize($totalSpaceFreed)} of space.");
    }
    
    /**
     * فرمت‌دهی حجم فایل به صورت خوانا
     *
     * @param int $size سایز به بایت
     * @return string
     */
    private function formatSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
} 