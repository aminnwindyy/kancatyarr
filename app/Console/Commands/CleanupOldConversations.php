<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\API\OrderController;

class CleanupOldConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cleanup-conversations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'پاکسازی گفتگوهای سفارشات تکمیل‌شده که بیش از 15 روز از آنها گذشته است';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('شروع پاکسازی گفتگوهای قدیمی...');

        $orderController = app(OrderController::class);
        $orderController->cleanupOldConversations();

        $this->info('پاکسازی گفتگوهای قدیمی با موفقیت انجام شد.');

        return Command::SUCCESS;
    }
}
