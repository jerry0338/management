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
            'management_type' => ['rules' => 'required'],
            'name' => ['rules' => 'required'],
            'mobile_number' => ['rules' => 'required'],
            'email' => ['rules' => 'required'],
            'role' => ['rules' => 'required']
        ];
        $body = json_decode($this->request->getBody());
        if ($this->validate($rules)) {
            try {
                helper('common');
                if($body->management_type == 'staff'){
                    $management_id = managementTypeToIdGet($body->management_id);
                }else{
                    $management_id = $body->management_id;
                }

                $managementModel = new Management();
                $management = $managementModel->where('email', $body->email)->first();
                if (is_null($management)) {
                    $managementStaffs = new ManagementStaff();
                    $managementStaff = $managementStaffs->where('management_id', $management_id)->where('email', $body->email)->first();
                    if (is_null($managementStaff)) {
                        helper('text'); 
                        $qr_key = random_string('alnum', 16);
                        $password = $body->password ?? '123456';
                        $managementStaff = new ManagementStaff();
                        $data = [
                            'management_id' => $management_id,
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
            'management_type' => ['rules' => 'required'],
            'name' => ['rules' => 'required'],
            'mobile_number' => ['rules' => 'required'],
            'email' => ['rules' => 'required'],
            'role' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                helper('common');
                if($body->management_type == 'staff'){
                    $management_id = managementTypeToIdGet($body->management_id);
                }else{
                    $management_id = $body->management_id;
                }

                $managementStaff = new ManagementStaff();
                $management = $managementStaff->where('id NOT LIKE', $body->management_staff_id)->where('management_id', $management_id)->where('email', $body->email)->first();
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
            'management_type' => ['rules' => 'required'],
            'management_staff_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                helper('common');
                if($body->management_type == 'staff'){
                    $management_id = managementTypeToIdGet($body->management_id);
                }else{
                    $management_id = $body->management_id;
                }

                $db = \Config\Database::connect();
                $managementStaff = $db->table('management_staff');
                
                // Assuming $id contains the ID of the row you want to delete
                $managementStaff->where('management_id', $management_id);
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
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                helper('common');
                if($body->management_type == 'staff'){
                    $management_id = managementTypeToIdGet($body->management_id);
                }else{
                    $management_id = $body->management_id;
                }

                $managementStaff = new ManagementStaff();
                $managementStaff = $managementStaff->where('management_id', $management_id)->get();
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
    public function view()
    {
        $rules = [
            'management_staff_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                $db = \Config\Database::connect();
                $management_login_builder = $db->table('management_login');

                if(!empty($body->formDate) && !empty($body->toDate)){
                    $formDate = date('Y-m-d', strtotime($body->formDate));
                    $toDate = date('Y-m-d', strtotime($body->toDate));
                    $visitor_records_builder->where('management_login.created_at >=', $formDate);
                    $visitor_records_builder->where('management_login.created_at <=', $toDate);
                }
                
                if(!empty($body->week_schedule)){
                    $management_login_builder->select("*,CONCAT(WEEK(management_login.created_at, 1) - WEEK(DATE_SUB(management_login.created_at, INTERVAL DAY(management_login.created_at) - 1 DAY), 1) + 1, ' ', DATE_FORMAT(management_login.created_at, '%b %Y')) AS formatted_week");
                    $management_login_builder->having('formatted_week', $body->week_schedule);
                }
                
                $management_login_builder->where('management_login.staff_id', $body->management_staff_id);
                $management_login_builder->join('management_staff', 'management_login.staff_id = management_staff.id', 'inner');

                $management_login_builder->orderBy('management_login.created_at','DESC');
                $managementLogin = $management_login_builder->get();
                $data = array(); $d=0;
                if ($results = $managementLogin->getResult()) {
                    foreach ($results as $key => $result) {
                        $data[$d]['staff_id'] = $result->staff_id;
                        $data[$d]['name'] = $result->name;
                        $data[$d]['mobile_number'] = $result->mobile_number;
                        $data[$d]['date'] = $result->date;
                        $data[$d]['time_in'] = $result->time_in;
                        $data[$d]['time_out'] = $result->time_out != '00:00:00' ? $result->total_time : '-';
                        $data[$d]['total_time'] = $result->total_time != '' ? $result->total_time : '-';
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
                    helper('common');
                    if($body->management_type == 'staff'){
                        $management_id = managementTypeToIdGet($body->management_id);
                    }else{
                        $management_id = $body->management_id;
                    }

                    $managementStaffs = new ManagementStaff();
                    $managementStaff = $managementStaffs->where('management_id', $management_id)->where('email', $body->email)->first();
                    if (is_null($managementStaff)) {
                        helper('text'); 
                        $qr_key = random_string('alnum', 16);
                        $password = $body->password ?? '123456';
                        $managementStaff = new ManagementStaff();
                        $data = [
                            'management_id' => $management_id,
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
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required']
        ];
        $body = json_decode($this->request->getBody());
        if ($this->validate($rules)) {
            try {
                helper('common');
                if($body->management_type == 'staff'){
                    $management_id = managementTypeToIdGet($body->management_id);
                }else{
                    $management_id = $body->management_id;
                }

                $managementLoginModel = new ManagementLogin();
                $managementLogin = $managementLoginModel->where('staff_id', $management_id)->where('status', '0')->first();
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
    public function history()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                helper('common');
                if($body->management_type == 'staff'){
                    $management_id = managementTypeToIdGet($body->management_id);
                }else{
                    $management_id = $body->management_id;
                }

                $db = \Config\Database::connect();
                $managementBuilder = $db->table('management_staff'); 
                $managementBuilder->where('management_id', $management_id)
                                ->groupBy('id')
                                ->select('id');
                $staffIds = $managementBuilder->get()->getResultArray();
                if (!empty($staffIds)) {
                    $managementLogin = new ManagementLogin();
                    $managementLogin = $managementLogin->whereIn('staff_id', array_column($staffIds, 'id'))->orderBy('created_at','DESC')->get();
                    $data = array(); $d=0;
                    if ($results = $managementLogin->getResult()) {
                        foreach ($results as $key => $result) {
                            $managementStaff = new ManagementStaff();
                            $staffData = $managementStaff->where('id', $result->staff_id)->first();
                            
                            $data[$d]['staff_id'] = $result->staff_id;
                            $data[$d]['name'] = $staffData['name'];
                            $data[$d]['mobile_number'] = $staffData['mobile_number'];
                            $data[$d]['email'] = $staffData['email'];
                            $data[$d]['date'] = $result->date;
                            $data[$d]['time_in'] = $result->time_in;
                            $data[$d]['time_out'] = $result->time_out;
                            $data[$d]['total_time'] = $result->total_time;
                            $data[$d]['created_at'] = $result->created_at;
                            $d++;
                        }
                    }
                    
                    if(sizeof($data) > 0){
                        return $this->respond(['status' => 1, 'message' => 'Staff data', 'data' => $data], 200);
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'No Staff record found ', 'data' => array()], 200);
                    }
                }else{
                    return $this->respond(['status' => 0, 'message' => 'No Staff record found ', 'data' => array()], 200);
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