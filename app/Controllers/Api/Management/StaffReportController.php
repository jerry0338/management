<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{Management, ManagementStaff, ManagementLogin};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\Pdf;
use Dompdf\Dompdf;

class StaffReportController extends BaseController
{
    use ResponseTrait;
    public function __construct() {
		$this->parser = service('renderer');
	}
    
    public function filterSelectList()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required']            
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {
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
                $week = array(); 

                $management_login_builder = $db->table('management_login');
                $management_login_builder->select("CONCAT(WEEK(created_at, 1) - WEEK(DATE_SUB(created_at, INTERVAL DAY(created_at) - 1 DAY), 1) + 1, ' ', DATE_FORMAT(created_at, '%b %Y')) AS formatted_week");
                $management_login_builder->whereIn('staff_id', array_column($staffIds, 'id'))->groupBy('formatted_week');
                $managementLogin = $management_login_builder->get();
                if ($results = $managementLogin->getResult()) {                    
                    foreach ($results as $result) {
                        $week[] = $result->formatted_week;
                    } 
                }

                $managementStaff = new ManagementStaff();
                $managementStaff = $managementStaff->where('management_id', $management_id)->orderBy('name','ASC')->get();
                $staff = array(); $s=0;
                if ($results = $managementStaff->getResult()) {
                    foreach ($results as $key => $result) {
                        $staff[$s]['management_staff_id'] = $result->id;
                        $staff[$s]['name'] = $result->name;
                        $s++;
                    }
                }
                $sortBy[0]['slug'] = 'staff_name_atoz';
                $sortBy[0]['title'] = 'Staff Name A to Z';
                $sortBy[1]['slug'] = 'staff_name_ztoa';
                $sortBy[1]['title'] = 'Staff Name Z to A';
                $sortBy[2]['slug'] = 'earliest_on_top';
                $sortBy[2]['title'] = 'Earliest On Top';
                $sortBy[3]['slug'] = 'earliest_on_bottom';
                $sortBy[3]['title'] = 'Earliest On Bottom';

                return $this->respond(['status' => 1, 'message' => 'Staff filter', 'week_schedule' => $week, 'staff_list' => $staff, 'sort_by' => $sortBy], 200);
            }else{
                return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
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
            $managementBuilder = $db->table('management_staff'); 
            $managementBuilder->where('management_id', $management_id)
                            ->groupBy('id')
                            ->select('id');
            $staffIds = $managementBuilder->get()->getResultArray();
            if (!empty($staffIds)) {
                
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

                if(!empty($body->management_staff_id)){
                    $management_login_builder->where('management_login.staff_id', $body->management_staff_id);
                }else{
                    $management_login_builder->whereIn('management_login.staff_id', array_column($staffIds, 'id'));
                }
                
                $management_login_builder->join('management_staff', 'management_login.staff_id = management_staff.id', 'inner');

                if(!empty($body->sort_by)){
                    if($body->sort_by == 'staff_name_atoz'){
                        $management_login_builder->orderBy('management_staff.name','ASC');
                    }else if($body->sort_by == 'staff_name_ztoa'){
                        $management_login_builder->orderBy('management_staff.name','DESC');
                    }else if($body->sort_by == 'earliest_on_top'){
                        $management_login_builder->orderBy('management_login.created_at','DESC');
                    }else if($body->sort_by == 'earliest_on_bottom'){
                        $management_login_builder->orderBy('management_login.created_at','ASC');
                    }else{
                        $management_login_builder->orderBy('management_login.created_at','DESC');
                    }
                }else{
                    $management_login_builder->orderBy('management_login.created_at','DESC');
                }
                $managementLogin = $management_login_builder->get();
                if ($results = $managementLogin->getResult()) {
                    $data = array(); $d=0;
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
                    $dompdf = new Dompdf();
                    $html = view('report/current_week_by_name', ['data' => $data]);
                    $dompdf->loadHtml($html);
                    $dompdf->setPaper('A4', 'landscape');
                    $dompdf->render();
                    $filename = 'Management_current_week_by_name_'.date('Ymd').time().'.pdf';
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
                        return $this->respond(['status' => 1, 'message' => 'Staff report', 'url' => base_url($file_path)], 200);
                    }else{
                        return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                    }
                }else{
                    return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
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
    public function currentWeekByName()
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
            $managementBuilder = $db->table('management_staff'); 
            $managementBuilder->where('management_id', $management_id)
                            ->groupBy('id')
                            ->select('id');
            $staffIds = $managementBuilder->get()->getResultArray();
            if (!empty($staffIds)) {
                
                $management_login_builder = $db->table('management_login');
                $startDate = date('Y-m-d', strtotime('this week monday'));
                $endDate = date('Y-m-d', strtotime('this week sunday'));
                $management_login_builder->where('management_login.created_at >=', $startDate);
                $management_login_builder->where('management_login.created_at <=', $endDate);
                $management_login_builder->whereIn('management_login.staff_id', array_column($staffIds, 'id'));
                $management_login_builder->join('management_staff', 'management_login.staff_id = management_staff.id', 'inner');
                $managementLogin = $management_login_builder->orderBy('management_staff.name','ASC')->get();
                if ($results = $managementLogin->getResult()) {
                    $data = array(); $d=0;
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
                    $dompdf = new Dompdf();
                    $html = view('report/current_week_by_name', ['data' => $data]);
                    $dompdf->loadHtml($html);
                    $dompdf->setPaper('A4', 'landscape');
                    $dompdf->render();
                    $filename = 'Management_current_week_by_name_'.date('Ymd').time().'.pdf';
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
                        return $this->respond(['status' => 1, 'message' => 'Staff report', 'url' => base_url($file_path)], 200);
                    }else{
                        return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                    }
                }else{
                    return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
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
    public function currentWeekByDate()
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
            $managementBuilder = $db->table('management_staff'); 
            $managementBuilder->where('management_id', $management_id)
                            ->groupBy('id')
                            ->select('id');
            $staffIds = $managementBuilder->get()->getResultArray();
            if (!empty($staffIds)) {
                
                $management_login_builder = $db->table('management_login');
                $startDate = date('Y-m-d', strtotime('this week monday'));
                $endDate = date('Y-m-d', strtotime('this week sunday'));
                $management_login_builder->where('management_login.created_at >=', $startDate);
                $management_login_builder->where('management_login.created_at <=', $endDate);
                $management_login_builder->whereIn('management_login.staff_id', array_column($staffIds, 'id'));
                $management_login_builder->join('management_staff', 'management_login.staff_id = management_staff.id', 'inner');
                $managementLogin = $management_login_builder->orderBy('management_login.date','DESC')->get();
                if ($results = $managementLogin->getResult()) {
                    $data = array(); $d=0;
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
                    $dompdf = new Dompdf();
                    $html = view('report/current_week_by_date', ['data' => $data]);
                    $dompdf->loadHtml($html);
                    $dompdf->setPaper('A4', 'landscape');
                    $dompdf->render();
                    $filename = 'Management_current_week_by_date_'.date('Ymd').time().'.pdf';
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
                        return $this->respond(['status' => 1, 'message' => 'Staff report', 'url' => base_url($file_path)], 200);
                    }else{
                        return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                    }
                }else{
                    return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
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
    public function currentMonthByName()
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
            $managementBuilder = $db->table('management_staff'); 
            $managementBuilder->where('management_id', $management_id)
                              ->groupBy('id')
                              ->select('id');
            $staffIds = $managementBuilder->get()->getResultArray();
            if (!empty($staffIds)) {
                
                $management_login_builder = $db->table('management_login');
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t'); 
                $management_login_builder->where('management_login.created_at >=', $startDate);
                $management_login_builder->where('management_login.created_at <=', $endDate);
                $management_login_builder->whereIn('management_login.staff_id', array_column($staffIds, 'id'));
                $management_login_builder->join('management_staff', 'management_login.staff_id = management_staff.id', 'inner');
                $managementLogin = $management_login_builder->orderBy('management_staff.name','ASC')->get();
                if ($results = $managementLogin->getResult()) {
                    $data = array(); $d=0;
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
                    $dompdf = new Dompdf();
                    $html = view('report/current_month_by_name', ['data' => $data]);
                    $dompdf->loadHtml($html);
                    $dompdf->setPaper('A4', 'landscape');
                    $dompdf->render();
                    $filename = 'Management_current_month_by_name_'.date('Ymd').time().'.pdf';
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
                        return $this->respond(['status' => 1, 'message' => 'Staff report', 'url' => base_url($file_path)], 200);
                    }else{
                        return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                    }
                }else{
                    return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
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
    public function currentMonthByDate()
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
            $managementBuilder = $db->table('management_staff'); 
            $managementBuilder->where('management_id', $management_id)
                              ->groupBy('id')
                              ->select('id');
            $staffIds = $managementBuilder->get()->getResultArray();
            if (!empty($staffIds)) {
                
                $management_login_builder = $db->table('management_login');
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t'); 
                $management_login_builder->where('management_login.created_at >=', $startDate);
                $management_login_builder->where('management_login.created_at <=', $endDate);
                $management_login_builder->whereIn('management_login.staff_id', array_column($staffIds, 'id'));
                $management_login_builder->join('management_staff', 'management_login.staff_id = management_staff.id', 'inner');
                $managementLogin = $management_login_builder->orderBy('management_staff.name','ASC')->get();
                if ($results = $managementLogin->getResult()) {
                    $data = array(); $d=0;
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
                    $dompdf = new Dompdf();
                    $html = view('report/current_month_by_date', ['data' => $data]);
                    $dompdf->loadHtml($html);
                    $dompdf->setPaper('A4', 'landscape');
                    $dompdf->render();
                    $filename = 'Management_current_month_by_date_'.date('Ymd').time().'.pdf';
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
                        return $this->respond(['status' => 1, 'message' => 'Staff report', 'url' => base_url($file_path)], 200);
                    }else{
                        return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                    }
                }else{
                    return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
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
    public function lastWeekByName()
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
            $managementBuilder = $db->table('management_staff'); 
            $managementBuilder->where('management_id', $management_id)
                            ->groupBy('id')
                            ->select('id');
            $staffIds = $managementBuilder->get()->getResultArray();
            if (!empty($staffIds)) {
                
                $management_login_builder = $db->table('management_login');
                $startDate = date('Y-m-d', strtotime('last week monday')); 
                $endDate = date('Y-m-d', strtotime('last week sunday')); 
                $management_login_builder->where('management_login.created_at >=', $startDate);
                $management_login_builder->where('management_login.created_at <=', $endDate);
                $management_login_builder->whereIn('management_login.staff_id', array_column($staffIds, 'id'));
                $management_login_builder->join('management_staff', 'management_login.staff_id = management_staff.id', 'inner');
                $managementLogin = $management_login_builder->orderBy('management_staff.name','ASC')->get();
                if ($results = $managementLogin->getResult()) {
                    $data = array(); $d=0;
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
                    $dompdf = new Dompdf();
                    $html = view('report/last_week_by_name', ['data' => $data]);
                    $dompdf->loadHtml($html);
                    $dompdf->setPaper('A4', 'landscape');
                    $dompdf->render();
                    $filename = 'Management_last_week_by_name_'.date('Ymd').time().'.pdf';
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
                        return $this->respond(['status' => 1, 'message' => 'Staff report', 'url' => base_url($file_path)], 200);
                    }else{
                        return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                    }
                }else{
                    return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
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
    public function lastWeekByDate()
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
            $managementBuilder = $db->table('management_staff'); 
            $managementBuilder->where('management_id', $management_id)
                            ->groupBy('id')
                            ->select('id');
            $staffIds = $managementBuilder->get()->getResultArray();
            if (!empty($staffIds)) {
                
                $management_login_builder = $db->table('management_login');
                $startDate = date('Y-m-d', strtotime('last week monday')); 
                $endDate = date('Y-m-d', strtotime('last week sunday')); 
                $management_login_builder->where('management_login.created_at >=', $startDate);
                $management_login_builder->where('management_login.created_at <=', $endDate);
                $management_login_builder->whereIn('management_login.staff_id', array_column($staffIds, 'id'));
                $management_login_builder->join('management_staff', 'management_login.staff_id = management_staff.id', 'inner');
                $managementLogin = $management_login_builder->orderBy('management_login.date','DESC')->get();
                if ($results = $managementLogin->getResult()) {
                    $data = array(); $d=0;
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
                    $dompdf = new Dompdf();
                    $html = view('report/last_week_by_date', ['data' => $data]);
                    $dompdf->loadHtml($html);
                    $dompdf->setPaper('A4', 'landscape');
                    $dompdf->render();
                    $filename = 'Management_last_week_by_date_'.date('Ymd').time().'.pdf';
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
                        return $this->respond(['status' => 1, 'message' => 'Staff report', 'url' => base_url($file_path)], 200);
                    }else{
                        return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                    }
                }else{
                    return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
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
    public function lastMonthByName()
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
            $managementBuilder = $db->table('management_staff'); 
            $managementBuilder->where('management_id', $management_id)
                              ->groupBy('id')
                              ->select('id');
            $staffIds = $managementBuilder->get()->getResultArray();
            if (!empty($staffIds)) {
                
                $management_login_builder = $db->table('management_login');
                $startDate = date('Y-m-01', strtotime('first day of last month')); 
                $endDate = date('Y-m-t', strtotime('last day of last month'));
                $management_login_builder->where('management_login.created_at >=', $startDate);
                $management_login_builder->where('management_login.created_at <=', $endDate);
                $management_login_builder->whereIn('management_login.staff_id', array_column($staffIds, 'id'));
                $management_login_builder->join('management_staff', 'management_login.staff_id = management_staff.id', 'inner');
                $managementLogin = $management_login_builder->orderBy('management_staff.name','ASC')->get();
                if ($results = $managementLogin->getResult()) {
                    $data = array(); $d=0;
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
                    $dompdf = new Dompdf();
                    $html = view('report/last_month_by_name', ['data' => $data]);
                    $dompdf->loadHtml($html);
                    $dompdf->setPaper('A4', 'landscape');
                    $dompdf->render();
                    $filename = 'Management_last_month_by_date_'.date('Ymd').time().'.pdf';
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
                        return $this->respond(['status' => 1, 'message' => 'Staff report', 'url' => base_url($file_path)], 200);
                    }else{
                        return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                    }
                }else{
                    return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
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
    public function lastMonthByDate()
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
            $managementBuilder = $db->table('management_staff'); 
            $managementBuilder->where('management_id', $management_id)
                              ->groupBy('id')
                              ->select('id');
            $staffIds = $managementBuilder->get()->getResultArray();
            if (!empty($staffIds)) {
                
                $management_login_builder = $db->table('management_login');
                $startDate = date('Y-m-01', strtotime('first day of last month')); 
                $endDate = date('Y-m-t', strtotime('last day of last month'));
                $management_login_builder->where('management_login.created_at >=', $startDate);
                $management_login_builder->where('management_login.created_at <=', $endDate);
                $management_login_builder->whereIn('management_login.staff_id', array_column($staffIds, 'id'));
                $management_login_builder->join('management_staff', 'management_login.staff_id = management_staff.id', 'inner');
                $managementLogin = $management_login_builder->orderBy('management_staff.name','ASC')->get();
                if ($results = $managementLogin->getResult()) {
                    $data = array(); $d=0;
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
                    $dompdf = new Dompdf();
                    $html = view('report/last_month_by_date', ['data' => $data]);
                    $dompdf->loadHtml($html);
                    $dompdf->setPaper('A4', 'landscape');
                    $dompdf->render();
                    $filename = 'Management_current_month_by_date_'.date('Ymd').time().'.pdf';
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
                        return $this->respond(['status' => 1, 'message' => 'Staff report', 'url' => base_url($file_path)], 200);
                    }else{
                        return $this->respond(['status' => 0,'message' => 'Failed to generate PDF', 'url' => ''], 200);
                    }
                }else{
                    return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'No staff data found', 'url' => ''], 200);
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