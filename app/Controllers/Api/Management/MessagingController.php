<?php
namespace App\Controllers\Api\Management;
require(APPPATH . "Libraries/Twilio/autoload.php");

use App\Controllers\BaseController;

use App\Models\{Management, ManagementPerson, Visitor, UserVisitor};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use Twilio\Rest\Client;
class MessagingController extends BaseController
{
    use ResponseTrait;
    
    public function send()
    {
        $rules = [
            'management_id' => ['rules' => 'required'],
            'visitor_id' => ['rules' => 'required'],
            'person_id' => ['rules' => 'required']
        ];
        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {
            try {
                
                $managementPerson = new ManagementPerson();
                $managementPersonData = $managementPerson->where('id', $body->person_id)->first();
                if($managementPersonData  || 1 > 0){
                    // $mobile_number = $managementPersonData['mobile_number'];
                    
                    $visitor = new Visitor();
                    $visitorData = $visitor->where('id', $body->visitor_id)->first();
                    
                    $body = $visitorData['first_name'].' '.$visitorData['last_name'].' has come to meet you';
                    
                    $sid    = "ACd8de3dee37827812f2abbda1d4c99720";
                    $token  = "fb5f2fb501d2c5a2b2aac094f64ef8b9";
                    $twilio = new Client($sid, $token);
                
                    $message = $twilio->messages
                      ->create("+919780522931", // to
                        array(
                          "from" => "+12074898610",
                          "body" => $body
                        )
                      );
                
                    // print($message->sid);
                    return $this->respond(['status' => 1,'message' => 'Management sms send'], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Management sms not send'], 200);
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