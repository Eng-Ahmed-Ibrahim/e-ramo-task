<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customer = [
            'name' => 'Ahmed Ibrahim',
            'email' => 'ahmed@gmail.com',
            'phone' => '01012345678',
            'password' => 'p@ssw0rd',
        ];

        Customer::firstOrCreate(
            ['email' => $customer['email']],
            $customer
        );
    }
}
