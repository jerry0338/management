<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVisitorHistory extends Migration
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
            'notes' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'user_id' => [
                'type' => 'BIGINT',
                'constraint' => 255,
                'unsigned' => true,
                'null' => true
            ],
            'visitor_id' => [
                'type' => 'BIGINT',
                'constraint' => 255,
                'unsigned' => true,
                'null' => true
            ],
            'modified_date' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'purpose' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'purpose_value' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
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
        $this->forge->addForeignKey('user_id', 'users', 'id');
        $this->forge->addForeignKey('visitor_id', 'visitors', 'id');
        $this->forge->createTable('visitorhistories');
    }

    public function down()
    {
        $this->forge->dropTable('visitorhistories');
    }
}
