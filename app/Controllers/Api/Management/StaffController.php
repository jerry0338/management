<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{Management, ManagementStaff, VisitorRecords, VisitorRecordKeys, ManagementLogin};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use \Firebase\JWT\JWT;
use DateTime;
class StaffController extends BaseController
{
    use ResponseTrait;
    
    public function add()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'name' => ['rules' => 'required'],
            'mobile_number' => ['rules' => 'required'],
            'email' => ['rules' => 'required'],
            'role' => ['rules' => 'required']
        ];
        $body = json_decode($this->request->getBody());
        if ($this->validate($rules)) {
            try {
                $managementModel = new Management();
                $management = $managementModel->where('email', $body->email)->first();
                if (is_null($management)) {
                    $managementStaffs = new ManagementStaff();
                    $managementStaff = $managementStaffs->where('management_id', $body->management_id)->where('email', $body->email)->first();
                    if (is_null($managementStaff)) {
                        helper('text'); 
                        $qr_key = random_string('alnum', 16);
                        $password = $body->password ?? '123456';
                        $managementStaff = new ManagementStaff();
                        $data = [
                            'management_id' => $body->management_id,
                            'name'          => $body->name,
                            'mobile_number' => $body->mobile_number,
                            'email'         => $body->email,
                            'password'      => password_hash($password, PASSWORD_DEFAULT),
                            'unique_key'    => $qr_key,
                            'role'          => $body->role
                        ];                    
                        $managementStaff->insert($data);
                        return $this->respond(['status' => 1, 'message' => 'Staff added successfully'], 200);
                    }else{
                        return $this->respond(['status' => 0,'message' => 'Email already taken.'], 200);
                    }
                }else{
                    return $this->respond(['status' => 0,'message' => 'Email already taken.'], 200);
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
    public function edit()
    {
        $rules = [
            'management_staff_id' => ['rules' => 'required'],
            'management_id' => ['rules' => 'required'],
            'name' => ['rules' => 'required'],
            'mobile_number' => ['rules' => 'required'],
            'email' => ['rules' => 'required'],
            'role' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                $managementStaff = new ManagementStaff();
                $management = $managementStaff->where('id NOT LIKE', $body->management_staff_id)->where('management_id', $body->management_id)->where('email', $body->email)->first();
                if (is_null($management)) {
                    $db = \Config\Database::connect();
                    $management_staff = $db->table('management_staff');
                    $managementStaff = $management_staff->where('id', $body->management_staff_id);
                    $data = [
                        'name'          => $body->name,
                        'mobile_number' => $body->mobile_number,
                        'email'         => $body->email,
                        'role'          => $body->role
                    ];
    
                    if($managementStaff->update($data)){
                        return $this->respond(['status' => 1, 'message' => 'Staff updated successfully'], 200);
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Staff not updat.please, try again.'], 200);
                    }
                }else{
                    return $this->respond(['status' => 0,'message' => 'Staff already added.'], 200);
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
    public function delete()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_staff_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
             
                $db = \Config\Database::connect();
                $managementStaff = $db->table('management_staff');
                
                // Assuming $id contains the ID of the row you want to delete
                $managementStaff->where('management_id', $body->management_id);
                $managementStaff->where('id', $body->management_staff_id);
                if ($managementStaff->delete()){
                    return $this->respond(['status' => 1, 'message' => 'Staff deleted.'], 200);
                }else{
                    return $this->respond(['status' => 0, 'message' => 'Staff not delete.please, try again.'], 200);
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
    public function list()
    {
        $rules = [
            'management_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                $managementStaff = new ManagementStaff();
                $managementStaff = $managementStaff->where('management_id', $body->management_id)->get();
                $data = array(); $d=0;
                if ($results = $managementStaff->getResult()) {
                    foreach ($results as $key => $result) {
                        $data[$d]['management_staff_id'] = $result->id;
                        $data[$d]['name'] = $result->name;
                        $data[$d]['mobile_number'] = $result->mobile_number;
                        $data[$d]['email'] = $result->email;
                        $data[$d]['role'] = $result->role;
                        $data[$d]['key_id'] = 'n/a';
                        $data[$d]['created_at'] = $result->created_at;
                        $d++;
                    }
                }
                
                if(sizeof($data) > 0){
                    return $this->respond(['status' => 1, 'message' => 'Staff data', 'data' => $data], 200);
                }else{
                    return $this->respond(['status' => 0, 'message' => 'No Staff data found ', 'data' => array()], 200);
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
    public function uploadCsv()
    {
        $bodys = json_decode($this->request->getBody());
        try {
            foreach($bodys as $body){
                $managementModel = new Management();
                $management = $managementModel->where('email', $body->email)->first();
                if (is_null($management)) {
                    $managementStaffs = new ManagementStaff();
                    $managementStaff = $managementStaffs->where('management_id', $body->management_id)->where('email', $body->email)->first();
                    if (is_null($managementStaff)) {
                        helper('text'); 
                        $qr_key = random_string('alnum', 16);
                        $password = $body->password ?? '123456';
                        $managementStaff = new ManagementStaff();
                        $data = [
                            'management_id' => $body->management_id,
                            'name'          => $body->name,
                            'mobile_number' => $body->mobile_number,
                            'email'         => $body->email,
                            'password'      => password_hash($password, PASSWORD_DEFAULT),
                            'unique_key'    => $qr_key,
                            'role'          => $body->role
                        ];                    
                        $managementStaff->insert($data);
                    }
                }
            }            
            return $this->respond(['status' => 1, 'message' => 'Upload csv successfully'], 200);
        } catch (Exception $exception) {
            return response()->json(['status' => 0, 'msg' => 'Something went wrong.'], 500);
        } 
    }
    public function signOut()
    {
        $rules = [
            'management_id' => ['rules' => 'required']
        ];
        $body = json_decode($this->request->getBody());
        if ($this->validate($rules)) {
            try {
                $managementLoginModel = new ManagementLogin();
                $managementLogin = $managementLoginModel->where('staff_id', $body->management_id)->where('status', '0')->first();
                if (!is_null($managementLogin)) {

                    $time1 = $managementLogin['time_in']; 
                    $time2 = date('H:i:s');
                    $datetime1 = new DateTime($time1);
                    $datetime2 = new DateTime($time2);
                    $interval = $datetime1->diff($datetime2);
                    $total_time = $interval->format('%h hours %i minutes');

                    $db = \Config\Database::connect();
                    $managementLoginUpdate = $db->table('management_login');
                    $managementLoginUpdate = $managementLoginUpdate->where('id', $managementLogin['id']);
                    $data = [
                        'time_out'   => date('h:i:s'),
                        'total_time' => $total_time,
                        'status'     => 1
                    ];
    
                    if($managementLoginUpdate->update($data)){
                        return $this->respond(['status' => 1, 'message' => 'Staff signout successfully'], 200);
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Staff not signout.please, try again.'], 200);
                    }
                }else{
                    return $this->respond(['status' => 0,'message' => 'Staff already signout.'], 200);
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