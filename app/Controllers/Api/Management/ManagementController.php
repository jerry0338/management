<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{Management, ManagementStaff, VisitorRecords, UserVisitor};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class ManagementController extends BaseController
{
    use ResponseTrait;
    
    public function managementUniqueKey()
    {
        $rules = [
            'management_id' => ['rules' => 'required']
        ];
        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                $managementModel = new Management();
                $management = $managementModel->where('id', $body->management_id)->first();
                if (is_null($management)) {
                    return $this->respond(['status' => 0, 'message' => 'Management Not available', 'data' => array()], 200);
                }else{
                    $data['unique_key'] = $management['unique_key'];
                    return $this->respond(['status' => 1,'message' => 'Management unique code', 'data' => $data], 200);
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
    
    public function managementUniqueKeyToId()
    {
        $rules = [
            'unique_key' => ['rules' => 'required']
        ];
        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                $managementModel = new Management();
                $management = $managementModel->where('unique_key', $body->unique_key)->first();
                if (is_null($management)) {
                    return $this->respond(['status' => 0, 'message' => 'Management Not available', 'data' => array()], 200);
                }else{
                    $data['management_id'] = $management['id'];
                    return $this->respond(['status' => 1,'message' => 'Management id', 'data' => $data], 200);
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
    
    public function managementPerson()
    {
        $rules = [
            'management_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                helper('text');
                $managementStaff = new ManagementStaff();
                $managementStaff = $managementStaff->where('id', $body->management_id)->get();
                $data = array(); $d=0;
                if ($results = $managementStaff->getResult()) {
                    foreach ($results as $key => $result) {
                        $data[$d]['person_id'] = $result->id;
                        $data[$d]['name'] = $result->name;
                        $data[$d]['mobile_number'] = $result->mobile_number;
                        $data[$d]['email'] = $result->email;
                        $d++;
                    }
                }
                $data[$d]['person_id'] = 0;
                $data[$d]['name'] = 'others';
                $data[$d]['mobile_number'] = '';
                $data[$d]['email'] = '';
                return $this->respond(['status' => 1, 'message' => 'Retrieved', 'data' => $data], 200);
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
    
    public function visitorAdd()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'visitor_id' => ['rules' => 'required'],
            'person_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                helper('text');
                
                $visitorRecordsCheck = new VisitorRecords();
                $visitorRecordsCheck = $visitorRecordsCheck->where('visitor_id', $body->visitor_id)->where('purpose_entry', 'LOG-IN')->first();
                if($visitorRecordsCheck){
                    $db = \Config\Database::connect();
    
                    $visitor_builder = $db->table('visitor_records');
                    $visitorRecords = $visitor_builder->where('id', $visitorRecordsCheck['id']);
                    $data = [
                        'person_id' => $body->person_id,
                        'purpose'   => $body->purpose ?? ''
                    ];
        
                    if($visitorRecords->update($data)){
                         return $this->respond(['status' => 1, 'message' => 'Visitor Entery listed'], 200);
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Visitor entry not listed.please, try again.'], 200);
                    }
                }else{
                    return $this->respond(['status' => 0, 'message' => 'Visitor not login.'], 200);
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
    
    public function visitorOut()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'visitor_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                $visitorRecordsCheck = new VisitorRecords();
                $visitorRecordsCheck = $visitorRecordsCheck->where('visitor_id', $body->visitor_id)->where('management_id', $body->management_id)->where('purpose_entry', 'LOG-IN')->first();
                if($visitorRecordsCheck){
                    
                    if($visitorRecordsCheck['management_key_id'] == 0){
                        $db = \Config\Database::connect();
    
                        $visitor_builder = $db->table('visitor_records');
                        $visitorRecords = $visitor_builder->where('id', $visitorRecordsCheck['id']);
                        $data = [
                            'purpose_entry'  => 'LOG-IN-OUT'
                        ];
            
                        if($visitorRecords->update($data)){
                            return $this->respond(['status' => 1, 'message' => 'Visitor Exites succesfully.'], 200);
                        }else{
                            return $this->respond(['status' => 0, 'message' => 'Visitor not exites.please, try again.'], 200);
                        }
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Key not return.please, try again.'], 200);
                    }
                }else{
                    return $this->respond(['status' => 2, 'message' => 'Visitor not login.'], 200);
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
