<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'role' => 'Principal',
                'created_at' => date('d-M-Y H:i:s'),
                'updated_at' => date('d-M-Y H:i:s'),
            ],
            [
                'role' => 'Business Manager',
                'created_at' => date('d-M-Y H:i:s'),
                'updated_at' => date('d-M-Y H:i:s'),
            ],
            [
                'role' => 'Grounsperson',
                'created_at' => date('d-M-Y H:i:s'),
                'updated_at' => date('d-M-Y H:i:s'),
            ],
        ];

        foreach ($data as $key => $value) {
            $insert = new Role();
            $insertdata['role'] = $value['role'];
            $insert->insert($insertdata);
        }
    }
}
