<?php

namespace App\Console\Commands;

use App\Jobs\CartCleanupJob;
use App\Models\CartItem;
use App\Models\CartStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * CleanExpiredCarts Command
 * 
 * Manually clean up expired cart items.
 * 
 * Usage:
 *   php artisan cart:cleanup           # Default 30 minutes expiration
 *   php artisan cart:cleanup --minutes=60   # Custom expiration time
 *   php artisan cart:cleanup --dry-run      # Preview what would be deleted
 *   php artisan cart:cleanup --sync         # Run synchronously (not queued)
 */
class CleanExpiredCarts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cart:cleanup 
                            {--minutes=30 : Cart expiration time in minutes}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--sync : Run synchronously instead of dispatching to queue}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up expired cart items and release blocked PV';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $dryRun = $this->option('dry-run');
        $sync = $this->option('sync');

        $this->info("Cart Cleanup - Expiration: {$minutes} minutes");
        $this->newLine();

        if ($dryRun) {
            return $this->performDryRun($minutes);
        }

        if ($sync) {
            return $this->performSyncCleanup($minutes);
        }

        return $this->dispatchJob($minutes);
    }

    /**
     * Preview what would be deleted
     */
    private function performDryRun(int $minutes): int
    {
        $this->info('🔍 DRY RUN MODE - No items will be deleted');
        $this->newLine();

        $activeStatus = CartStatus::where('status_name', 'attivo')->first();

        if (!$activeStatus) {
            $this->error('Active cart status not found!');
            return Command::FAILURE;
        }

        $expirationThreshold = Carbon::now()->subMinutes($minutes);

        $expiredItems = CartItem::with(['user', 'article'])
            ->where('cart_status_id', $activeStatus->id)
            ->where('updated_at', '<', $expirationThreshold)
            ->get();

        if ($expiredItems->isEmpty()) {
            $this->info('✅ No expired cart items found.');
            return Command::SUCCESS;
        }

        $this->warn("Found {$expiredItems->count()} expired cart items:");
        $this->newLine();

        $tableData = $expiredItems->map(function ($item) {
            return [
                'ID' => $item->id,
                'User' => $item->user ? $item->user->name . ' ' . $item->user->cognome : 'N/A',
                'Article' => $item->article ? $item->article->article_name : 'N/A',
                'Qty' => $item->quantity,
                'PV Blocked' => number_format($item->pv_bloccati),
                'Age (min)' => $item->updated_at->diffInMinutes(now()),
                'Last Update' => $item->updated_at->format('d/m/Y H:i'),
            ];
        })->toArray();

        $this->table(
            ['ID', 'User', 'Article', 'Qty', 'PV Blocked', 'Age (min)', 'Last Update'],
            $tableData
        );

        $this->newLine();
        $totalPv = $expiredItems->sum('pv_bloccati');
        $this->info("Total PV that would be released: " . number_format($totalPv));

        return Command::SUCCESS;
    }

    /**
     * Run cleanup synchronously
     */
    private function performSyncCleanup(int $minutes): int
    {
        $this->info('🔄 Running synchronous cleanup...');

        try {
            $job = new CartCleanupJob($minutes);
            $job->handle();

            $this->info('✅ Cart cleanup completed successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Cleanup failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Dispatch job to queue
     */
    private function dispatchJob(int $minutes): int
    {
        $this->info('📤 Dispatching CartCleanupJob to queue...');

        CartCleanupJob::dispatch($minutes);

        $this->info('✅ Job dispatched successfully!');
        $this->comment('Run "php artisan queue:work" to process the job.');

        return Command::SUCCESS;
    }
}
