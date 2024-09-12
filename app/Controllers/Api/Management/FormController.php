<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{Management, ManagementStaff, ManagementForm, VisitorRecords, VisitorRecordKeys};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;



class FormController extends BaseController
{
    use ResponseTrait;
    
    public function add()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required'],
            'title' => ['rules' => 'required'],
            'form_data' => ['rules' => 'required']
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

                $managementForm = new ManagementForm();
                $management = $managementForm->where('management_id', $management_id)->where('title', $body->title)->first();
    
                if (is_null($management)) {
                    $managementForm = new ManagementForm();
                    $data = [
                        'management_id'  => $management_id,
                        'title'         => $body->title,
                        'form_data'       => serialize($body->form_data)
                    ];
                
                    $managementForm->insert($data);
                    return $this->respond(['status' => 1, 'message' => 'Form added successfully'], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Form already register.'], 200);
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
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required'],
            'management_form_id' => ['rules' => 'required'],
            'title' => ['rules' => 'required'],
            'form_data' => ['rules' => 'required']
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

                $managementForm = new ManagementForm();
                $management = $managementForm->where('id NOT LIKE', $body->management_form_id)->where('management_id', $management_id)->where('title', $body->title)->first();
                if (is_null($management)) {
                    $db = \Config\Database::connect();
                    $management_Form = $db->table('management_form');
                    $managementForm = $management_Form->where('id', $body->management_form_id);
                    $data = [
                        'title'  => $body->title,
                        'form_data'  => serialize($body->form_data)
                    ];
    
                    if($managementForm->update($data)){
                        return $this->respond(['status' => 1, 'message' => 'Form updated successfully'], 200);
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Form not updat.please, try again.'], 200);
                    }
                }else{
                    return $this->respond(['status' => 0,'message' => 'Form already added.'], 200);
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
            'management_form_id' => ['rules' => 'required']
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
                $managementForm = $db->table('management_form');
                
                // Assuming $id contains the ID of the row you want to delete
                $managementForm->where('management_id', $management_id);
                $managementForm->where('id', $body->management_form_id);
                if ($managementForm->delete()){
                    return $this->respond(['status' => 1, 'message' => 'Form deleted.'], 200);
                }else{
                    return $this->respond(['status' => 0, 'message' => 'Form not delete.please, try again.'], 200);
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
                
                $managementForm = new ManagementForm();
                $managementForm = $managementForm->where('management_id', $management_id)->get();
                $data = array(); $d=0;
                if ($results = $managementForm->getResult()) {
                    foreach ($results as $key => $result) {
                        $data[$d]['management_form_id'] = $result->id;
                        $data[$d]['title'] = $result->title;
                        $data[$d]['form_data'] = unserialize($result->form_data);
                        $data[$d]['status'] = $result->status;
                        $data[$d]['created_at'] = $result->created_at;
                        $d++;
                    }
                }
                
                if(sizeof($data) > 0){
                    return $this->respond(['status' => 1, 'message' => 'Form data', 'data' => $data], 200);
                }else{
                    return $this->respond(['status' => 0, 'message' => 'No Form data found ', 'data' => array()], 200);
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
    
    public function active()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'management_type' => ['rules' => 'required'],
            'management_form_id' => ['rules' => 'required']
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
                $managementForm = new ManagementForm();
                $management = $managementForm->where('id', $body->management_form_id)->where('status', 1)->first();
                if (is_null($management)) {
                    $managementForm = new ManagementForm();
                    $management = $managementForm->where('management_id', $management_id)->where('status', 1)->first();
                    if (is_null($management)) {
                        $db = \Config\Database::connect();
                        $management_Form = $db->table('management_form');
                        $managementForm = $management_Form->where('id', $body->management_form_id);
                        $data = [
                            'status'  => 1
                        ];
                        if($managementForm->update($data)){
                            return $this->respond(['status' => 1, 'message' => 'Form actived successfully'], 200);
                        }else{
                            return $this->respond(['status' => 0, 'message' => 'Form not active.please, try again.'], 200);
                        }
                    }else{
                        return $this->respond(['status' => 0,'message' => 'Form already actived. Please, first deactive.'], 200);
                    }
                }else{
                    $db = \Config\Database::connect();
                    $management_Form = $db->table('management_form');
                    $managementForm = $management_Form->where('id', $body->management_form_id);
                    $data = [
                        'status'  => 0
                    ];
                    if($managementForm->update($data)){
                        return $this->respond(['status' => 1, 'message' => 'Form deactive successfully'], 200);
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Form not deactive.please, try again.'], 200);
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
    
    public function activeData()
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

                $managementForm = new ManagementForm();
                $management = $managementForm->where('management_id', $management_id)->where('status', 1)->first();
                $data = array();
                if (is_null($management)) {
                    return $this->respond(['status' => 0,'message' => 'Form not active.'], 200);
                }else{
                    $data['form_data'] = unserialize($management['form_data']);
                    return $this->respond(['status' => 1, 'message' => 'Form data', 'data' => $data], 200);
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