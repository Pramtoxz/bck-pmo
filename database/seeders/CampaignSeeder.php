<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campaign;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        Campaign::create([
            'title' => 'Gear Up & Get Rewarded',
            'badge' => 'NEW CONTRACT',
            'description' => 'Ends Dec 31, 2025 • Target: 85% Reach',
            'start_date' => '2025-07-01 00:00:00',
            'end_date' => '2025-12-31 23:59:59',
            'status' => 'active',
            'full_description' => 'Join our exclusive campaign and get amazing rewards! Purchase selected parts and reach your target to unlock special bonuses.',
            'terms_and_conditions' => '1. Campaign valid from July 1 to December 31, 2025\n2. Minimum purchase applies\n3. Rewards will be distributed after campaign ends',
            'parts_included' => ['OIL-001', 'OIL-002', 'PART-001', 'PART-003'],
            'rewards' => ['Free merchandise', 'Discount voucher 10%', 'Bonus points'],
        ]);

        Campaign::create([
            'title' => 'Summer Parts Sale',
            'badge' => 'PROMO',
            'description' => 'Ends Aug 31, 2025 • Special discount',
            'start_date' => '2025-06-01 00:00:00',
            'end_date' => '2025-08-31 23:59:59',
            'status' => 'completed',
            'full_description' => 'Summer special promotion with great discounts on selected parts.',
            'terms_and_conditions' => 'Terms and conditions apply',
            'parts_included' => ['PART-004', 'PART-005'],
            'rewards' => ['Cashback 5%'],
        ]);
    }
}
