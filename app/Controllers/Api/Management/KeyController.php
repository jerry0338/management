<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{Management, ManagementStaff, ManagementKey, VisitorRecords, VisitorRecordKeys};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;



class KeyController extends BaseController
{
    use ResponseTrait;
    
    public function add()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required'],
            'key_id' => ['rules' => 'required'],
            'serial_no' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                helper('text');
                helper('common');
                if($body->management_type == 'staff'){
                    $management_id = managementTypeToIdGet($body->management_id);
                }else{
                    $management_id = $body->management_id;
                }

                $manageData = $this->managementRecordFind($management_id);
                var_dump($manageData);
                $managementKey = new ManagementKey();
                $management = $managementKey->where('management_id', $management_id)->where('key_id', $body->key_id)->first();
    
                if (is_null($management)) {
                    $managementKey = new ManagementKey();
                    $data = [
                        'management_id'  => $management_id,
                        'key_id'         => $body->key_id,
                        'serial_no'       => $body->serial_no,
                        'key_type'       => $body->key_type ?? ''
                    ];
                
                    $managementKey->insert($data);
                    return $this->respond(['status' => 1, 'message' => 'Key register successfully'], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Key already register.'], 200);
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
            'management_key_id' => ['rules' => 'required'],
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required'],
            'key_id' => ['rules' => 'required'],
            'serial_no' => ['rules' => 'required']
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
                $managementKey = new ManagementKey();
                $management = $managementKey->where('id NOT LIKE', $body->management_key_id)->where('management_id', $management_id)->where('key_id', $body->key_id)->first();
                if (is_null($management)) {
                    $db = \Config\Database::connect();

                    $management_Key = $db->table('management_key');
                    $managementKey = $management_Key->where('id', $body->management_key_id);
                    $data = [
                        'key_id'  => $body->key_id,
                        'serial_no'  => $body->serial_no,
                        'key_type'       => $body->key_type ?? ''
                    ];
        
                    if($managementKey->update($data)){
                        return $this->respond(['status' => 1, 'message' => 'Key updated successfully'], 200);
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Key not updat.please, try again.'], 200);
                    }
                    
                }else{
                    return $this->respond(['status' => 0,'message' => 'Key already register.'], 200);
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
            'management_key_id' => ['rules' => 'required']
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
                $recordsCheck = new VisitorRecords();
                $recordsCheck = $recordsCheck->where('management_key_id', $body->management_key_id)->where('management_id', $management_id)->first();
                if(!$recordsCheck){
                            
                    $db = \Config\Database::connect();
                    $managementKey = $db->table('management_key');
                    
                    // Assuming $id contains the ID of the row you want to delete
                    $managementKey->where('management_id', $management_id);
                    $managementKey->where('id', $body->management_key_id);
                    if ($managementKey->delete()){
                        return $this->respond(['status' => 1, 'message' => 'Key deleted.'], 200);
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Key not delete.please, try again.'], 200);
                    }
                }else{
                    return $this->respond(['status' => 0, 'message' => 'Key alreay assign.please, try again.'], 200);
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
                helper('text');
                helper('common');
                if($body->management_type == 'staff'){
                    $management_id = managementTypeToIdGet($body->management_id);
                }else{
                    $management_id = $body->management_id;
                }

                $managementKey = new ManagementKey();
                $managementKey = $managementKey->where('management_id', $management_id)->get();
                $data = array(); $d=0;
                if ($results = $managementKey->getResult()) {
                    foreach ($results as $key => $result) {
                        $data[$d]['management_key_id'] = $result->id;
                        $data[$d]['key_id'] = $result->key_id;
                        $data[$d]['serial_no'] = $result->serial_no;
                        $data[$d]['key_type'] = $result->key_type;
                        
                        $visitorRecordKeys = new VisitorRecordKeys();
                        
                        $visitorRecordKeysData = $visitorRecordKeys->select('visitor_record_keys.*, visitors.first_name, visitors.last_name, visitors.company_name, visitor_type.type')->join('visitor_records', 'visitor_records.id = visitor_record_keys.records_id')->join('visitors', 'visitors.id = visitor_records.visitor_id')->join('visitor_type', 'visitor_type.id = visitors.visitor_type_id')->where('visitor_record_keys.management_key_id', $result->id)->where('visitor_record_keys.status', 0)->first();
                
                        if($visitorRecordKeysData){
                            $data[$d]['key_loan'] = true;
                            
                            $data[$d]['person_type'] = $visitorRecordKeysData['type'];
                            $data[$d]['name'] = $visitorRecordKeysData['first_name'].' '.$visitorRecordKeysData['last_name'];
                            $data[$d]['company'] = $visitorRecordKeysData['company_name'];
                            
                            $loan_period = date_add(date_create($visitorRecordKeysData['created_at']), date_interval_create_from_date_string($visitorRecordKeysData['loan_period']));
                            $data[$d]['date_out'] = date_format($loan_period, 'd/m/Y');
                            $data[$d]['time_out'] = date_format($loan_period, 'h:ia');
                            $data[$d]['overdue'] = 'yes';
                            $data[$d]['loan_length'] =  $visitorRecordKeysData['loan_period'];
                        }else{
                            $data[$d]['key_loan'] = false;
                            
                            $data[$d]['person_type'] = 'n/a';
                            $data[$d]['name'] = 'n/a';
                            $data[$d]['company'] = 'n/a';
                            $data[$d]['date_out'] = 'n/a';
                            $data[$d]['time_out'] = 'n/a';
                            $data[$d]['overdue'] = 'n/a';
                            $data[$d]['loan_length'] = 'n/a';
                        }
                        $data[$d]['created_at'] = $result->created_at;
                        
                        $d++;
                    }
                }
                
                if(sizeof($data) > 0){
                    return $this->respond(['status' => 1, 'message' => 'Key data', 'data' => $data], 200);
                }else{
                    return $this->respond(['status' => 0, 'message' => 'No Key data found ', 'data' => array()], 200);
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
    
    public function list1()
    {
        $rules = [
            'management_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                helper('text');
                $managementKey = new ManagementKey();
                
                $managementKey = $managementKey
                                ->select('management_key.*, visitor_record_keys.*, visitors.first_name, visitors.last_name, visitors.company_name, visitor_type.type')
                                ->join('visitor_record_keys', 'visitor_record_keys.management_key_id = management_key.id')
                                ->join('visitor_records', 'visitor_records.id = visitor_record_keys.records_id')
                                ->join('visitors', 'visitors.id = visitor_records.visitor_id')
                                ->join('visitor_type', 'visitor_type.id = visitors.visitor_type_id')
                                ->where('management_key.management_id', $body->management_id);
                                    
                if(!empty($body->searchKeyword)){
                    $conditions = "(key_id LIKE '%" . $body->searchKeyword . "%' OR serial_no LIKE '%" . $body->searchKeyword . "%' OR key_type LIKE '%" . $body->searchKeyword . "%')";
                    $managementKey = $managementKey->where($conditions);
                }
                
                $managementKey = $managementKey->get();
                if ($results = $managementKey->getResult()) {
                    $data = array(); $d=0;
                    var_dump($results);
                    foreach ($results as $key => $result) {
                        
                        
                        
                        if(!empty($body->filterPersonType) || !empty($body->filterName) || !empty($body->filterPersonType)){
                        
                            
                        }
                        // $data[$d]['management_key_id'] = $result->id;
                        // $data[$d]['key_id'] = $result->key_id;
                        // $data[$d]['serial_no'] = $result->serial_no;
                        // $data[$d]['key_type'] = $result->key_type;
                        
                        $visitorRecordKeys = new VisitorRecordKeys();
                        
                        $visitorRecordKeysData = $visitorRecordKeys->select('visitor_record_keys.*, visitors.first_name, visitors.last_name, visitors.company_name, visitor_type.type')->join('visitor_records', 'visitor_records.id = visitor_record_keys.records_id')->join('visitors', 'visitors.id = visitor_records.visitor_id')->join('visitor_type', 'visitor_type.id = visitors.visitor_type_id')->where('visitor_record_keys.management_key_id', $result->id)->where('visitor_record_keys.status', 0)->first();
                
                        if($visitorRecordKeysData){
                            $data[$d]['key_loan'] = true;
                            
                            $data[$d]['person_type'] = $visitorRecordKeysData['type'];
                            $data[$d]['name'] = $visitorRecordKeysData['first_name'].' '.$visitorRecordKeysData['last_name'];
                            $data[$d]['company'] = $visitorRecordKeysData['company_name'];
                            
                            $loan_period = date_add(date_create($visitorRecordKeysData['created_at']), date_interval_create_from_date_string($visitorRecordKeysData['loan_period']));
                            $data[$d]['date_out'] = date_format($loan_period, 'd/m/Y');
                            $data[$d]['time_out'] = date_format($loan_period, 'h:ia');
                            
                            $date = strtotime(date_format($loan_period, 'Y-m-d H:i:s'));
                            if ($date < time()) {
                                $data[$d]['overdue'] = 'yes';
                            }else{
                                $data[$d]['overdue'] =  'no';
                            }
    
                            $data[$d]['loan_length'] =  $visitorRecordKeysData['loan_period'];
                        }else{
                            $data[$d]['key_loan'] = false;
                            
                            $data[$d]['person_type'] = 'n/a';
                            $data[$d]['name'] = 'n/a';
                            $data[$d]['company'] = 'n/a';
                            $data[$d]['date_out'] = 'n/a';
                            $data[$d]['time_out'] = 'n/a';
                            $data[$d]['overdue'] = 'n/a';
                            $data[$d]['loan_length'] = 'n/a';
                        }
                        // $data[$d]['created_at'] = $result->created_at;
                        
                        $d++;
                    }
                }
                
                if(sizeof($data) > 0){
                    return $this->respond(['status' => 1, 'message' => 'Key data', 'data' => $data], 200);
                }else{
                    return $this->respond(['status' => 0, 'message' => 'No Key data found ', 'data' => array()], 200);
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
            'management_key_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                helper('text');
                
                $visitorRecordKeys = new VisitorRecordKeys();
                $visitorRecordKeys->select('visitor_record_keys.*, visitors.first_name, visitors.last_name, visitors.company_name, visitor_type.type')->join('visitor_records', 'visitor_records.id = visitor_record_keys.records_id')->join('visitors', 'visitors.id = visitor_records.visitor_id')->join('visitor_type', 'visitor_type.id = visitors.visitor_type_id');

                if(!empty($body->visitor_type_id)){
                    $visitorRecordKeys->where('visitor_type.id', $body->visitor_type_id);
                }
                if(!empty($body->name)){
                    $conditions = "(visitors.first_name LIKE '%" . $body->name . "%' OR visitors.last_name LIKE '%" . $body->name . "%')";
                    $visitorRecordKeys->where($conditions);
                }
                if(!empty($body->company_name)){
                    $visitorRecordKeys->where('visitors.company_name', $body->company_name);
                }
                
                $visitorRecordKeys->where('visitor_record_keys.management_key_id', $body->management_key_id);
                
                $visitorRecordKeysData = $visitorRecordKeys->orderBy('created_at','DESC')->get();

                $data = array(); $d=0;
                if ($results = $visitorRecordKeysData->getResult()) {
                    foreach ($results as $key => $result) {
                        
                        $managementKey = new ManagementKey();
                        
                        if(!empty($body->serial_no)){
                            $managementKey = $managementKey->like('serial_no', '%'.$body->serial_no.'%')->where('id', $body->management_key_id)->first();
                        }else{
                            $managementKey = $managementKey->where('id', $body->management_key_id)->first();
                        }
                        
                        if($managementKey){
                
                            $data[$d]['management_key_id'] = $managementKey['id'];
                            $data[$d]['key_id'] = $managementKey['key_id'];
                            $data[$d]['serial_no'] = $managementKey['serial_no'];
                            $data[$d]['key_type'] = $managementKey['key_type'];
                            
                            if($result->status == 1){
                                $data[$d]['key_loan'] = false;
                            }else{
                                $data[$d]['key_loan'] = true;
                            }
                                
                            $data[$d]['person_type'] = $result->type;
                            $data[$d]['name'] = $result->first_name.' '.$result->last_name;
                            $data[$d]['company'] = $result->company_name;
                            
                            $loan_period = date_create($result->created_at);
                            $data[$d]['date_out'] = date_format($loan_period, 'd/m/Y');
                            $data[$d]['time_out'] = date_format($loan_period, 'h:ia');
                            
                            $loan_period = date_create($result->updated_at);
                            $data[$d]['return_date'] = date_format($loan_period, 'd/m/Y');
                            $data[$d]['return_time'] = date_format($loan_period, 'h:ia');
                        
                            $data[$d]['loan_length'] =  $result->loan_period;
                            $d++;
                        }
                    }
                    
                }
                    
                if(sizeof($data) > 0){
                    return $this->respond(['status' => 1, 'message' => 'Key data', 'data' => $data], 200);
                }else{
                    return $this->respond(['status' => 0, 'message' => 'No Key data found ', 'data' => array()], 200);
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
    
    public function assign()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required'],
            'key_id' => ['rules' => 'required'],
            'loan_period' => ['rules' => 'required'],
            'visitor_id' => ['rules' => 'required']
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

                $visitorRecordsCheck = new VisitorRecords();
                $visitorRecordsCheck = $visitorRecordsCheck->where('visitor_id', $body->visitor_id)->where('management_id', $management_id)->where('purpose_entry', 'LOG-IN')->first();
                if($visitorRecordsCheck){
                  
                    $managementKey = new ManagementKey();
                    $managementKeyData = $managementKey->where('management_id', $management_id)->where('key_id', $body->key_id)->first();
                    
                    if($managementKeyData){
                        
                        $visitorRecordKeys = new VisitorRecordKeys();
                        $visitorRecordKeysData = $visitorRecordKeys->where('management_key_id', $managementKeyData['id'])->where('status', 0)->first();
                
                        if($visitorRecordKeysData){
                            return $this->respond(['status' => 0, 'message' => 'Key already assign. please, try again.'], 200);
                        }else{
                            
                            $visitorRecordKeysAdd = new VisitorRecordKeys();
                            $data = [
                                'records_id'   => $visitorRecordsCheck['id'],
                                'management_key_id' => $managementKeyData['id'],
                                'key_id'       => $body->key_id,
                                'loan_period'  => $body->loan_period,
                                'status'       => 0
                            ];
                
                            if($visitorRecordKeysAdd->insert($data)){
                                return $this->respond(['status' => 1, 'message' => 'Key Assign succesfully.'], 200);
                            }else{
                                return $this->respond(['status' => 0, 'message' => 'Key not assign.please, try again.'], 200);
                            }
                        }
                        
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Enter key id not found.please, try again.'], 200);
                    }
                }else{
                    return $this->respond(['status' => 2, 'message' => 'Visitor not found.'], 200);
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
    
    public function return()
    {
        $rules = [
            'management_key_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
             
                $db = \Config\Database::connect();
                
                $visitorRecordKeys = new VisitorRecordKeys();
                $visitorRecordKeysCheck = $visitorRecordKeys->where('management_key_id', $body->management_key_id)->where('status', 0)->first();
                if($visitorRecordKeysCheck){
                
                    $visitorRecordKeys = $db->table('visitor_record_keys');
                    $visitorRecordKeysData = $visitorRecordKeys->where('id', $visitorRecordKeysCheck['id']);
                    $data = array('status' => 1);
                    if($visitorRecordKeysData->update($data)){
                        return $this->respond(['status' => 1, 'message' => 'Key Return succesfully.'], 200);
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Key not return.please, try again.'], 200);
                    }
                }else{
                    return $this->respond(['status' => 2, 'message' => 'Key record not found.'], 200);
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
    
    public function checkout()
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
                if(!empty($body->management_key_id)){
                    $management_key_id = $body->management_key_id;
                }else if(!empty($body->key_id)){
                    $managementKey = new ManagementKey();
                    $managementKeyData = $managementKey->where('management_id', $management_id)->where('key_id', $body->key_id)->first();
                    if($managementKeyData){
                        $management_key_id = $managementKeyData['id'];
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Enter key id not found.please, try again.'], 200);
                    }
                }else if(!empty($body->serial_no)){
                    $managementKey = new ManagementKey();
                    $managementKeyData = $managementKey->where('management_id', $management_id)->where('serial_no', $body->serial_no)->first();
                    if($managementKeyData){
                        $management_key_id = $managementKeyData['id'];
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Enter key id not found.please, try again.'], 200);
                    }
                }
                
                $visitorRecordKeys = new VisitorRecordKeys();
                $visitorRecordKeysCheck = $visitorRecordKeys->where('management_key_id', $management_key_id)->where('status', '0')->first();
                if($visitorRecordKeysCheck){
                
                    $visitorRecords = $db->table('visitor_records');
                    $visitorRecordsData = $visitorRecords->where('id', $visitorRecordKeysCheck['records_id']);
                    $data = array('purpose_entry' => 'LOG-IN-OUT');
                    if($visitorRecordsData->update($data)){
                        
                        $visitorRecordKeys = $db->table('visitor_record_keys');
                        $visitorRecordKeysData = $visitorRecordKeys->where('records_id', $visitorRecordKeysCheck['records_id']);
                        $data = array('status' => 1);
                        $visitorRecordKeysData->update($data);
                    
                        return $this->respond(['status' => 1, 'message' => 'Visitor Checkout succesfully.'], 200);
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Visitor not checkout.please, try again.'], 200);
                    }
                }else{
                    return $this->respond(['status' => 2, 'message' => 'Visitor not found.'], 200);
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
    private function managementRecordFind(){
        
        $managementModel = new Management();

    }

}