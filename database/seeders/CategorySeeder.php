<?php

//create categories
namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create([
            'user_id' => 1,
            'name' => 'Groceries',
        ]);

        Category::create([
            'user_id' => 1,
            'name' => 'Entertainment',
        ]);
        Category::create([
            'user_id' => 1,
            'name' => 'Utilities',
        ]);
        Category::create([
            'user_id' => 1,
            'name' => 'Food & Drinks',
        ]);
        Category::create([
            'user_id' => 1,
            'name' => 'Transportation',
        ]);
        Category::create([
            'user_id' => 1,
            'name' => 'Others',
        ]);
    }
}
