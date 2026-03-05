<?php

namespace Database\Seeders;

use App\Models\EcommerceHomepageSlide;
use App\Models\EcommerceHomepageProductRow;
use Illuminate\Database\Seeder;

/**
 * EcommerceHomepageSeeder
 * 
 * Migrates the hardcoded carousel slides and product row config
 * from ecommerce.component.ts into the database.
 * 
 * Safe to run multiple times (uses updateOrCreate).
 */
class EcommerceHomepageSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSlides();
        $this->seedProductRows();
    }

    private function seedSlides(): void
    {
        $slides = [
            [
                'title' => 'Buoni Amazon Disponibili!',
                'description' => 'Converti i tuoi Punti Valore in buoni regalo Amazon. Disponibili tagli da 5€ a 100€.',
                'badge_text' => 'Disponibile Ora',
                'badge_icon' => 'redeem',
                'cta_text' => 'Scopri i Buoni',
                'cta_action' => 'scroll-catalog',
                'cta_disabled' => false,
                'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'sort_order' => 0,
                'is_active' => true,
            ],
            [
                'title' => 'Premia la Tua Fedeltà!',
                'description' => 'Accumula Punti Valore con ogni contratto e scopri le ricompense esclusive del nostro programma fedeltà.',
                'badge_text' => 'Programma Fedeltà',
                'badge_icon' => 'emoji_events',
                'cta_text' => 'Vedi il Tuo Saldo',
                'cta_action' => 'open-wallet',
                'cta_disabled' => false,
                'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Buoni Carburante in Arrivo!',
                'description' => 'Presto potrai convertire i tuoi Punti Valore anche in buoni carburante per i tuoi spostamenti.',
                'badge_text' => 'Prossimamente',
                'badge_icon' => 'local_gas_station',
                'cta_text' => 'In Arrivo',
                'cta_action' => 'coming-soon',
                'cta_disabled' => true,
                'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($slides as $slideData) {
            EcommerceHomepageSlide::updateOrCreate(
                ['title' => $slideData['title']],
                $slideData
            );
        }
    }

    private function seedProductRows(): void
    {
        $rows = [
            [
                'row_key' => 'featured',
                'title' => 'In Evidenza',
                'icon' => 'local_fire_department',
                'row_type' => 'featured',
                'display_style' => 'grid',
                'items_per_row' => 4,
                'max_items' => 8,
                'sort_order' => 0,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'row_key' => 'new_arrivals',
                'title' => 'Novità',
                'icon' => 'fiber_new',
                'row_type' => 'new_arrivals',
                'display_style' => 'grid',
                'items_per_row' => 4,
                'max_items' => 8,
                'sort_order' => 1,
                'is_active' => true,
                'is_system' => true,
            ],
        ];

        foreach ($rows as $rowData) {
            EcommerceHomepageProductRow::updateOrCreate(
                ['row_key' => $rowData['row_key']],
                $rowData
            );
        }
    }
}