<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\State;

class StateSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'state' => 'Gujarat',
                'created_at' => date('d-M-Y H:i:s'),
                'updated_at' => date('d-M-Y H:i:s'),
            ],
        ];

        foreach ($data as $key => $value) {
            $insert = new State();
            $insertdata['state'] = $value['state'];
            $insert->insert($insertdata);
        }
    }
}
