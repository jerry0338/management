<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{Visitor, VisitorRecords, UserVisitor, VisitorRecordKeys};
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
            'management_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {
            helper('text');

            $db = \Config\Database::connect();
            $visitor_records_builder = $db->table('visitor_records');
            $visitor_records_builder->where('purpose_entry', 'LOG-IN');
            $visitor_records_builder->where('management_id', $body->management_id);
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
            'management_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {
            helper('text');

            $db = \Config\Database::connect();
            $visitor_records_builder = $db->table('visitor_records');
            $today = date('Y-m-d');
            $visitor_records_builder->where('DATE(created_at)', $today);
            $visitor_records_builder->where('management_id', $body->management_id);
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
            'management_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {
            helper('text');

            $db = \Config\Database::connect();
            $visitor_records_builder = $db->table('visitor_records');
            $startDate = date('Y-m-d', strtotime('this week monday'));
            $endDate = date('Y-m-d', strtotime('this week sunday'));
            $visitor_records_builder->where('created_at >=', $startDate);
            $visitor_records_builder->where('created_at <=', $endDate);
            $visitor_records_builder->where('management_id', $body->management_id);
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
    
    public function filterData()
    {
        $rules = [
            'management_id' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {
            helper('text');

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
            
            $visitor_records_builder->where('management_id', $body->management_id);
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
}