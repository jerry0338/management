<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run()
    {
        $this->call('TypeSeeder');
        $this->call('RoleSeeder');
        $this->call('StateSeeder');
    }
}
