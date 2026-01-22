<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Verify E-commerce Setup
 * 
 * Checks if all e-commerce tables are properly seeded and configured.
 * 
 * Usage: php artisan ecommerce:verify
 */
class VerifyEcommerceSetup extends Command
{
    protected $signature = 'ecommerce:verify';
    protected $description = 'Verify e-commerce tables are properly seeded and configured';

    public function handle(): int
    {
        $this->info('🔍 Verifying E-commerce Setup...');
        $this->newLine();

        $allPassed = true;

        // 1. Check Tables Exist
        $allPassed = $this->checkTables() && $allPassed;

        // 2. Check Stores
        $allPassed = $this->checkStores() && $allPassed;

        // 3. Check Categories
        $allPassed = $this->checkCategories() && $allPassed;

        // 4. Check Articles
        $allPassed = $this->checkArticles() && $allPassed;

        // 5. Check Stock
        $allPassed = $this->checkStock() && $allPassed;

        // 6. Check Cart Statuses
        $allPassed = $this->checkCartStatuses() && $allPassed;

        // 7. Check Order Statuses
        $allPassed = $this->checkOrderStatuses() && $allPassed;

        // 8. Check User PV Fields
        $allPassed = $this->checkUserFields() && $allPassed;

        $this->newLine();

        if ($allPassed) {
            $this->info('✅ All e-commerce checks passed!');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Some checks failed. Run the seeder: php artisan db:seed --class=EcommerceSeeder');
            return Command::FAILURE;
        }
    }

    private function checkTables(): bool
    {
        $this->info('📋 Checking tables...');

        $requiredTables = [
            'stores',
            'categories',
            'articles',
            'stock',
            'cart_items',
            'cart_statuses',
            'orders',
            'order_items',
            'order_statuses',
            'filters',
        ];

        $missing = [];
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $missing[] = $table;
            }
        }

        if (empty($missing)) {
            $this->line('   ✅ All required tables exist');
            return true;
        } else {
            $this->error('   ❌ Missing tables: ' . implode(', ', $missing));
            return false;
        }
    }

    private function checkStores(): bool
    {
        $this->info('🏪 Checking stores...');

        $count = DB::table('stores')->count();
        $amazonExists = DB::table('stores')->where('slug', 'amazon')->exists();

        if ($count > 0 && $amazonExists) {
            $this->line("   ✅ Stores: {$count} found (Amazon: yes)");
            return true;
        } else {
            $this->error("   ❌ Stores: {$count} found (Amazon: " . ($amazonExists ? 'yes' : 'no') . ')');
            return false;
        }
    }

    private function checkCategories(): bool
    {
        $this->info('📁 Checking categories...');

        $count = DB::table('categories')->count();
        $buoniAmazon = DB::table('categories')->where('slug', 'buoni-amazon')->exists();

        if ($count > 0 && $buoniAmazon) {
            $this->line("   ✅ Categories: {$count} found (Buoni Amazon: yes)");
            return true;
        } else {
            $this->error("   ❌ Categories: {$count} found (Buoni Amazon: " . ($buoniAmazon ? 'yes' : 'no') . ')');
            return false;
        }
    }

    private function checkArticles(): bool
    {
        $this->info('🛍️ Checking articles...');

        $count = DB::table('articles')->count();
        $articles = DB::table('articles')
            ->whereIn('sku', ['AMZ-BUONO-15', 'AMZ-BUONO-30', 'AMZ-BUONO-50', 'AMZ-BUONO-100'])
            ->get(['sku', 'article_name', 'pv_price', 'euro_price', 'available']);

        if ($count >= 3) {
            $this->line("   ✅ Articles: {$count} found");
            
            $this->table(
                ['SKU', 'Name', 'PV', 'Euro', 'Available'],
                $articles->map(fn($a) => [
                    $a->sku,
                    $a->article_name,
                    $a->pv_price,
                    $a->euro_price ?? 'NULL',
                    $a->available ? '✅' : '❌'
                ])->toArray()
            );
            return true;
        } else {
            $this->error("   ❌ Articles: {$count} found (expected at least 3)");
            return false;
        }
    }

    private function checkStock(): bool
    {
        $this->info('📦 Checking stock...');

        $count = DB::table('stock')->count();
        $articlesWithStock = DB::table('stock')
            ->join('articles', 'stock.article_id', '=', 'articles.id')
            ->where('stock.quantity', '>', 0)
            ->count();

        if ($count > 0) {
            $this->line("   ✅ Stock records: {$count} (with quantity > 0: {$articlesWithStock})");
            return true;
        } else {
            $this->error("   ❌ Stock records: {$count} (expected > 0)");
            return false;
        }
    }

    private function checkCartStatuses(): bool
    {
        $this->info('🛒 Checking cart statuses...');

        $statuses = DB::table('cart_statuses')->pluck('status_name')->toArray();
        $required = ['attivo', 'in_attesa_di_pagamento', 'completato'];
        $missing = array_diff($required, $statuses);

        if (empty($missing)) {
            $this->line('   ✅ Cart statuses: ' . implode(', ', $statuses));
            return true;
        } else {
            $this->error('   ❌ Missing cart statuses: ' . implode(', ', $missing));
            return false;
        }
    }

    private function checkOrderStatuses(): bool
    {
        $this->info('📋 Checking order statuses...');

        $statuses = DB::table('order_statuses')->pluck('status_name')->toArray();
        $required = ['in_attesa', 'in_lavorazione', 'completato', 'annullato'];
        $missing = array_diff($required, $statuses);

        if (empty($missing)) {
            $this->line('   ✅ Order statuses: ' . implode(', ', $statuses));
            return true;
        } else {
            $this->error('   ❌ Missing order statuses: ' . implode(', ', $missing));
            return false;
        }
    }

    private function checkUserFields(): bool
    {
        $this->info('👤 Checking user PV fields...');

        $requiredColumns = [
            'punti_valore_maturati',
            'punti_carriera_maturati',
            'punti_bonus',
            'punti_spesi',
        ];

        $missing = [];
        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('users', $column)) {
                $missing[] = $column;
            }
        }

        if (empty($missing)) {
            $this->line('   ✅ All PV fields exist in users table');
            return true;
        } else {
            $this->error('   ❌ Missing columns in users: ' . implode(', ', $missing));
            return false;
        }
    }
}
