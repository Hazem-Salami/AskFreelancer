<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $first_seed = [
            ['name' => 'Software Engineering'],
            ['name' => 'Translating'],
        ];
        Category::insert($first_seed);

        $second_seed = [
            ['name' => 'English', 'parent_id' => 2],
            ['name' => 'Arabic', 'parent_id' => 2],
        ];
        Category::insert($second_seed);
    }
}
