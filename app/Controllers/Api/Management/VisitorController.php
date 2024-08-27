<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{Visitor, VisitorRecords, UserVisitor, VisitorRecordKeys};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class VisitorController extends BaseController
{
    use ResponseTrait;
    
    public function add()
    {
        $rules = [
            'state_id' => ['rules' => 'required'],
            'visitor_type_id' => ['rules' => 'required'],
            'first_name' => ['rules' => 'required|min_length[3]|max_length[255]'],
            'last_name' => ['rules' => 'required|min_length[3]|max_length[255]'],
            'company_name' => ['rules' => 'permit_empty|min_length[3]|max_length[255]'],
            'mobile_number' => ['rules' => 'required|min_length[10]|max_length[10]'],
            'wwcc' => ['rules' => 'permit_empty|min_length[3]|max_length[255]'],
            'email' => ['rules' => 'required|min_length[4]|max_length[255]|valid_email|is_unique[visitors.email]'],
            'is_covid_or_sickness' => ['rules' => 'permit_empty|in_list[0,1]'],
            'location_service' => ['rules' => 'permit_empty|in_list[0,1]'],
            'visitor_person' => ['rules' => 'permit_empty'],
            'management_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {

            helper('text');

            $VisitorModel = new Visitor();

            $img_name = null;

            if ($this->request->getFile('photo')) {
                $img = $this->request->getFile('photo');
                $img_name = time() . '.' . $img->getClientExtension();
                $img->move('../public/uploads', $img_name);
            }

            $unique_key = random_string('alnum', 16);

            $data = [
                'state_id'              => $body->state_id,
                'visitor_type_id'       => $body->visitor_type_id,
                'visitor_person'        => $body->visitor_person ?? '',
                'first_name'            => $body->first_name,
                'last_name'             => $body->last_name,
                'company_name'          => $body->company_name ?? '',
                'mobile_number'         => $body->mobile_number,
                'wwcc'                  => $body->wwcc ?? '',
                'photo'                 => $img_name,
                'email'                 => $body->email,
                'is_covid_or_sickness'  => $body->is_covid_or_sickness ?? '',
                'location_service'      => $body->location_service ?? '',
                'unique_key'            => $unique_key
            ];

            $visitor_id = $VisitorModel->insert($data);

            return $this->respond(['status' => 1,'message' => 'Visitor has register.', 'data' => [
                'visitor_id'        => $visitor_id,
                'first_name'        => $body->first_name,
                'last_name'         => $body->last_name,
                'email'             => $body->email,
                'unique_key'         => $unique_key,
            ]], 200);
        } else {
            $response = [
                'status' => 0,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->fail($response, 409);
        }
    }
    
    public function search()
    {
        $rules = [
            'management_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            
            
            try {
                $db = \Config\Database::connect();
    
                $visitor_builder = $db->table('visitors');
                
                if(!empty($body->mobile_number) || !empty($body->name)){
                    if(!empty($body->mobile_number)){
                        $visitor_builder->like('mobile_number', '%'.$body->mobile_number.'%');
                    }
                    
                    if(!empty($body->name)){
                        $name = $body->name ? explode(" ", $body->name) : [];
                        if (isset($name[0])) {
                            $visitor_builder->like('first_name', '%'.$name[0].'%');
                        }
                        if (isset($name[1])) {
                            $visitor_builder->like('last_name', '%'.$name[1].'%');
                        }
                    }
                    $get_visitor = $visitor_builder->orderBy('created_at','DESC')->limit(1)->get();
                    
                    if ($results = $get_visitor->getResult()) {
                        $data = array(); $d=0;
                        foreach ($results as $key => $result) {
                            $data[$d]['visitor_id'] = $result->id;
                            $data[$d]['first_name'] = $result->first_name;
                            $data[$d]['last_name'] = $result->last_name;
                            $data[$d]['email'] = $result->email;
                            $data[$d]['mobile_number'] = $result->mobile_number;
                            
                            $visitorRecord = $db->table('visitor_records');
                            $visitorRecord->where('visitor_id', $result->id);
                            $visitorRecord->where('management_id', $body->management_id);
                            $visitorRecord->orderBy('created_at','DESC');
                            $visitorRecord->limit(1);
                            $getVisitorRecord = $visitorRecord->get();
                            $getVisitorRecord = $getVisitorRecord->getFirstRow();
                            $person_id = 0; $person_name = "others";
                            if($getVisitorRecord){
                                $person_id = $getVisitorRecord->person_id;
                                
                                $person = $db->table('');
                                $person->where('id', $person_id);
                                $person->limit(1);
                                $getPerson = $person->get();
                                $getPerson = $getPerson->getFirstRow();
                    
                                if($getPerson){
                                    $person_name = $getPerson->name;
                                }
                            }
                            $data[$d]['person_id'] = $person_id;
                            $data[$d]['person_name'] = $person_name;
                        
                            
                            $d++;
                        }
                        return $this->respond(['status' => 1, 'message' => 'Retrieved', 'data' => $data], 200);
                    }else{
                          return $this->respond(['status' => 0,'message' => 'Data not found!', 'data' => array()], 200);
                    }
                }else{
                    return $this->respond(['status' => 0,'message' => 'Data not found!', 'data' => array()], 200);
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
    
    public function records()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'type' => ['rules' => 'required'],
            'page' => ['rules' => 'required']
        ];
        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            $page = $body->page;
            $limit = 10;
            $start = $page * $limit - $limit;
            try {
                $db = \Config\Database::connect();
    
                $visitor_builder = $db->table('visitor_records');
                    
                if($body->type == 'active'){
                    $visitor_builder->where('purpose_entry', 'LOG-IN');
                }else{
                    $visitor_builder->where('purpose_entry', 'LOG-IN-OUT');
                }
                $visitor_builder->where('management_id', $body->management_id);
                $visitor_builder->limit($limit, $start);
                $get_visitor = $visitor_builder->orderBy('created_at','DESC')->get();
                if ($results = $get_visitor->getResult()) {
                    $data = array(); $d=0;
                    foreach ($results as $key => $result) {
                        
                        $visitor = $db->table('visitors');
                        $visitor->where('id', $result->visitor_id);
                        $visitor->limit(1);
                        $visitor = $visitor->get();
                        $visitorRecords = $visitor->getFirstRow();
                        
                        $data[$d]['visitor_records_id'] = $result->id;
                        $data[$d]['visitor_id'] = $visitorRecords->id;
                        $data[$d]['visitor_type_id'] = $visitorRecords->visitor_type_id;
                        
                        $visitor_type = $db->table('visitor_type');
                        $visitor_type->where('id', $visitorRecords->visitor_type_id);
                        $visitor_type->limit(1);
                        $visitor_type = $visitor_type->get();
                        $visitorTypeRecords = $visitor_type->getFirstRow();
                        $data[$d]['type_name'] = $visitorTypeRecords->type;
                        
                        $person_id = 0; $person_name = "others";   
                        $person = $db->table('management_staff');
                        $person->where('id', $result->person_id);
                        $person->limit(1);
                        $getPerson = $person->get();
                        $getPerson = $getPerson->getFirstRow();
                        if($getPerson){
                            $person_id = $getPerson->id;
                            $person_name = $getPerson->name;
                        }
                            
                        $data[$d]['person_id'] = $person_id;
                        $data[$d]['person_name'] = $person_name;
                        
                        $data[$d]['first_name'] = $visitorRecords->first_name;
                        $data[$d]['last_name'] = $visitorRecords->last_name;
                        $data[$d]['company_name'] = $visitorRecords->company_name;
                        $data[$d]['email'] = $visitorRecords->email;
                        $data[$d]['mobile_number'] = $visitorRecords->mobile_number;
                        
                        $visitorRecordKeys = new VisitorRecordKeys();
                        $visitorRecordKeysDatas = $visitorRecordKeys->where('records_id', $result->id)->where('status', 0)->get();
                        $recordKeys = array(); $r=0;
                        if ($visitorRecordKeysDatas = $visitorRecordKeysDatas->getResult()) {
                            foreach($visitorRecordKeysDatas as $visitorRecordKeysData){
                                $recordKeys[$r]['management_key_id'] = $visitorRecordKeysData->management_key_id;
                                $recordKeys[$r]['key_id'] = $visitorRecordKeysData->key_id;
                                $recordKeys[$r]['loan_period'] = $visitorRecordKeysData->loan_period;
                                $recordKeys[$r]['status'] = $visitorRecordKeysData->status;
                                $r++;
                            }
                        }
                        
                        $data[$d]['visitor_record_keys'] = $recordKeys;
                        
                        $data[$d]['created_at'] = $result->created_at;
                        $data[$d]['complate_at'] = $result->complate_at;
                        
                        $d++;
                    }
                    return $this->respond(['status' => 1, 'message' => 'Retrieved', 'data' => $data], 200);
                }else{
                      return $this->respond(['status' => 0,'message' => 'Data not found!', 'data' => array()], 200);
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
    
    public function checkoutNumber()
    {
        $rules = [
            'management_id' => ['rules' => 'required']
        ];
        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            
            try {
                $db = \Config\Database::connect();
                if(!empty($body->mobile_number)){
                    $mobile_number = $body->mobile_number;
                    
                    $visitorCheck = new Visitor();
                    $visitorRecordsCheck = $visitorCheck->where('mobile_number', $mobile_number)->first();
                }else if(!empty($body->unique_key)){
                    $unique_key = $body->unique_key;
                    
                    $visitorCheck = new Visitor();
                    $visitorRecordsCheck = $visitorCheck->where('unique_key', $unique_key)->first();
                }else{
                    return $this->respond(['status' => 0, 'message' => 'Visitor not found.'], 200);
                }
                if($visitorRecordsCheck){
                    $visitor_id = $visitorRecordsCheck['id'];
                    $visitor_name = $visitorRecordsCheck['first_name'].' '.$visitorRecordsCheck['last_name'];
                    
                    $visitorRecords = new VisitorRecords();
                    $visitorRecords = $visitorRecords->where('visitor_id ', $visitor_id)->where('management_id ', $body->management_id)->where('purpose_entry', 'LOG-IN')->first();
                    if($visitorRecords){
                        
                        $visitorRecordKeys = new VisitorRecordKeys();
                        $visitorRecordKeysCheck = $visitorRecordKeys->where('records_id ', $visitorRecords['id'])->where('status ', '0')->first();
                        if($visitorRecordKeysCheck && $body->permission == '0'){
                            return $this->respond(['status' => 1, 'message' => 'Visitor Checkout succesfully.', 'data' => $visitor_name], 200);
                        }
                    
                        $visitorRecords1 = $db->table('visitor_records');
                        $visitorRecordsData = $visitorRecords1->where('id', $visitorRecords['id']);
                        $data = array('purpose_entry' => 'LOG-IN-OUT');
                        if($visitorRecordsData->update($data)){
                            
                            if($visitorRecordKeysCheck){
                                $visitorRecordKeys = $db->table('visitor_record_keys');
                                $visitorRecordKeysData = $visitorRecordKeys->where('records_id', $visitorRecordKeysCheck['records_id']);
                                $data = array('status' => 1);
                                $visitorRecordKeysData->update($data);
                            }
                        
                            return $this->respond(['status' => 2, 'message' => 'Visitor Checkout succesfully.'], 200);
                        }else{
                            return $this->respond(['status' => 1, 'message' => 'Visitor not checkout.please, try again.'], 200);
                        }
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Visitor not found.'], 200);
                    }
                
                }else{
                    return $this->respond(['status' => 0, 'message' => 'Visitor not found.'], 200);
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

    public function checkoutKey()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_key_id' => ['rules' => 'required']
        ];
        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            
            try {
                $db = \Config\Database::connect();
                $management_key_id = $body->management_key_id;
                
                $visitorRecordKeys = new VisitorRecordKeys();
                $visitorRecordKeysCheck = $visitorRecordKeys->where('management_key_id', $management_key_id)->where('status', '0')->first();
                if($visitorRecordKeysCheck && $body->permission == '0'){
                    $records_id = $visitorRecordKeysCheck['records_id'];
                    
                    $visitorRecords = new VisitorRecords();
                    $visitorRecords = $visitorRecords->where('id', $records_id)->first();
                    
                    $visitor_id = $visitorRecords['visitor_id'];
                    $visitor = new Visitor();
                    $visitor = $visitor->where('id', $visitor_id)->first();
                    $visitor_name = $visitor['first_name'].' '.$visitor['last_name'];
                    
                    return $this->respond(['status' => 1, 'message' => 'Visitor Checkout succesfully.', 'data' => $visitor_name], 200);
                }else if($visitorRecordKeysCheck){
                
                    $visitorRecords = $db->table('visitor_records');
                    $visitorRecordsData = $visitorRecords->where('id', $visitorRecordKeysCheck['records_id']);
                    $data = array('purpose_entry' => 'LOG-IN-OUT');
                    if($visitorRecordsData->update($data)){
                        
                        $visitorRecordKeys = $db->table('visitor_record_keys');
                        $visitorRecordKeysData = $visitorRecordKeys->where('records_id', $visitorRecordKeysCheck['records_id']);
                        $data = array('status' => 1);
                        $visitorRecordKeysData->update($data);
                    
                        return $this->respond(['status' => 2, 'message' => 'Visitor Checkout succesfully.'], 200);
                    }else{
                        return $this->respond(['status' => 1, 'message' => 'Visitor not checkout.please, try again.'], 200);
                    }
                }else{
                    return $this->respond(['status' => 0, 'message' => 'Visitor not found.'], 200);
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