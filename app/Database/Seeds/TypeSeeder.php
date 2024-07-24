<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\Type;

class TypeSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'type' => 'Staff',
                'created_at' => date('d-M-Y H:i:s'),
                'updated_at' => date('d-M-Y H:i:s'),
            ],
        ];

        foreach ($data as $key => $value) {
            $insert = new Type();
            $insertdata['type'] = $value['type'];
            $insert->insert($insertdata);
        }
    }
}
