<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => "admin@gmail.com"],
            [
                'name' => "Admin",
                'password' => "p@ssw0rd",
            ]
        );

        $admin->syncRoles(['admin']);
    }
}
