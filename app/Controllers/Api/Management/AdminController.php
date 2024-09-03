<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{Management, ManagementStaff, VisitorRecords, VisitorRecordKeys, ManagementLogin};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use \Firebase\JWT\JWT;
use DateTime;
class AdminController extends BaseController
{
    use ResponseTrait;
    
    public function list()
    {
        $rules = [
            'management_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                $managementStaff = new ManagementStaff();
                $managementStaff = $managementStaff->where('role', 'Admin')->where('management_id', $body->management_id)->get();
                $data = array(); $d=0;
                if ($results = $managementStaff->getResult()) {
                    foreach ($results as $key => $result) {
                        $data[$d]['management_staff_id'] = $result->id;
                        $data[$d]['name'] = $result->name;
                        $data[$d]['mobile_number'] = $result->mobile_number;
                        $data[$d]['email'] = $result->email;
                        $data[$d]['role'] = $result->role;
                        $data[$d]['access'] = $result->access;
                        $data[$d]['key_id'] = 'n/a';
                        $data[$d]['created_at'] = $result->created_at;
                        $d++;
                    }
                }
                
                if(sizeof($data) > 0){
                    return $this->respond(['status' => 1, 'message' => 'Admin data', 'data' => $data], 200);
                }else{
                    return $this->respond(['status' => 0, 'message' => 'No Admin data found ', 'data' => array()], 200);
                }
            } catch (Exception $exception) {
                return response()->json(['status' => 0, 'msg' => 'Something went wrong.'], 500);
            } 
        } else {
            $response = [
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->fail($response, 409);
        }
    }
    public function accessUpdate()
    {
        $rules = [
            'management_staff_id' => ['rules' => 'required'],
            'access' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                $db = \Config\Database::connect();
                $management_staff = $db->table('management_staff');
                $managementStaff = $management_staff->where('id', $body->management_staff_id);
                $data = [
                    'access' => $body->access
                ];

                if($managementStaff->update($data)){
                    return $this->respond(['status' => 1, 'message' => 'Staff access updated successfully'], 200);
                }else{
                    return $this->respond(['status' => 0, 'message' => 'Staff access not updat.please, try again.'], 200);
                }
            } catch (Exception $exception) {
                return response()->json(['status' => 0, 'msg' => 'Something went wrong.'], 500);
            } 
        } else {
            $response = [
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->fail($response, 409);
        }
    }
    public function passwordUpdate()
    {
        $rules = [
            'management_staff_id' => ['rules' => 'required'],
            'password' => ['rules' => 'required|min_length[6]|max_length[255]']
        ];

        $body = json_decode($this->request->getBody());
        helper('text'); 
        if ($this->validate($rules)) {
            try {
                $db = \Config\Database::connect();
                $management_staff = $db->table('management_staff');
                $managementStaff = $management_staff->where('id', $body->management_staff_id);
                $data = [
                    'password' => password_hash($body->password, PASSWORD_DEFAULT)
                ];

                if($managementStaff->update($data)){
                    return $this->respond(['status' => 1, 'message' => 'Staff access updated successfully'], 200);
                }else{
                    return $this->respond(['status' => 0, 'message' => 'Staff access not updat.please, try again.'], 200);
                }
            } catch (Exception $exception) {
                return response()->json(['status' => 0, 'msg' => 'Something went wrong.'], 500);
            } 
        } else {
            $response = [
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->fail($response, 409);
        }
    }
}