<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Complete E-commerce Seeder
 * 
 * Seeds all necessary e-commerce tables in correct order:
 * 1. Filters (role-based visibility)
 * 2. Stores
 * 3. Categories
 * 4. Cart Statuses
 * 5. Order Statuses
 * 6. Payment Modes (for e-commerce)
 * 7. Articles (products)
 * 8. Stock
 * 
 * Run with: php artisan db:seed --class=EcommerceSeeder
 */
class EcommerceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting E-commerce seeder...');

        // 1. Seed Filters
        $this->seedFilters();

        // 2. Seed Stores
        $this->seedStores();

        // 3. Seed Categories
        $this->seedCategories();

        // 4. Seed Cart Statuses
        $this->seedCartStatuses();

        // 5. Seed Order Statuses
        $this->seedOrderStatuses();

        // 6. Seed Payment Modes (e-commerce specific)
        $this->seedPaymentModes();

        // 7. Seed Articles
        $this->seedArticles();

        // 8. Seed Stock
        $this->seedStock();

        $this->command->info('E-commerce seeder completed successfully!');
    }

    /**
     * Seed visibility filters
     * 
     * Filters control which users can see specific stores/categories/products.
     * Based on role_id and/or qualification_id.
     */
    private function seedFilters(): void
    {
        $this->command->info('Seeding filters...');

        $filters = [
            // 1. Base filter: All users (no restrictions)
            [
                'filter_name' => 'Tutti gli utenti',
                'description' => 'Visibile a tutti gli utenti registrati',
                'role_id' => null,
                'qualification_id' => null,
                'expand_to_leads' => false,
            ],
            
            // 2. Solo Advisor (SEU) - any qualification
            [
                'filter_name' => 'Solo Advisor',
                'description' => 'Visibile solo agli Advisor (SEU)',
                'role_id' => 2, // Advisor
                'qualification_id' => null,
                'expand_to_leads' => false,
            ],
            
            // 3. Solo Clienti
            [
                'filter_name' => 'Solo Clienti',
                'description' => 'Visibile solo ai Clienti',
                'role_id' => 3, // Cliente
                'qualification_id' => null,
                'expand_to_leads' => false,
            ],
            
            // 4. Advisor Senior (SEU Senior e superiori)
            [
                'filter_name' => 'Advisor Senior',
                'description' => 'Visibile agli Advisor con qualifica SEU Senior o superiore',
                'role_id' => 2, // Advisor
                'qualification_id' => 5, // SEU Senior
                'expand_to_leads' => false,
            ],
            
            // 5. Advisor with expand_to_leads (includes converted leads)
            [
                'filter_name' => 'Advisor + Lead Convertiti',
                'description' => 'Visibile agli Advisor e ai loro lead convertiti',
                'role_id' => 2, // Advisor
                'qualification_id' => null,
                'expand_to_leads' => true,
            ],
            
            // 6. BackOffice only
            [
                'filter_name' => 'Solo BackOffice',
                'description' => 'Visibile solo agli operatori BackOffice',
                'role_id' => 5, // BackOffice
                'qualification_id' => null,
                'expand_to_leads' => false,
            ],
            
            // 7. Admin only
            [
                'filter_name' => 'Solo Admin',
                'description' => 'Visibile solo agli Amministratori',
                'role_id' => 1, // Administrator
                'qualification_id' => null,
                'expand_to_leads' => false,
            ],
        ];

        foreach ($filters as $filter) {
            DB::table('filters')->updateOrInsert(
                ['filter_name' => $filter['filter_name']],
                array_merge($filter, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    /**
     * Seed stores
     */
    private function seedStores(): void
    {
        $this->command->info('Seeding stores...');

        $stores = [
            [
                'store_name' => 'Amazon',
                'slug' => 'amazon',
                'store_type' => 'digital',
                'description' => 'Buoni regalo Amazon - Consegna digitale immediata',
                'filter_id' => null, // Visible to all
                'active' => true,
                'sort_order' => 1,
                'contact_email' => 'support@semprechiaro.it',
            ],
            [
                'store_name' => 'Carburante',
                'slug' => 'carburante',
                'store_type' => 'digital',
                'description' => 'Buoni carburante - Prossimamente disponibile',
                'filter_id' => null,
                'active' => false, // Coming soon
                'sort_order' => 2,
                'contact_email' => 'support@semprechiaro.it',
            ],
        ];

        foreach ($stores as $store) {
            DB::table('stores')->updateOrInsert(
                ['slug' => $store['slug']],
                array_merge($store, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    /**
     * Seed categories
     */
    private function seedCategories(): void
    {
        $this->command->info('Seeding categories...');

        $categories = [
            [
                'category_name' => 'Buoni Sconto',
                'slug' => 'buoni-sconto',
                'description' => 'Buoni sconto digitali per acquisti online e offline',
                'icon' => 'card_giftcard',
                'filter_id' => null, // Visible to all
                'parent_id' => null,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'category_name' => 'Buoni Amazon',
                'slug' => 'buoni-amazon',
                'description' => 'Buoni regalo Amazon utilizzabili su Amazon.it',
                'icon' => 'shopping_bag',
                'filter_id' => null,
                'parent_id' => null, // Will be updated after parent creation
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'category_name' => 'Buoni Carburante',
                'slug' => 'buoni-carburante',
                'description' => 'Buoni carburante per le principali catene di distributori',
                'icon' => 'local_gas_station',
                'filter_id' => null,
                'parent_id' => null,
                'sort_order' => 2,
                'is_active' => false, // Coming soon
            ],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                array_merge($category, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Set parent_id for Buoni Amazon (child of Buoni Sconto)
        $parentId = DB::table('categories')->where('slug', 'buoni-sconto')->value('id');
        if ($parentId) {
            DB::table('categories')
                ->where('slug', 'buoni-amazon')
                ->update(['parent_id' => $parentId]);

            DB::table('categories')
                ->where('slug', 'buoni-carburante')
                ->update(['parent_id' => $parentId]);
        }
    }

    /**
     * Seed cart statuses
     */
    private function seedCartStatuses(): void
    {
        $this->command->info('Seeding cart statuses...');

        $statuses = [
            'attivo',
            'in_attesa_di_pagamento',
            'pagamento_effettuato',
            'completato',
            'inattivo',
        ];

        foreach ($statuses as $status) {
            DB::table('cart_statuses')->updateOrInsert(
                ['status_name' => $status],
                [
                    'status_name' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Seed order statuses
     */
    private function seedOrderStatuses(): void
    {
        $this->command->info('Seeding order statuses...');

        $statuses = [
            ['status_name' => 'in_attesa', 'description' => 'Ordine in attesa di elaborazione'],
            ['status_name' => 'in_lavorazione', 'description' => 'Ordine preso in carico dal backoffice'],
            ['status_name' => 'completato', 'description' => 'Ordine evaso e buono inviato'],
            ['status_name' => 'annullato', 'description' => 'Ordine annullato'],
            ['status_name' => 'rimborsato', 'description' => 'Ordine rimborsato - PV restituiti'],
        ];

        foreach ($statuses as $status) {
            DB::table('order_statuses')->updateOrInsert(
                ['status_name' => $status['status_name']],
                array_merge($status, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    /**
     * Seed payment modes for e-commerce
     */
    private function seedPaymentModes(): void
    {
        $this->command->info('Seeding e-commerce payment modes...');

        $modes = [
            [
                'tipo_pagamento' => 'Punti Valore (PV)',
                'payment_type' => 'instant',
            ],
            // Future payment methods
            // [
            //     'tipo_pagamento' => 'PayPal',
            //     'payment_type' => 'electronic',
            // ],
            // [
            //     'tipo_pagamento' => 'Stripe',
            //     'payment_type' => 'electronic',
            // ],
        ];

        foreach ($modes as $mode) {
            // Check if already exists to avoid duplicates
            $exists = DB::table('payment_modes')
                ->where('tipo_pagamento', $mode['tipo_pagamento'])
                ->exists();

            if (!$exists) {
                DB::table('payment_modes')->insert(array_merge($mode, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Seed articles (products)
     */
    private function seedArticles(): void
    {
        $this->command->info('Seeding articles...');

        // Get store and category IDs
        $storeId = DB::table('stores')->where('slug', 'amazon')->value('id');
        $categoryId = DB::table('categories')->where('slug', 'buoni-amazon')->value('id');

        if (!$storeId || !$categoryId) {
            $this->command->error('Store or Category not found! Make sure stores and categories are seeded first.');
            return;
        }

        $articles = [
            [
                'sku' => 'AMZ-BUONO-15',
                'article_name' => 'Buono Amazon da 15€',
                'description' => 'Buono regalo digitale Amazon del valore di 15 euro. Utilizzabile per qualsiasi acquisto su Amazon.it. Consegna immediata via email dopo l\'elaborazione dell\'ordine. Nessuna scadenza.',
                'pv_price' => 300,
                'euro_price' => 15.00,
                'is_digital' => true,
                'available' => true,
                'sort_order' => 1,
                'is_featured' => false,
                'category_id' => $categoryId,
                'store_id' => $storeId,
            ],
            [
                'sku' => 'AMZ-BUONO-30',
                'article_name' => 'Buono Amazon da 30€',
                'description' => 'Buono regalo digitale Amazon del valore di 30 euro. Utilizzabile per qualsiasi acquisto su Amazon.it. Consegna immediata via email dopo l\'elaborazione dell\'ordine. Nessuna scadenza.',
                'pv_price' => 600,
                'euro_price' => 30.00,
                'is_digital' => true,
                'available' => true,
                'sort_order' => 2,
                'is_featured' => true, // Best seller
                'category_id' => $categoryId,
                'store_id' => $storeId,
            ],
            [
                'sku' => 'AMZ-BUONO-50',
                'article_name' => 'Buono Amazon da 50€',
                'description' => 'Buono regalo digitale Amazon del valore di 50 euro. Utilizzabile per qualsiasi acquisto su Amazon.it. Consegna immediata via email dopo l\'elaborazione dell\'ordine. Nessuna scadenza.',
                'pv_price' => 1000,
                'euro_price' => 50.00,
                'is_digital' => true,
                'available' => true,
                'sort_order' => 3,
                'is_featured' => false,
                'category_id' => $categoryId,
                'store_id' => $storeId,
            ],
            [
                'sku' => 'AMZ-BUONO-100',
                'article_name' => 'Buono Amazon da 100€',
                'description' => 'Buono regalo digitale Amazon del valore di 100 euro. Utilizzabile per qualsiasi acquisto su Amazon.it. Consegna immediata via email dopo l\'elaborazione dell\'ordine. Nessuna scadenza.',
                'pv_price' => 2000,
                'euro_price' => 100.00,
                'is_digital' => true,
                'available' => true,
                'sort_order' => 4,
                'is_featured' => false,
                'category_id' => $categoryId,
                'store_id' => $storeId,
            ],
        ];

        foreach ($articles as $article) {
            DB::table('articles')->updateOrInsert(
                ['sku' => $article['sku']],
                array_merge($article, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    /**
     * Seed stock for articles
     */
    private function seedStock(): void
    {
        $this->command->info('Seeding stock...');

        $storeId = DB::table('stores')->where('slug', 'amazon')->value('id');

        if (!$storeId) {
            $this->command->error('Store not found!');
            return;
        }

        // Get all Amazon articles
        $articles = DB::table('articles')
            ->where('store_id', $storeId)
            ->where('is_digital', true)
            ->get();

        foreach ($articles as $article) {
            DB::table('stock')->updateOrInsert(
                [
                    'article_id' => $article->id,
                    'store_id' => $storeId,
                ],
                [
                    'quantity' => 9999, // Infinite for digital products
                    'total_stock' => 9999,
                    'minimum_stock' => 100, // Alert threshold
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
