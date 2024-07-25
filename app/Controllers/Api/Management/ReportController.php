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
            $visitor_builder = $db->table('visitor_records');
            $visitor_builder->where('purpose_entry', 'LOG-IN');
            $visitor_builder->where('management_id', $body->management_id);
            $get_visitor = $visitor_builder->orderBy('created_at','DESC')->get();
            if ($results = $get_visitor->getResult()) {

                $data = array(); $d=0;
                foreach ($results as $key => $result) {
                    
                    $visitor = $db->table('visitors');
                    $visitor->where('id', $result->visitor_id);
                    $visitor->limit(1);
                    $visitor = $visitor->get();
                    $visitorRecords = $visitor->getFirstRow();
                                    
                    $visitor_type = $db->table('visitor_type');
                    $visitor_type->where('id', $visitorRecords->visitor_type_id);
                    $visitor_type->limit(1);
                    $visitor_type = $visitor_type->get();
                    $visitorTypeRecords = $visitor_type->getFirstRow();
                    $data[$d]['type_name'] = $visitorTypeRecords->type;
                                        
                    $data[$d]['name'] = $visitorRecords->first_name.' '.$visitorRecords->last_name;
                    $data[$d]['company_name'] = $visitorRecords->company_name;
                    $data[$d]['email'] = $visitorRecords->email;
                    $data[$d]['mobile_number'] = $visitorRecords->mobile_number;
                    $data[$d]['created_at'] = $result->created_at;
                    
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
                file_put_contents($file_path, $dompdf->output());
                return $this->respond(['status' => 1, 'message' => 'Current visitor report', 'url' => $file_path], 200);
            }else{
                return $this->respond(['status' => 0,'message' => 'No active visitor found', 'data' => array()], 200);
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