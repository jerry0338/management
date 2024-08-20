<?php

namespace App\Models;

use CodeIgniter\Model;

class ManagementAlert extends Model
{
    protected $table            = 'management_alert';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'management_id',
        'still_on_site_alert',
        'sign_in_method',
        'sign_out_method',
        'sign_out_knr_method',
        'still_on_site_method',
        'sign_in_visitor',
        'sign_out_visitor',
        'sign_out_knr_visitor',
        'still_on_site_visitor',
        'sign_in_staff',
        'sign_out_staff',
        'sign_out_knr_staff',
        'still_on_site_staff',
        'sign_in_wvisiting',
        'sign_out_wvisiting',
        'sign_out_knr_wvisiting',
        'still_on_site_wvisiting',
        'sign_in_status',
        'sign_out_status',
        'sign_out_knr_status',
        'still_on_site_status',
        'created_at',
        'updated_at',
    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
