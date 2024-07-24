<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVisitor extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 255,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'qr_key' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true
            ],
            'type_id' => [
                'type' => 'BIGINT',
                'constraint' => 255,
                'unsigned' => true,
                'null' => true
            ],
            'visitor_person' => [
                'type' => 'BIGINT',
                'constraint' => 255,
                'unsigned' => true,
                'null' => true
            ],
            'state_id' => [
                'type' => 'BIGINT',
                'constraint' => 255,
                'unsigned' => true,
                'null' => true
            ],
            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true
            ],
            'last_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true
            ],
            'company_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true
            ],
            'wwcc' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true
            ],
            'mobile_number' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true
            ],
            'photo' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true
            ],
            'location_service' => [
                'type' => 'TINYINT',
                'constraint' => '1',
                'default' => '0'
            ],
            'is_covid_or_sickness' => [
                'type' => 'TINYINT',
                'constraint' => '1',
                'default' => '0'
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'unique' => true,
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('type_id', 'types', 'id');
        $this->forge->addForeignKey('state_id', 'states', 'id');
        $this->forge->createTable('visitors');
    }

    public function down()
    {
        $this->forge->dropTable('visitors');
    }
}
