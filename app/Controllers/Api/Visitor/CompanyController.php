<?php

namespace App\Controllers\Api\Visitor;

use App\Controllers\BaseController;

use App\Models\{Company};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class CompanyController extends BaseController
{
    use ResponseTrait;
    
    public function add()
    {
        $rules = [
            'name' => ['rules' => 'required']
        ];

        $body = json_decode($this->request->getBody());
        if ($this->validate($rules)) {
            try {
                helper('text');
                
                $company = new Company();
                $companyCheck = $company->where('name', ucfirst($body->name))->first();
    
                if (is_null($companyCheck)) {
                    $companyAdd = new Company();
                    $data = [
                        'name'  => ucfirst($body->name)
                    ];
                
                    $companyAdd->insert($data);
                    return $this->respond(['status' => 1, 'message' => 'Company added'], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Company already exites.'], 200);
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
        try {
            $body = json_decode($this->request->getBody());
            
            $db = \Config\Database::connect();
            $company = $db->table('company');
            
            if(!empty($body->keyword)){
                $company->like('name', ucfirst($body->keyword).'%');
            }
            $companyData = $company->orderBy('name','ASC')->limit(10)->get();
            
            $data = array(); $d=0;
            if ($results = $companyData->getResult()) {
                foreach ($results as $key => $result) {
                    $data[$d]['id'] = $result->id;
                    $data[$d]['name'] = $result->name;
                    $d++;
                }
            }
            
            if(sizeof($data) > 0){
                return $this->respond(['status' => 1, 'message' => 'Company data', 'data' => $data], 200);
            }else{
                return $this->respond(['status' => 0, 'message' => 'No Company data found ', 'data' => array()], 200);
            }
        } catch (Exception $exception) {
            return response()->json(['status' => 0, 'msg' => 'Something went wrong.'], 500);
        } 
    }
}