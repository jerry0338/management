<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\{ManagementType, VisitorType, States};
use App\Models\Type;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class ListController extends BaseController
{
    use ResponseTrait;

    public function managementType()
    {
        try {
            $model = new ManagementType();
            $data = $model->select(['id as management_type_id', 'type as management_type'])->orderBy('type')->findAll();
            return $this->respond(['status' => 1,'message' => 'Retrieved', 'data' => $data], 200);
        } catch (Exception $exception) {
            return response()->json(['status' => 0, 'msg' => 'Something went wrong.'], 500);
        }   
    }

    public function visitorType()
    {
        try {
            $model = new VisitorType();
            $data = $model->select(['id as visitor_type_id', 'type as visitor_type'])->orderBy('type')->findAll();
            return $this->respond(['status' => 1,'message' => 'Retrieved', 'data' => $data], 200);
        } catch (Exception $exception) {
            return response()->json(['status' => 0, 'msg' => 'Something went wrong.'], 500);
        }   
    }

    public function states()
    {
        try {
            $model = new States();
            $data = $model->select(['id as state_id', 'name as state_name'])->where('country_id', 14)->orderBy('name')->findAll();
            return $this->respond(['message' => 'Retrieved', 'data' => $data], 200);
        } catch (Exception $exception) {
            return response()->json(['status' => 0, 'msg' => 'Something went wrong.'], 500);
        }   
    }
}
