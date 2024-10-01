<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{Management, ManagementType, ManagementStaff, VisitorRecords, UserVisitor};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class ManagementController extends BaseController
{
    use ResponseTrait;
    
    public function profileUpdate()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required'],
            'title' => ['rules' => 'required'],
            'first_name' => ['rules' => 'required|min_length[3]|max_length[255]'],
            'last_name' => ['rules' => 'required|min_length[3]|max_length[255]'],
            'email' => ['rules' => 'required|min_length[4]|max_length[255]'],
            'mobile_number' => ['rules' => 'required|min_length[10]|max_length[10]']
        ];

        $body = json_decode($this->request->getBody());
        if ($this->validate($rules)) {
            try {
                
                helper('common');
                if($body->management_type == 'staff'){
                    return $this->respond(['status' => 0, 'message' => 'Management Staff not updated'], 200);
                }else{
                    $management_id = $body->management_id;

                    $managementTypeModel = new ManagementType();

                    $management = new Management();
                    $management = $management->where('id NOT LIKE', $management_id)->where('email', $body->email)->first();
                    $data = array();
                    if (is_null($management)) {
                        
                        $management = new Management();
                        $management = $management->where('id NOT LIKE', $management_id)->where('mobile_number', $body->mobile_number)->first();
                        if (is_null($management)) {
                            
                            $db = \Config\Database::connect();
        
                            $management = $db->table('management');
                            $management = $management->where('id', $management_id);
                            
                            if(!empty($body->first_name)){
                                $data['first_name'] = $body->first_name;
                            }
                            if(!empty($body->last_name)){
                                $data['last_name'] = $body->last_name;
                            }
                            if(!empty($body->email)){
                                $data['email'] = $body->email;
                            }
                            if(!empty($body->title)){
                                $data['title'] = $body->title;
                            } 
                            if(!empty($body->mobile_number)){
                                $data['mobile_number'] = $body->mobile_number;
                            } 
                            if (!empty($body->profile_image)) {
                        
                                $base64_image = $body->profile_image;
                                list($type, $data1) = explode(';', $base64_image);
                                list(, $data1) = explode(',', $data1);
                
                                $image_data = base64_decode($data1);
                                $filename = time().uniqid().'.png';
                            
                                
                                $folder = '../public/uploads/management_profile/';
                                
                                if (!file_exists($folder)) {
                                    mkdir($folder, 0777, true);
                                }
                                
                                file_put_contents($folder.$filename, $image_data);
                                $data['profile_image'] = $filename;
                                
                            }

                            if($management->update($data)){

                                $managementModel = new Management();
                                $management = $managementModel->where('id', $management_id)->first();
        
                                $folder = 'uploads/management_profile/';
                                $baseURL = base_url($folder);
                                if (file_exists('../public/' . $folder . $management['profile_image'])) {
                                    $profile_image = $baseURL . $management['profile_image'];
                                } else {
                                    $profile_image = '';
                                }
                                $managementType = $managementTypeModel->where('id', $management['management_type_id'])->select(['id as management_type_id', 'type as management_type'])->first();

                                $data = [
                                    "management_id" => $management_id,
                                    "type" => 'admin',
                                    "unique_key" => $management['unique_key'],
                                    "email" => $management['email'],
                                    "first_name" => $management['first_name'],
                                    "last_name" => $management['last_name'],
                                    "title" => $management['title'],
                                    "mobile_number" => $management['mobile_number'],
                                    "profile_image" => $profile_image,
                                    "management_type" => $managementType,
                                ];
                                
                                return $this->respond(['status' => 1, 'message' => 'Management updated successfully', 'data' => $data], 200);
                            }else{
                                return $this->respond(['status' => 0, 'message' => 'Management not updat.please, try again.'], 200);
                            }
                        }else{
                            return $this->respond(['status' => 0,'message' => 'Management mobile number already exites.'], 200);
                        } 
                    }else{
                        return $this->respond(['status' => 0,'message' => 'Management email already exites.'], 200);
                    } 

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

    public function managementUniqueKey()
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

                $managementModel = new Management();
                $management = $managementModel->where('id', $management_id)->first();
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

                $managementStaff = new ManagementStaff();
                $managementStaff = $managementStaff->where('id', $management_id)->get();
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

    public function managementPin()
    {
        $rules = [
            'management_id' => ['rules' => 'required']
        ];
        $body = json_decode($this->request->getBody());
        helper('text'); 
        if ($this->validate($rules)) {
            try {
                $managementModel = new Management();
                $management = $managementModel->where('id', $body->management_id)->first();
                if (is_null($management)) {
                    return $this->respond(['status' => 0, 'message' => 'Management Not available', 'data' => array()], 200);
                }else{
                    $data['pin'] = $management['pin'];
                    return $this->respond(['status' => 1,'message' => 'Management pin code', 'data' => $data], 200);
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

    public function managementPinUpdate()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'pin' => ['rules' => 'required'],
        ];
        $body = json_decode($this->request->getBody());
        helper('text'); 
        if ($this->validate($rules)) {
            try {
                $db = \Config\Database::connect();
                $management = $db->table('management');
                $management = $management->where('id', $body->management_id);
                $data = [
                    'pin'  => $body->pin
                ];
    
                if($management->update($data)){
                    return $this->respond(['status' => 1,'message' => 'Management pin updated successfully'], 200);
                }else{
                    return $this->respond(['status' => 0, 'message' => 'Management Not available', 'data' => array()], 200);
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
    
    public function visitorAdd()
    {
        $rules = [
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
            'management_type' => ['rules' => 'required'],
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
