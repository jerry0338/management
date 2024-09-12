<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{Visitor, VisitorRecords, UserVisitor, VisitorRecordKeys, ManagementKey};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\Pdf;
use Dompdf\Dompdf;

class ReportController extends BaseController
{
    use ResponseTrait;
    public function __construct() {
		$this->parser = service('renderer');
	}
    public function curerntVisitor()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {
            helper('text');
            helper('common');
            if($body->management_type == 'staff'){
                $management_id = managementTypeToIdGet($body->management_id);
            }else{
                $management_id = $body->management_id;
            }

            $db = \Config\Database::connect();
            $visitor_records_builder = $db->table('visitor_records');
            $visitor_records_builder->where('purpose_entry', 'LOG-IN');
            $visitor_records_builder->where('management_id', $management_id);
            $get_visitor = $visitor_records_builder->orderBy('created_at','DESC')->get();
            if ($results = $get_visitor->getResult()) {

                $data = array(); $d=0;
                foreach ($results as $key => $visitor_records) {
                    
                    $visitor_builder = $db->table('visitors');
                    $visitor_builder->where('id', $visitor_records->visitor_id);
                    $visitor_builder->limit(1);
                    $visitor_builder = $visitor_builder->get();
                    $visitor = $visitor_builder->getFirstRow();
                                    
                    $visitor_type_builder = $db->table('visitor_type');
                    $visitor_type_builder->where('id', $visitor->visitor_type_id);
                    $visitor_type_builder->limit(1);
                    $visitor_type_builder = $visitor_type_builder->get();
                    $visitor_type = $visitor_type_builder->getFirstRow();
                    $data[$d]['type_name'] = $visitor_type->type;
                                        
                    $data[$d]['name'] = $visitor->first_name.' '.$visitor->last_name;
                    $data[$d]['company_name'] = $visitor->company_name;
                    $data[$d]['email'] = $visitor->email;
                    $data[$d]['mobile_number'] = $visitor->mobile_number;
                    $data[$d]['created_at'] = $visitor_records->created_at;
                    $d++;
                }
                $dompdf = new Dompdf();
                $html = view('report/curernt_visitor', ['data' => $data]);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $filename = 'Management_current_visitor_'.date('Ymd').time().'.pdf';
                $originalPath = 'pdf/';
            
                $folderName = date('Ym');
                $path = $originalPath.$folderName;
                // Ensure the directory exists
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                $file_path = $path.'/'.$filename;
                // Write the file using native PHP file handling
                if(file_put_contents($file_path, $dompdf->output())){
                    return $this->respond(['status' => 1, 'message' => 'Visitor report', 'url' => base_url($file_path)], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No active visitor found', 'url' => ''], 200);
            }
        } else {
            $response = [
                'status' => 0,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->respond($response, 409);
        }
    }
    public function dailyData()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {
            helper('text');
            helper('common');
            if($body->management_type == 'staff'){
                $management_id = managementTypeToIdGet($body->management_id);
            }else{
                $management_id = $body->management_id;
            }

            $db = \Config\Database::connect();
            $visitor_records_builder = $db->table('visitor_records');
            $today = date('Y-m-d');
            $visitor_records_builder->where('DATE(created_at)', $today);
            $visitor_records_builder->where('management_id', $management_id);
            $get_visitor = $visitor_records_builder->orderBy('created_at','DESC')->get();
            if ($results = $get_visitor->getResult()) {

                $data = array(); $d=0;
                foreach ($results as $key => $visitorRecords) {
                    
                    $visitor_builder = $db->table('visitors');
                    $visitor_builder->where('id', $visitorRecords->visitor_id);
                    $visitor_builder->limit(1);
                    $visitor_builder = $visitor_builder->get();
                    $visitor = $visitor_builder->getFirstRow();
                                    
                    $visitor_type = $db->table('visitor_type');
                    $visitor_type->where('id', $visitor->visitor_type_id);
                    $visitor_type->limit(1);
                    $visitor_type = $visitor_type->get();
                    $visitorTypeRecords = $visitor_type->getFirstRow();
                    $data[$d]['type_name'] = $visitorTypeRecords->type;
                                        
                    $data[$d]['name'] = $visitor->first_name.' '.$visitor->last_name;
                    $data[$d]['company_name'] = $visitor->company_name;
                    $data[$d]['email'] = $visitor->email;
                    $data[$d]['mobile_number'] = $visitor->mobile_number;

                    if($visitorRecords->purpose_entry == 'LOG-IN'){
                        $data[$d]['status'] = 'Avalible';
                    }else{
                        $data[$d]['status'] = 'unavailable';
                    }
                    $data[$d]['created_at'] = $visitorRecords->created_at;
                    
                    $d++;
                }
                $dompdf = new Dompdf();
                $html = view('report/daily_data', ['data' => $data]);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $filename = 'Management_current_visitor_'.date('Ymd').time().'.pdf';
                $originalPath = 'pdf/';
            
                $folderName = date('Ym');
                $path = $originalPath.$folderName;
                // Ensure the directory exists
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                $file_path = $path.'/'.$filename;
                // Write the file using native PHP file handling
                if(file_put_contents($file_path, $dompdf->output())){
                    return $this->respond(['status' => 1, 'message' => 'Visitor report', 'url' => base_url($file_path)], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No active visitor found', 'url' => ''], 200);
            }
        } else {
            $response = [
                'status' => 0,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->respond($response, 409);
        }
    }
    public function weeklyData()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {
            helper('text');
            helper('common');
            if($body->management_type == 'staff'){
                $management_id = managementTypeToIdGet($body->management_id);
            }else{
                $management_id = $body->management_id;
            }

            $db = \Config\Database::connect();
            $visitor_records_builder = $db->table('visitor_records');
            $startDate = date('Y-m-d', strtotime('this week monday'));
            $endDate = date('Y-m-d', strtotime('this week sunday'));
            $visitor_records_builder->where('created_at >=', $startDate);
            $visitor_records_builder->where('created_at <=', $endDate);
            $visitor_records_builder->where('management_id', $management_id);
            $get_visitor = $visitor_records_builder->orderBy('created_at','DESC')->get();
            if ($results = $get_visitor->getResult()) {

                $data = array(); $d=0;
                foreach ($results as $key => $visitorRecords) {
                    
                    $visitor_builder = $db->table('visitors');
                    $visitor_builder->where('id', $visitorRecords->visitor_id);
                    $visitor_builder->limit(1);
                    $visitor_builder = $visitor_builder->get();
                    $visitor = $visitor_builder->getFirstRow();
                                    
                    $visitor_type = $db->table('visitor_type');
                    $visitor_type->where('id', $visitor->visitor_type_id);
                    $visitor_type->limit(1);
                    $visitor_type = $visitor_type->get();
                    $visitorTypeRecords = $visitor_type->getFirstRow();
                    $data[$d]['type_name'] = $visitorTypeRecords->type;
                                        
                    $data[$d]['name'] = $visitor->first_name.' '.$visitor->last_name;
                    $data[$d]['company_name'] = $visitor->company_name;
                    $data[$d]['email'] = $visitor->email;
                    $data[$d]['mobile_number'] = $visitor->mobile_number;

                    if($visitorRecords->purpose_entry == 'LOG-IN'){
                        $data[$d]['status'] = 'Avalible';
                    }else{
                        $data[$d]['status'] = 'unavailable';
                    }
                    $data[$d]['created_at'] = $visitorRecords->created_at;
                    
                    $d++;
                }
                $dompdf = new Dompdf();
                $html = view('report/weekly_data', ['data' => $data]);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $filename = 'Management_weekly_visitor_'.date('Ymd').time().'.pdf';
                $originalPath = 'pdf/';
            
                $folderName = date('Ym');
                $path = $originalPath.$folderName;
                // Ensure the directory exists
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                $file_path = $path.'/'.$filename;
                // Write the file using native PHP file handling
                if(file_put_contents($file_path, $dompdf->output())){
                    return $this->respond(['status' => 1, 'message' => 'Visitor report', 'url' => base_url($file_path)], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No active visitor found', 'url' => ''], 200);
            }
        } else {
            $response = [
                'status' => 0,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->respond($response, 409);
        }
    }
    public function filterData()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {
            helper('text');
            helper('common');
            if($body->management_type == 'staff'){
                $management_id = managementTypeToIdGet($body->management_id);
            }else{
                $management_id = $body->management_id;
            }

            $db = \Config\Database::connect();
            $visitor_records_builder = $db->table('visitor_records');
            
            if(!empty($body->formDate) && !empty($body->toDate)){
                $formDate = date('Y-m-d', strtotime($body->formDate));
                $toDate = date('Y-m-d', strtotime($body->toDate));
                $visitor_records_builder->where('created_at >=', $formDate);
                $visitor_records_builder->where('created_at <=', $toDate);
            }

            if(!empty($body->visitor_type_id)){
                $visitorBuilder = $db->table('visitors'); 
                $visitorBuilder->where('visitor_type_id', $body->visitor_type_id)
                                ->groupBy('id')
                                ->select('id');
                $visitorIds = $visitorBuilder->get()->getResultArray();
                if (!empty($visitorIds)) {
                    $visitor_records_builder->whereIn('visitor_id', array_column($visitorIds, 'id'));
                }else{
                    $visitor_records_builder->where('visitor_id', '0');
                }
            }

            if(!empty($body->visitor_id)){
                $visitor_records_builder->where('visitor_id', $body->visitor_id);
            }
            
            $visitor_records_builder->where('management_id', $management_id);
            $get_visitor = $visitor_records_builder->orderBy('created_at','DESC')->get();
            if ($results = $get_visitor->getResult()) {

                $data = array(); $d=0;
                foreach ($results as $key => $visitorRecords) {
                    
                    $visitor_builder = $db->table('visitors');
                    $visitor_builder->where('id', $visitorRecords->visitor_id);
                    $visitor_builder->limit(1);
                    $visitor_builder = $visitor_builder->get();
                    $visitor = $visitor_builder->getFirstRow();
                                    
                    $visitor_type = $db->table('visitor_type');
                    $visitor_type->where('id', $visitor->visitor_type_id);
                    $visitor_type->limit(1);
                    $visitor_type = $visitor_type->get();
                    $visitorTypeRecords = $visitor_type->getFirstRow();
                    $data[$d]['type_name'] = $visitorTypeRecords->type;
                                        
                    $data[$d]['name'] = $visitor->first_name.' '.$visitor->last_name;
                    $data[$d]['company_name'] = $visitor->company_name;
                    $data[$d]['email'] = $visitor->email;
                    $data[$d]['mobile_number'] = $visitor->mobile_number;

                    if($visitorRecords->purpose_entry == 'LOG-IN'){
                        $data[$d]['status'] = 'Avalible';
                    }else{
                        $data[$d]['status'] = 'unavailable';
                    }
                    $data[$d]['created_at'] = $visitorRecords->created_at;
                    
                    $d++;
                }
                $dompdf = new Dompdf();
                $html = view('report/filter_data', ['data' => $data]);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $filename = 'Management_current_visitor_'.date('Ymd').time().'.pdf';
                $originalPath = 'pdf/';
            
                $folderName = date('Ym');
                $path = $originalPath.$folderName;
                // Ensure the directory exists
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                $file_path = $path.'/'.$filename;
                // Write the file using native PHP file handling
                if(file_put_contents($file_path, $dompdf->output())){
                    return $this->respond(['status' => 1, 'message' => 'Visitor report', 'url' => base_url($file_path)], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No active visitor found', 'url' => ''], 200);
            }
        } else {
            $response = [
                'status' => 0,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->respond($response, 409);
        }
    }
    public function keyAvalible()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {
            helper('text');
            helper('common');
            if($body->management_type == 'staff'){
                $management_id = managementTypeToIdGet($body->management_id);
            }else{
                $management_id = $body->management_id;
            }

            $db = \Config\Database::connect();
            $managementKey = new ManagementKey();
            $managementKey = $managementKey->where('management_id', $management_id)->get();
            if ($results = $managementKey->getResult()) {
                $data = array(); $d=0;
                foreach ($results as $key => $result) {                    
                    $visitorRecordKeys = new VisitorRecordKeys();
                    $visitorRecordKeysData = $visitorRecordKeys->where('management_key_id', $result->id)->where('status', 0)->first();
                    if(!$visitorRecordKeysData){
                        $data[$d]['key_id'] = $result->key_id;
                        $data[$d]['serial_no'] = $result->serial_no;
                        $data[$d]['key_type'] = $result->key_type;
                        $d++;
                    }     
                }
                $dompdf = new Dompdf();
                $html = view('report/key_avalible', ['data' => $data]);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $filename = 'Management_key_avalible_'.date('Ymd').time().'.pdf';
                $originalPath = 'pdf/';
            
                $folderName = date('Ym');
                $path = $originalPath.$folderName;
                // Ensure the directory exists
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                $file_path = $path.'/'.$filename;
                // Write the file using native PHP file handling
                if(file_put_contents($file_path, $dompdf->output())){
                    return $this->respond(['status' => 1, 'message' => 'Key avalible report', 'url' => base_url($file_path)], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No avalible key found', 'url' => ''], 200);
            }
        } else {
            $response = [
                'status' => 0,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->respond($response, 409);
        }
    }
    public function keyOnLoan()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {
            helper('text');
            helper('common');
            if($body->management_type == 'staff'){
                $management_id = managementTypeToIdGet($body->management_id);
            }else{
                $management_id = $body->management_id;
            }

            $db = \Config\Database::connect();
            $managementKey = new ManagementKey();
            $managementKey = $managementKey->where('management_id', $management_id)->get();
            if ($results = $managementKey->getResult()) {
                $data = array(); $d=0;
                foreach ($results as $key => $result) {                    
                    $visitorRecordKeys = new VisitorRecordKeys();
                    $visitorRecordKeysData = $visitorRecordKeys->select('visitor_record_keys.*, visitors.first_name, visitors.last_name, visitors.company_name, visitor_type.type')->join('visitor_records', 'visitor_records.id = visitor_record_keys.records_id')->join('visitors', 'visitors.id = visitor_records.visitor_id')->join('visitor_type', 'visitor_type.id = visitors.visitor_type_id')->where('visitor_record_keys.management_key_id', $result->id)->where('visitor_record_keys.status', 0)->first();
            
                    if($visitorRecordKeysData){
                        $data[$d]['key_id'] = $result->key_id;
                        $data[$d]['serial_no'] = $result->serial_no;
                        $data[$d]['key_type'] = $result->key_type;
                        
                        $data[$d]['person_type'] = $visitorRecordKeysData['type'];
                        $data[$d]['name'] = $visitorRecordKeysData['first_name'].' '.$visitorRecordKeysData['last_name'];
                        $data[$d]['company'] = $visitorRecordKeysData['company_name'];
                        
                        $loan_period = date_add(date_create($visitorRecordKeysData['created_at']), date_interval_create_from_date_string($visitorRecordKeysData['loan_period']));
                        $data[$d]['key_out'] = date_format($loan_period, 'd/m/Y h:ia');
                        $data[$d]['loan_length'] =  $visitorRecordKeysData['loan_period'];
                        $d++;
                    }
                }
                $dompdf = new Dompdf();
                $html = view('report/key_on_loan', ['data' => $data]);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $filename = 'Management_key_on_loan_'.date('Ymd').time().'.pdf';
                $originalPath = 'pdf/';
            
                $folderName = date('Ym');
                $path = $originalPath.$folderName;
                // Ensure the directory exists
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                $file_path = $path.'/'.$filename;
                // Write the file using native PHP file handling
                if(file_put_contents($file_path, $dompdf->output())){
                    return $this->respond(['status' => 1, 'message' => 'Key on loan report', 'url' => base_url($file_path)], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No loan key found', 'url' => ''], 200);
            }
        } else {
            $response = [
                'status' => 0,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->respond($response, 409);
        }
    }
    public function allKeyList()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {
            helper('text');
            helper('common');
            if($body->management_type == 'staff'){
                $management_id = managementTypeToIdGet($body->management_id);
            }else{
                $management_id = $body->management_id;
            }

            $db = \Config\Database::connect();
            $managementKey = new ManagementKey();
            $managementKey = $managementKey->where('management_id', $management_id)->get();
            if ($results = $managementKey->getResult()) {
                $data = array(); $d=0;
                foreach ($results as $key => $result) {
                    $data[$d]['key_id'] = $result->key_id;
                    $data[$d]['serial_no'] = $result->serial_no;
                    $data[$d]['key_type'] = $result->key_type;
                    
                    $visitorRecordKeys = new VisitorRecordKeys();
                    
                    $visitorRecordKeysData = $visitorRecordKeys->select('visitor_record_keys.*, visitors.first_name, visitors.last_name, visitors.company_name, visitor_type.type')->join('visitor_records', 'visitor_records.id = visitor_record_keys.records_id')->join('visitors', 'visitors.id = visitor_records.visitor_id')->join('visitor_type', 'visitor_type.id = visitors.visitor_type_id')->where('visitor_record_keys.management_key_id', $result->id)->where('visitor_record_keys.status', 0)->first();
            
                    if($visitorRecordKeysData){
                        $data[$d]['key_loan'] = 'Y';
                        
                        $data[$d]['person_type'] = $visitorRecordKeysData['type'];
                        $data[$d]['name'] = $visitorRecordKeysData['first_name'].' '.$visitorRecordKeysData['last_name'];
                        $data[$d]['company'] = $visitorRecordKeysData['company_name'];
                        
                        $loan_period = date_add(date_create($visitorRecordKeysData['created_at']), date_interval_create_from_date_string($visitorRecordKeysData['loan_period']));
                        $data[$d]['key_out'] = date_format($loan_period, 'd/m/Y h:ia');
                        $data[$d]['loan_length'] =  $visitorRecordKeysData['loan_period'];
                    }else{
                        $data[$d]['key_loan'] = 'N';
                        
                        $data[$d]['person_type'] = 'n/a';
                        $data[$d]['name'] = 'n/a';
                        $data[$d]['company'] = 'n/a';
                        $data[$d]['key_out'] = 'n/a';
                        $data[$d]['loan_length'] = 'n/a';
                    }                    
                    $d++;
                }
                $dompdf = new Dompdf();
                $html = view('report/all_key_list', ['data' => $data]);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $filename = 'Management_all_key_list_'.date('Ymd').time().'.pdf';
                $originalPath = 'pdf/';
            
                $folderName = date('Ym');
                $path = $originalPath.$folderName;
                // Ensure the directory exists
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                $file_path = $path.'/'.$filename;
                // Write the file using native PHP file handling
                if(file_put_contents($file_path, $dompdf->output())){
                    return $this->respond(['status' => 1, 'message' => 'All Key report', 'url' => base_url($file_path)], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No key found', 'url' => ''], 200);
            }
        } else {
            $response = [
                'status' => 0,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->respond($response, 409);
        }
    }
    public function endOfDayKey()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {
            helper('text');
            helper('common');
            if($body->management_type == 'staff'){
                $management_id = managementTypeToIdGet($body->management_id);
            }else{
                $management_id = $body->management_id;
            }

            $db = \Config\Database::connect();
            $managementKey = new ManagementKey();
            $managementKey = $managementKey->where('management_id', $management_id)->get();
            if ($results = $managementKey->getResult()) {
                $data = array(); $d=0;
                foreach ($results as $key => $result) {
                    
                    
                    $visitorRecordKeys = new VisitorRecordKeys();
                    
                    $visitorRecordKeysData = $visitorRecordKeys->select('visitor_record_keys.*, visitors.first_name, visitors.last_name, visitors.company_name, visitor_type.type')->join('visitor_records', 'visitor_records.id = visitor_record_keys.records_id')->join('visitors', 'visitors.id = visitor_records.visitor_id')->join('visitor_type', 'visitor_type.id = visitors.visitor_type_id')->where('visitor_record_keys.management_key_id', $result->id)->where('visitor_record_keys.status', 0)->first();
                
                    $data[$d]['key_id'] = $result->key_id;
                    $data[$d]['serial_no'] = $result->serial_no;
                    $data[$d]['key_type'] = $result->key_type;
                    if($visitorRecordKeysData){
                        $data[$d]['key_loan'] = 'Y';
                        
                        $data[$d]['person_type'] = $visitorRecordKeysData['type'];
                        $data[$d]['name'] = $visitorRecordKeysData['first_name'].' '.$visitorRecordKeysData['last_name'];
                        $data[$d]['company'] = $visitorRecordKeysData['company_name'];
                        
                        $loan_period = date_add(date_create($visitorRecordKeysData['created_at']), date_interval_create_from_date_string($visitorRecordKeysData['loan_period']));
                        $data[$d]['key_out'] = date_format($loan_period, 'd/m/Y h:ia');
                        $data[$d]['loan_length'] =  $visitorRecordKeysData['loan_period'];
                    }else{
                        $data[$d]['key_loan'] = 'N';
                        
                        $data[$d]['person_type'] = 'n/a';
                        $data[$d]['name'] = 'n/a';
                        $data[$d]['company'] = 'n/a';
                        $data[$d]['key_out'] = 'n/a';
                        $data[$d]['loan_length'] = 'n/a';
                    }                    
                    $d++;
                }
                $dompdf = new Dompdf();
                $html = view('report/end_of_day_key', ['data' => $data]);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $filename = 'Management_end_of_day_key_'.date('Ymd').time().'.pdf';
                $originalPath = 'pdf/';
            
                $folderName = date('Ym');
                $path = $originalPath.$folderName;
                // Ensure the directory exists
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                $file_path = $path.'/'.$filename;
                // Write the file using native PHP file handling
                if(file_put_contents($file_path, $dompdf->output())){
                    return $this->respond(['status' => 1, 'message' => 'End of day Key report', 'url' => base_url($file_path)], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No key Record found', 'url' => ''], 200);
            }
        } else {
            $response = [
                'status' => 0,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->respond($response, 409);
        }
    }
    public function staffKey()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {
            helper('text');
            helper('common');
            if($body->management_type == 'staff'){
                $management_id = managementTypeToIdGet($body->management_id);
            }else{
                $management_id = $body->management_id;
            }
            $db = \Config\Database::connect();
            $managementKey = new ManagementKey();
            $managementKey = $managementKey->where('management_id', $management_id)->get();
            if ($results = $managementKey->getResult()) {
                $data = array(); $d=0;
                foreach ($results as $key => $result) {
                    
                    
                    $visitorRecordKeys = new VisitorRecordKeys();
                    
                    $visitorRecordKeysData = $visitorRecordKeys->select('visitor_record_keys.*, visitors.first_name, visitors.last_name, visitors.company_name, visitor_type.type')->join('visitor_records', 'visitor_records.id = visitor_record_keys.records_id')->join('visitors', 'visitors.id = visitor_records.visitor_id')->join('visitor_type', 'visitor_type.id = visitors.visitor_type_id')->where('visitor_record_keys.management_key_id', $result->id)->where('visitor_record_keys.status', 0)->first();
                
                    $data[$d]['key_id'] = $result->key_id;
                    $data[$d]['serial_no'] = $result->serial_no;
                    $data[$d]['key_type'] = $result->key_type;
                    if($visitorRecordKeysData){
                        $data[$d]['key_loan'] = 'Y';
                        
                        $data[$d]['person_type'] = $visitorRecordKeysData['type'];
                        $data[$d]['name'] = $visitorRecordKeysData['first_name'].' '.$visitorRecordKeysData['last_name'];
                        $data[$d]['company'] = $visitorRecordKeysData['company_name'];
                        
                        $loan_period = date_add(date_create($visitorRecordKeysData['created_at']), date_interval_create_from_date_string($visitorRecordKeysData['loan_period']));
                        $data[$d]['key_out'] = date_format($loan_period, 'd/m/Y h:ia');
                        $data[$d]['loan_length'] =  $visitorRecordKeysData['loan_period'];
                    }else{
                        $data[$d]['key_loan'] = 'N';
                        
                        $data[$d]['person_type'] = 'n/a';
                        $data[$d]['name'] = 'n/a';
                        $data[$d]['company'] = 'n/a';
                        $data[$d]['key_out'] = 'n/a';
                        $data[$d]['loan_length'] = 'n/a';
                    }                    
                    $d++;
                }
                $dompdf = new Dompdf();
                $html = view('report/end_of_day_key', ['data' => $data]);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $filename = 'Management_end_of_day_key_'.date('Ymd').time().'.pdf';
                $originalPath = 'pdf/';
            
                $folderName = date('Ym');
                $path = $originalPath.$folderName;
                // Ensure the directory exists
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                $file_path = $path.'/'.$filename;
                // Write the file using native PHP file handling
                if(file_put_contents($file_path, $dompdf->output())){
                    return $this->respond(['status' => 1, 'message' => 'End of day Key report', 'url' => base_url($file_path)], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No key Record found', 'url' => ''], 200);
            }
        } else {
            $response = [
                'status' => 0,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->respond($response, 409);
        }
    }
}