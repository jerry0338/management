<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{ManagementAlert, ManagementKeyAlert};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class AlertController extends BaseController
{
    use ResponseTrait;
    
    public function alertData()
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

                $managementAlert = new ManagementAlert();
                $managementAlert = $managementAlert->where('management_id', $management_id)->first();
                if (is_null($managementAlert)) {

                    $alerts = [
                        [
                            'slug' => 'notify_when_visitor_sign_in',
                            'title' => 'Notify When Visitor Sign IN',
                        ],
                        [
                            'slug' => 'notify_when_visitor_sign_out',
                            'title' => 'Notify When Visitor Sign OUT',
                        ],
                        [
                            'slug' => 'notify_when_visitor_sign_out_not_return_keys',
                            'title' => 'Notify When Visitor Sign OUT not Return Keys',
                        ],
                        [
                            'slug' => 'visitor_still_on_site_after',
                            'title' => 'Visitor Still On Site After',
                        ],
                        [
                            'slug' => 'evacuation_alert',
                            'title' => 'Evacuation Alert',
                        ],
                        [
                            'slug' => 'roll_call_alert',
                            'title' => 'Roll call Alert',
                        ],
                    ];
                    
                    foreach ($alerts as $alert) {
                        $management = new ManagementAlert();
                        $data = [
                            'management_id' => $management_id,
                            'slug' => $alert['slug'],
                            'title' => $alert['title'],
                            'status' => '1',
                        ];
                        $management->insert($data); 
                        $inserted_id = $management->insertID();
                    }
                }

                $alertData = $this->singleAlertData($management_id);
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
        $bodyData = json_decode($this->request->getBody());
        $db = \Config\Database::connect();
        try {
            $bodys = $bodyData->data;
            foreach($bodys as $body){
                $ManagementAlert = new ManagementAlert();
                $ManagementAlert = $ManagementAlert->where('id', $body->alert_id)->first();
                if (!is_null($ManagementAlert)) {
                    $managementAlertUpdate = $db->table('management_alert');
                    $managementAlertUpdate = $managementAlertUpdate->where('id', $body->alert_id);
                    $data = [
                        'set_alert_time'  => $body->set_alert_time,
                        'method'  => $body->method,
                        'visitor'  => $body->visitor,
                        'admin_staff'  => $body->admin_staff,
                        'whome_visiting'  => $body->whome_visiting,
                        'turn_alert'  => $body->turn_alert,
                    ];
                    $managementAlertUpdate->update($data);
                }
            }
            return $this->respond(['status' => 1,'message' => 'Management Alert updated'], 200);
        } catch (Exception $exception) {
            return response()->json(['status' => 0, 'msg' => 'Something went wrong.'], 500);
        } 
    } 

    public function keyAlertData()
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
                $managementAlert = new ManagementKeyAlert();
                $managementAlert = $managementAlert->where('management_id', $management_id)->first();
                if (is_null($managementAlert)) {

                    $alerts = [
                        [
                            'slug' => 'contractor_end_of_day_alert',
                            'title' => 'Contractor End of Day Alert',
                        ],
                        [
                            'slug' => 'contractor_more_than_24_hrs',
                            'title' => 'Contractor More than 24 hrs',
                        ]
                    ];
                    
                    foreach ($alerts as $alert) {
                        $management = new ManagementKeyAlert();
                        $data = [
                            'management_id' => $management_id,
                            'slug' => $alert['slug'],
                            'title' => $alert['title'],
                            'status' => '1',
                        ];
                        $management->insert($data); 
                    }
                }

                $alertData = $this->singleKeyAlertData($management_id);
                return $this->respond(['status' => 1,'message' => 'Management Key Alert Data', 'data' => $alertData], 200);
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
    
    public function updateKeyAlertData()
    {
        $bodyData = json_decode($this->request->getBody());
        $db = \Config\Database::connect();
        try {
            $bodys = $bodyData->data;
            foreach($bodys as $body){
                $ManagementAlert = new ManagementKeyAlert();
                $ManagementAlert = $ManagementAlert->where('id', $body->alert_id)->first();
                if (!is_null($ManagementAlert)) {
                    $managementAlertUpdate = $db->table('management_key_alert');
                    $managementAlertUpdate = $managementAlertUpdate->where('id', $body->alert_id);
                    $data = [
                        'set_time'  => $body->set_time,
                        'alert_status'  => $body->alert_status,
                    ];
                    $managementAlertUpdate->update($data);
                }
            }
            return $this->respond(['status' => 1,'message' => 'Management Key Alert updated'], 200);
        } catch (Exception $exception) {
            return response()->json(['status' => 0, 'msg' => 'Something went wrong.'], 500);
        } 
    } 

    public function singleAlertData($management_id)
    {
        $managementAlert = new ManagementAlert();
        $managementAlerts = $managementAlert->where('management_id', $management_id)->where('status', 1)->get();
        $data = array(); $d=0;
        if ($results = $managementAlerts->getResult()) {
            foreach ($results as $key => $result) {
                $data[$d]['alert_id'] = $result->id;
                $data[$d]['slug'] = $result->slug;
                $data[$d]['title'] = $result->title;
                $data[$d]['set_alert_time'] = $result->set_alert_time;
                $data[$d]['method'] = $result->method;
                $data[$d]['visitor'] = $result->visitor;
                $data[$d]['admin_staff'] = $result->admin_staff;
                $data[$d]['whome_visiting'] = $result->whome_visiting;
                $data[$d]['turn_alert'] = $result->turn_alert;
                $d++;
            }
        }
        return $data;
    }
    
    public function singleKeyAlertData($management_id)
    {
        $managementKeyAlert = new ManagementKeyAlert();
        $managementKeyAlerts = $managementKeyAlert->where('management_id', $management_id)->where('status', 1)->get();
        $data = array(); $d=0;
        if ($results = $managementKeyAlerts->getResult()) {
            foreach ($results as $key => $result) {
                $data[$d]['alert_id'] = $result->id;
                $data[$d]['slug'] = $result->slug;
                $data[$d]['title'] = $result->title;
                $data[$d]['set_time'] = $result->set_time;
                $data[$d]['alert_status'] = $result->alert_status;
                $d++;
            }
        }
        return $data;
    }
}