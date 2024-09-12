<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{Management, ManagementStaff, VisitorRecords, ManagementKey, ManagementQuestion};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class QuestionController extends BaseController
{
    use ResponseTrait;
    
    public function add()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required'],
            'question' => ['rules' => 'required'],
            'type' => ['rules' => 'required']
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

                $managementQuestion = new ManagementQuestion();
                $management = $managementQuestion->where('management_id', $management_id)->where('question', $body->question)->first();
    
                if (is_null($management)) {
                    $managementQuestion = new ManagementQuestion();
                    $data = [
                        'management_id'  => $management_id,
                        'question'         => $body->question,
                        'type'       => $body->type
                    ];
                
                    $managementQuestion->insert($data);
                    return $this->respond(['status' => 1, 'message' => 'Question register successfully'], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Question already register.'], 200);
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
    
    public function edit()
    {
        $rules = [
            'management_question_id' => ['rules' => 'required'],
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required'],
            'question' => ['rules' => 'required'],
            'type' => ['rules' => 'required']
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

                $managementQuestion = new ManagementQuestion();
                $management = $managementQuestion->where('id NOT LIKE', $body->management_question_id)->where('management_id', $management_id)->where('question', $body->question)->first();
                if (is_null($management)) {
                    $db = \Config\Database::connect();

                    $management_Question = $db->table('management_question');
                    $managementQuestion = $management_Question->where('id', $body->management_question_id);
                    $data = [
                        'question'  => $body->question,
                        'type'  => $body->type
                    ];
        
                    if($managementQuestion->update($data)){
                        return $this->respond(['status' => 1, 'message' => 'Question updated successfully'], 200);
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Question not updat.please, try again.'], 200);
                    }
                    
                }else{
                    return $this->respond(['status' => 0,'message' => 'Question already added.'], 200);
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
    
    public function delete()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required'],
            'management_question_id' => ['rules' => 'required']
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

                $db = \Config\Database::connect();
                $managementQuestion = $db->table('management_question');
                
                // Assuming $id contains the ID of the row you want to delete
                $managementQuestion->where('management_id', $management_id);
                $managementQuestion->where('id', $body->management_question_id);
                if ($managementQuestion->delete()){
                    return $this->respond(['status' => 1, 'message' => 'Question deleted.'], 200);
                }else{
                    return $this->respond(['status' => 0, 'message' => 'Question not delete.please, try again.'], 200);
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

                $managementQuestion = new ManagementQuestion();
                $managementQuestion = $managementQuestion->where('management_id', $management_id)->get();
                $data = array(); $d=0;
                if ($results = $managementQuestion->getResult()) {
                    foreach ($results as $key => $result) {
                        $data[$d]['management_question_id'] = $result->id;
                        $data[$d]['question'] = $result->question;
                        $data[$d]['type'] = $result->type;
                        $data[$d]['created_at'] = $result->created_at;
                        $d++;
                    }
                }
                
                if(sizeof($data) > 0){
                    return $this->respond(['status' => 1, 'message' => 'Question data', 'data' => $data], 200);
                }else{
                    return $this->respond(['status' => 0, 'message' => 'No Question data found ', 'data' => array()], 200);
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