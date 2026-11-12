<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchWarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Cairo Branch',
                'code' => 'CAI',
                'address' => 'Nasr City, Cairo',
                'warehouses' => [
                    [
                        'name' => 'Cairo Main Warehouse',
                        'code' => 'CAI-MAIN',
                        'address' => 'Nasr City, Cairo',
                    ]
                ],
            ],
            [
                'name' => 'Giza Branch',
                'code' => 'GIZ',
                'address' => 'Dokki, Giza',
                'warehouses' => [
                    [
                        'name' => 'Giza Main Warehouse',
                        'code' => 'GIZ-MAIN',
                        'address' => 'Dokki, Giza',
                    ]
                ],
            ],

        ];

        foreach ($branches as $branchData) {
            $warehouses = $branchData['warehouses'];

            unset($branchData['warehouses']);

            $branch = Branch::firstOrCreate(
                ['code' => $branchData['code']],
                [
                    'name' => $branchData['name'],
                    'address' => $branchData['address'],
                    'is_active' => true,
                ]
            );

            foreach ($warehouses as $warehouseData) {
                $branch->warehouses()->firstOrCreate(
                    ['code' => $warehouseData['code']],
                    [
                        'name' => $warehouseData['name'],
                        'address' => $warehouseData['address'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
