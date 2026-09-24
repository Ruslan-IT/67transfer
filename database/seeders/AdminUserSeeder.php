<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'ruslan.r.f@yandex.ru'],
            [
                'name' => 'Admin',
                'password' => Hash::make('123'),
                'is_admin' => true,
            ]
        );
    }
}
