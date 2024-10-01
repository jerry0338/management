<?php

namespace App\Controllers\Api\Visitor;

use App\Controllers\BaseController;

use App\Models\{Visitor, Management, ManagementKey, VisitorRecords, VisitorRecordKeys};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class VisitorController extends BaseController
{
    use ResponseTrait;
    
    public function profileUpdate()
    {
        $rules = [
            'visitor_id' => ['rules' => 'required'],
            'first_name' => ['rules' => 'required|min_length[3]|max_length[255]'],
            'last_name' => ['rules' => 'required|min_length[3]|max_length[255]'],
            'email' => ['rules' => 'required|min_length[4]|max_length[255]'],
            'mobile_number' => ['rules' => 'required|min_length[10]|max_length[10]'],
            'visitor_type_id' => ['rules' => 'required']
        ];
        $body = json_decode($this->request->getBody());
        if ($this->validate($rules)) {
            try {
                
                $visitor = new Visitor();
                $visitor = $visitor->where('id NOT LIKE', $body->visitor_id)->where('email', $body->email)->first();
                // ->orwhere('mobile_number', $body->)
                $data = array();
                if (is_null($visitor)) {
                    
                    $visitor = new Visitor();
                    $visitor = $visitor->where('id NOT LIKE', $body->visitor_id)->where('mobile_number', $body->mobile_number)->first();
                    if (is_null($visitor)) {
                        
                        $db = \Config\Database::connect();
    
                        $visitors = $db->table('visitors');
                        $visitors = $visitors->where('id', $body->visitor_id);
                        
                        if(!empty($body->first_name)){
                            $data['first_name'] = $body->first_name;
                        }
                        if(!empty($body->last_name)){
                            $data['last_name'] = $body->last_name;
                        }
                        if(!empty($body->email)){
                            $data['email'] = $body->email;
                        }
                        if(!empty($body->mobile_number)){
                            $data['mobile_number'] = $body->mobile_number;
                        }
                        if(!empty($body->password)){
                            $data['password'] = password_hash($body->password, PASSWORD_DEFAULT);
                        }
                        
                        if(!empty($body->visitor_type_id)){
                            $data['visitor_type_id'] = $body->visitor_type_id;
                        }
                        if (!empty($body->profile_image)) {
                    
                            $base64_image = $body->profile_image;
                            list($type, $data1) = explode(';', $base64_image);
                            list(, $data1) = explode(',', $data1);
            
                            $image_data = base64_decode($data1);
                            $filename = time().uniqid().'.png';
                           
                            
                            $folder = '../public/uploads/visitor_profile/';
                            
                            if (!file_exists($folder)) {
                                mkdir($folder, 0777, true);
                            }
                            
                            file_put_contents($folder.$filename, $image_data);
                             $data['photo'] = $filename;
                            
                        }
            
                        if($visitors->update($data)){
                            $VisitorModel = new Visitor();
                            $data = $VisitorModel->select('id as visitor_id, unique_key, visitor_type_id, visitor_person, state_id, first_name, last_name, email, company_name, wwcc, mobile_number, photo, location_service, is_covid_or_sickness, latitude, longitude')->where('id', $body->visitor_id)->first();

                                if(!empty($data['photo'])){
                                    $data['photo'] = base_url('uploads/visitor_profile/'.$data['photo']);
                                }else{
                                    $data['photo'] = base_url('uploads/visitor_profile/profile.jpeg');
                                }
                            
                            return $this->respond(['status' => 1, 'message' => 'Visitor updated successfully', 'data' => $data], 200);
                        }else{
                            return $this->respond(['status' => 0, 'message' => 'Visitor not updat.please, try again.'], 200);
                        }
                    }else{
                        return $this->respond(['status' => 0,'message' => 'Visitor mobile number already exites.'], 200);
                    } 
                }else{
                    return $this->respond(['status' => 0,'message' => 'Visitor email already exites.'], 200);
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

    public function qrCheck()
    {
        $rules = [
            'visitor_id' => ['rules' => 'required'],
            'unique_key' => ['rules' => 'required'],
        ];

        $body = json_decode($this->request->getBody());
        if ($this->validate($rules)) {
            
            try {
                
                $managementModel = new Management();
                $management = $managementModel->where('unique_key', $body->unique_key)->first();
                
                if($management){
                    $db = \Config\Database::connect();
                        
                    $visitorRecords = $db->table('visitor_records');
                    $visitorRecords->where('visitor_id', $body->visitor_id);
                    $visitorRecords->where('management_id', $management['id']);
                    $visitorRecords->orderBy('created_at','DESC');
                    $visitorRecords->limit(1);
                    $visitorRecords = $visitorRecords->get();
                    $getVisitorRecords = $visitorRecords->getFirstRow();
                    $records = '';
                    if($getVisitorRecords){
                        $records = $getVisitorRecords->purpose;
                    }
                    $data['management_id'] = $management['id'];
                    $data['title'] = $management['title'];
                    $data['visitor_purpose'] = $records;
                    return $this->respond(['status' => 1, 'message' => 'Retrieved', 'data' => $data], 200);
                    
                }else{
                    return $this->respond(['status' => 0,'message' => 'Qr Code is currept!', 'data' => array()], 200);
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
    
    public function acceptEntry()
    {
        $rules = [
            'visitor_id' => ['rules' => 'required'],
            'management_id' => ['rules' => 'required'],
        ];

        $body = json_decode($this->request->getBody());
        if ($this->validate($rules)) {
            
            try {
                
                $visitorRecordsCheck = new VisitorRecords();
                $visitorRecordsCheck = $visitorRecordsCheck->where('visitor_id', $body->visitor_id)->where('purpose_entry', 'LOG-IN')->first();
                if(!$visitorRecordsCheck){
                    $visitorRecords = new VisitorRecords();
                    $data = [
                        'visitor_id'     => $body->visitor_id,
                        'management_id'  => $body->management_id,
                        'purpose'        => '',
                        'purpose_entry'  => 'LOG-IN'
                    ];
        
                    if($visitorRecords->insert($data)){
                        return $this->respond(['status' => 1, 'message' => 'Visitor login in management.'], 200);
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Visitor not login.please, try again.'], 200);
                    }
                }else{
                    // $visitorRecordsCheck = new VisitorRecords();
                    // $visitorRecordsChecks = $visitorRecordsCheck->where('visitor_id', $body->visitor_id)->where('management_id', $body->management_id)->where('purpose_entry', 'LOG-IN')->get();
                    // if ($results = $visitorRecordsChecks->getResult()) {
                    //     foreach ($results as $key => $result) {
                    //         $db = \Config\Database::connect();
    
                    //         $visitor_builder = $db->table('visitor_records');
                    //         $visitorRecords = $visitor_builder->where('id', $result->id);
                    //         $data = [
                    //             'purpose_entry'  => 'LOG-IN-OUT',
                    //             'management_key_id'  => 0,
                    //             'assign_at' => NULL
                    //         ];
                
                    //         $visitorRecords->update($data);
                    //     }
                    // }
                    // return $this->respond(['status' => 0, 'message' => 'Visitor now free.please, try again.'], 200);
                    return $this->respond(['status' => 0, 'message' => 'Visitor already login.'], 200);
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
            'visitor_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        if ($this->validate($rules)) {
            
            try {
                $db = \Config\Database::connect();
                
                $visitor_builder = $db->table('visitor_records');
                if(!empty($body->start_date) && !empty($body->end_date)){
                    $visitor_builder->where('DATE(created_at) >=', $body->start_date);
                    $visitor_builder->where('DATE(created_at) <', $body->end_date);
                }else if(!empty($body->start_date) && empty($body->end_date)){
                    $visitor_builder->where('DATE(created_at)', $body->start_date);
                }
                $visitor_builder->where('visitor_id', $body->visitor_id);
                $get_visitor = $visitor_builder->orderBy('created_at','DESC')->get();
                
                if ($results = $get_visitor->getResult()) {
                    $currentDate = '';
                    $data = array(); $d = 0;
                    foreach ($results as $key => $result) {
                        $managementModel = new Management();
                        $management = $managementModel->where('id', $result->management_id)->first();
                        
                        $visitorRecordKeys = new VisitorRecordKeys();
                        $visitorRecordKeysDatas = $visitorRecordKeys->where('records_id', $result->id)->get();
                        $recordKeys = array(); $r=0;
                        if ($visitorRecordKeysDatas = $visitorRecordKeysDatas->getResult()) {
                            foreach($visitorRecordKeysDatas as $visitorRecordKeysData){
                                $recordKeys[$r]['management_key_id'] = $visitorRecordKeysData->management_key_id;
                                $recordKeys[$r]['visitor_record_keys'] = $visitorRecordKeysData->key_id;
                                $recordKeys[$r]['loan_period'] = $visitorRecordKeysData->loan_period;
                                $recordKeys[$r]['status'] = $visitorRecordKeysData->status;
                                $r++;
                            }
                        }
                        
                        $created_at = date("d-m-Y", strtotime($result->created_at));
                        if($currentDate == $created_at){
                           $list[$l]['record_id'] = $result->id;
                           $list[$l]['title'] = $management['title'];
                           $list[$l]['purpose'] = $result->purpose;
                           $list[$l]['key_id'] = $recordKeys;
                           $list[$l]['check_in'] = date("H:i", strtotime($result->created_at));
                           if($result->purpose_entry == 'LOG-IN'){
                               $list[$l]['check_out'] = '00:00';
                           }else{
                               $list[$l]['check_out'] = date("H:i", strtotime($result->complate_at));
                           }
                           $data[$d-1]['list'] = $list;
                           $l++;
                        }else{
                           $list = array(); $l = 0;
                           $data[$d]['tag'] = date("l jS F Y", strtotime($result->created_at));
                           $list[$l]['record_id'] = $result->id;
                           $list[$l]['title'] = $management['title'];
                           $list[$l]['purpose'] = $result->purpose;
                           $list[$l]['visitor_record_keys'] = $recordKeys;
                           $list[$l]['check_in'] = date("H:i", strtotime($result->created_at));
                           if($result->purpose_entry == 'LOG-IN'){
                               $list[$l]['check_out'] = '00:00';
                           }else{
                               $list[$l]['check_out'] = date("H:i", strtotime($result->complate_at));
                           }
                           
                           $data[$d]['list'] = $list;
                           $currentDate = date("d-m-Y", strtotime($result->created_at));
                           $d++; $l++;
                       }
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
    
    public function activeManagementData()
    {
        $rules = [
            'visitor_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        if ($this->validate($rules)) {
            
            try {
                
                $visitorRecords = new VisitorRecords();
                $visitorRecords = $visitorRecords->where('visitor_id', $body->visitor_id)->where('purpose_entry', 'LOG-IN')->first();
                if ($visitorRecords) {
                    $currentDate = '';
                    $data = array(); 
                    $data['record_id'] = $visitorRecords['id'];
                    
                    $managementModel = new Management();
                    $management = $managementModel->where('id', $visitorRecords['management_id'])->first();
                    $data['management_id'] = $management['id'];
                    $data['title'] = $management['title'];
                    
                    $data['check_in'] = date("H:i", strtotime($visitorRecords['created_at']));
                    $data['check_out'] = '';
                    
                    $visitorRecordKeys = new VisitorRecordKeys();
                    $visitorRecordKeysDatas = $visitorRecordKeys->where('records_id', $visitorRecords['id'])->where('status', '0')->get();
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
                    
                    $data['visitor_record_keys'] = $recordKeys;
                        
                    return $this->respond(['status' => 1, 'message' => 'Retrieved', 'data' => $data], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Visitor Data not found!', 'data' => array()], 200);
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

    public function visitorHistory()
    {
        $rules = [
            'visitor_id' => ['rules' => 'required'],
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {

            $db = \Config\Database::connect();
            $builder = $db->table('visitor_records');
            $builder->where('visitor_id', $body->visitor_id);
            $builder->orderBy('created_at', 'DESC');
            $builder->selectCount('modified_date');
            $builder->select(['id', 'modified_date', 'notes']);
            $builder->groupBy('id');
            $query = $builder->get();

            $archive = array();

            if ($results = $query->getResult()) {
                foreach ($results as $key => $result) {
                    $new_obj['notes'] = $result->notes;
                    $archive[$result->modified_date][] = $new_obj;
                }
            }

            return $this->respond(['message' => 'History retrieved', 'data' => $archive], 200);
        } else {
            $response = [
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->fail($response, 409);
        }
    }
    
    public function keyReturn()
    {
        $rules = [
            'visitor_id' => ['rules' => 'required'],
            'management_key_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
             
                $db = \Config\Database::connect();
                
                $visitorRecords = new VisitorRecords();
                $visitorRecordsCheck = $visitorRecords->where('visitor_id', $body->visitor_id)->where('purpose_entry', 'LOG-IN')->orderBy('id', 'DESC')->first();
                
                if($visitorRecordsCheck){
                   
                    $query_builder = $db->table('visitor_record_keys');
                    $visitorRecords = $query_builder->where('records_id', $visitorRecordsCheck['id']);
                    $visitorRecords = $query_builder->where('management_key_id', $body->management_key_id);
                    $visitorRecords = $query_builder->where('status', 0);
                    
                    $data = [
                        'status'  => 1
                    ];
        
                    if($visitorRecords->update($data)){
                        return $this->respond(['status' => 1, 'message' => 'Key Return succesfully.'], 200);
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Key not return.please, try again.'], 200);
                    }
                }else{
                    return $this->respond(['status' => 2, 'message' => 'Visitor record not found.'], 200);
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
    
    public function keyList()
    {
        $rules = [
            'visitor_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                
                $visitorRecords = new VisitorRecords();
                $visitorRecordsData = $visitorRecords->where('visitor_id', $body->visitor_id)->where('purpose_entry', 'LOG-IN')->first();
                $data = array(); $d=0;
                if($visitorRecordsData){
                    
                    $visitorRecordKeys = new VisitorRecordKeys();
                    $visitorRecordKeysDatas = $visitorRecordKeys->where('records_id', $visitorRecordsData['id'])->where('status', 0)->get();
                    if ($visitorRecordKeysDatas = $visitorRecordKeysDatas->getResult()) {
                        foreach($visitorRecordKeysDatas as $visitorRecordKeysData){
                            
                            $managementKey = new ManagementKey();
                            $managementKeyData = $managementKey->where('id', $visitorRecordKeysData->management_key_id)->first();
                    
                            
                            $data[$d]['management_key_id'] = $visitorRecordKeysData->management_key_id;
                            $data[$d]['key_id'] = $visitorRecordKeysData->key_id;
                            $data[$d]['loan_period'] = $visitorRecordKeysData->loan_period;
                            $data[$d]['status'] = $visitorRecordKeysData->status;
                            
                            $data[$d]['serial_no'] = $managementKeyData['serial_no'];
                            $data[$d]['created_at'] = $visitorRecordsData['created_at'];
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
    
    public function distanceCheck()
    {
        $rules = [
            'visitor_id' => ['rules' => 'required'],
            'latitude' => ['rules' => 'required'],
            'longitude' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                
                $visitorRecords = new VisitorRecords();
                $visitorRecordsData = $visitorRecords->where('visitor_id', $body->visitor_id)->where('purpose_entry', 'LOG-IN')->first();
                $data = array(); 
                if($visitorRecordsData){
                    $management_id = $visitorRecordsData['management_id'];
                    
                    $management = new Management();
                    $managementData = $management->where('id', $management_id)->first();
                    $managementLatitude = $managementData['latitude'];
                    $managementLongitude = $managementData['longitude'];
                    
                    $distance = $this->distance($managementLatitude, $managementLongitude, $body->latitude, $body->longitude, 'K');
                    if((int)$distance < 1){
                    
                        $records_id = $visitorRecordsData['records_id'];
                        
                        $visitorRecords = $db->table('visitor_records');
                        $visitorRecordsData = $visitorRecords->where('id', $records_id);
                        $data = array('purpose_entry' => 'LOG-IN-OUT');
                        if($visitorRecordsData->update($data)){
                            
                            $visitorRecordKeys = $db->table('visitor_record_keys');
                            $visitorRecordKeysData = $visitorRecordKeys->where('records_id', $records_id);
                            $data = array('status' => 1);
                            $visitorRecordKeysData->update($data);
                        
                            return $this->respond(['status' => 1, 'message' => 'signOut Successfully'], 200);
                        }else{
                            return $this->respond(['status' => 0, 'message' => 'Not signOut'], 200);
                        }
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Not signOut'], 200);
                    }
                }else{
                    return $this->respond(['status' => 0, 'message' => 'Not signOut'], 200);
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
    
    public function visitorFind()
    {
        $rules = [
            'name' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                
                $db = \Config\Database::connect();
                $visitor_builder = $db->table('visitors');
                
                if(!empty($body->mobile_number)){
                    $visitor_builder->like('mobile_number', '%'.$body->mobile_number.'%');
                }
                
                $name = $body->name ? explode(" ", $body->name) : [];
                if (isset($name[0])) {
                    $visitor_builder->like('first_name', '%'.$name[0].'%');
                }
                if (isset($name[1])) {
                    $visitor_builder->like('last_name', '%'.$name[1].'%');
                }
                    
                $get_visitor = $visitor_builder->orderBy('created_at','DESC')->limit(10)->get();
                    
                if ($results = $get_visitor->getResult()) {
                    $data = array(); $d=0;
                    foreach ($results as $key => $result) {
                        $data[$d]['visitor_id'] = $result->id;
                        $data[$d]['first_name'] = $result->first_name;
                        $data[$d]['last_name'] = $result->last_name;
                        $data[$d]['email'] = $result->email;
                        $data[$d]['mobile_number'] = $result->mobile_number;
                        $d++;
                    }
                    return $this->respond(['status' => 1, 'message' => 'Visitor data', 'data' => $data], 200);
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
    
    public function visitorQrtoData()
    {
        $rules = [
            'unique_key' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                
                $visitor = new Visitor();
                $visitorData = $visitor->where('unique_key', $body->unique_key)->first();
                if($visitorData){
                    $data['visitor_id'] = $visitorData['id'];
                    $data['first_name'] = $visitorData['first_name'];
                    $data['last_name'] = $visitorData['last_name'];
                    $data['email'] = $visitorData['email'];
                    $data['mobile_number'] = $visitorData['mobile_number'];
                    return $this->respond(['status' => 1, 'message' => 'Visitor data', 'data' => $data], 200);
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
    
    function distance($lat1, $lon1, $lat2, $lon2, $unit) {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);
        
        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }
}
