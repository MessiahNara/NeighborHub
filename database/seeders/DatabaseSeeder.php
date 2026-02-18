<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['title' => 'Selling Bangus', 'category' => 'buy_sell'],
            ['title' => 'Borrow Ladder', 'category' => 'borrow'],
            ['title' => 'Electrician Service', 'category' => 'service'],
            ['title' => 'Town Fiesta', 'category' => 'event'],
            ['title' => 'Binmaley Plaza', 'category' => 'place'],
            ['title' => 'Water Interruption', 'category' => 'announcement'],
        ];

        foreach ($data as $item) {
            Post::create($item);
        }
    }
}