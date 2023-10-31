<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create([
            'name' => 'super-admin'
        ]);

        Admin::create([
            'username' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('12345'),
            'role_id' => 1,
        ]);

        User::create([
            'first_name' => 'علي',
            'last_name' => 'خضر',
            'email' => 'ali@gmail.com',
            'password' => bcrypt('12345'),
            'birthday' => '1999-07-22',
            'phone_number' => '0936943559',
            'bio' => 'bio',
            'profissionName' => 'engineer',
            'speciality' => 'laravel developer',
            'type' => 0,
        ]);
    }
}
