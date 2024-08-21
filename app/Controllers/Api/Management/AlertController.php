<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{ManagementAlert, ManagementAlertData};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class AlertController extends BaseController
{
    use ResponseTrait;
    
    public function alertData()
    {
        $rules = [
            'management_id' => ['rules' => 'required']
        ];
        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                $managementAlert = new ManagementAlert();
                $managementAlert = $managementAlert->where('management_id', $body->management_id)->first();
                if (is_null($managementAlert)) {

                    $management = new ManagementAlert();
                    $data = [
                        'management_id' => $body->management_id,
                        'slug' => 'notify_when_visitor_sign_in',
                        'title' => 'Notify When Visitor Sign IN',
                    ];
                    $management->insert($data);  

                }

                $alertData = $this->singleAlertData($body->management_id);
                return $this->respond(['status' => 1,'message' => 'Management Alert Data', 'data' => $alertData], 200);
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
    
    public function updateAlertData()
    {
        $rules = [
            'management_id' => ['rules' => 'required']
        ];
        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                $ManagementAlert = new ManagementAlert();
                $ManagementAlert = $ManagementAlert->where('management_id', $body->management_id)->first();
                if (is_null($management)) {
                    return $this->respond(['status' => 0, 'message' => 'Management Alert Not available', 'data' => array()], 200);
                }else{
                    $managementAlertUpdate = $db->table('management_alert');
                    $managementAlertUpdate = $managementAlertUpdate->where('management_id', $body->management_id);
                    $data = [
                        'still_on_site_alert'  => $body->still_on_site_alert,
                        'sign_in_method'  => $body->sign_in_method,
                        'sign_out_method'  => $body->sign_out_method,
                        'sign_out_knr_method'  => $body->sign_out_knr_method,
                        'still_on_site_method'  => $body->still_on_site_method,
                        'sign_in_visitor'  => $body->sign_in_visitor,
                        'sign_out_visitor'  => $body->sign_out_visitor,
                        'sign_out_knr_visitor'  => $body->sign_out_knr_visitor,
                        'still_on_site_visitor'  => $body->still_on_site_visitor,
                        'sign_in_staff'  => $body->sign_in_staff,
                        'sign_out_staff'  => $body->sign_out_staff,
                        'sign_out_knr_staff'  => $body->sign_out_knr_staff,
                        'still_on_site_staff'  => $body->still_on_site_staff,
                        'sign_in_wvisiting'  => $body->sign_in_wvisiting,
                        'sign_out_wvisiting'  => $body->sign_out_wvisiting,
                        'sign_out_knr_wvisiting'  => $body->sign_out_knr_wvisiting,
                        'still_on_site_wvisiting'  => $body->still_on_site_wvisiting,
                        'sign_in_status'  => $body->sign_in_status,
                        'sign_out_status'  => $body->sign_out_status,
                        'still_on_site_status'  => $body->still_on_site_status,
                        'sign_out_status'  => $body->sign_out_status,
                    ];
                    $managementAlertUpdate->update($data);

                    return $this->respond(['status' => 1,'message' => 'Management Alert updated'], 200);
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

    public function singleAlertData($management_id)
    {
        return $management_id;
    }
}