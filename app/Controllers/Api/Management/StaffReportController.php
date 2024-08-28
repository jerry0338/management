<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{Visitor, VisitorRecords, UserVisitor, VisitorRecordKeys, ManagementKey};
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
}