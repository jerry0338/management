<?php

namespace App\Controllers\Api\Visitor;

use App\Controllers\BaseController;
use App\Models\{Visitor, VisitorRecords, VisitorRecordKeys};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use \Firebase\JWT\JWT;

class AuthController extends BaseController
{
    use ResponseTrait;

    public function login()
    {
        $rules = [
            'email' => ['rules' => 'required|valid_email'],
            'password' => ['rules' => 'required'],
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {

            $VisitorModel = new Visitor();

            $email = $body->email;
            $password = $body->password;

            $data = $VisitorModel->select('id as visitor_id, unique_key, password, visitor_type_id, visitor_person, state_id, first_name, last_name, email, company_name, wwcc, mobile_number, photo, location_service, is_covid_or_sickness, latitude, longitude')->where('email', $email)->first();

            if (is_null($data)) {
                return $this->respond(['error' => 'Invalid username or password.'], 401);
            }

            $pwd_verify = password_verify($password, $data['password']);

            if (!$pwd_verify) {
                return $this->respond(['error' => 'Invalid username or password.'], 401);
            }

            $key = getenv('JWT_SECRET');
            $iat = time(); // current timestamp value
            $exp = $iat + 36000;

            if ($data) {
                unset($data['password']);
                // Load the URL helper

                
                if(!empty($data['photo'])){
                    $data['photo'] = base_url('uploads/visitor_profile/'.$data['photo']);
                }else{
                    $data['photo'] = base_url('uploads/visitor_profile/profile.jpeg');
                }
                
                $data['qr_code'] = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='.$data['unique_key'];
            }

            $payload = array(
                "iss" => "Issuer of the JWT",
                "aud" => "Audience that the JWT",
                "sub" => "Subject of the JWT",
                "iat" => $iat, //Time the JWT issued at
                "exp" => $exp, // Expiration time of token
                "data" => $data
            );

            $token = JWT::encode($payload, $key, 'HS256');

            $response = [
                'status' => 1,
                'message' => 'Login Succesful',
                'token' => $token,
                'data' => $data,
            ];

            return $this->respond($response, 200);
        } else {
            $response = [
                'status' => 0,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->fail($response, 409);
        }
    }
    
    public function signout()
    {
        $rules = [
            'visitor_id' => ['rules' => 'required'],
            'management_id' => ['rules' => 'required'],
        ];

        $body = json_decode($this->request->getBody());
        if ($this->validate($rules)) {
            
            try {
                
                $visitorRecordsCheck = new VisitorRecords();
                $visitorRecordsCheck = $visitorRecordsCheck->where('visitor_id', $body->visitor_id)->where('management_id', $body->management_id)->where('purpose_entry', 'LOG-IN')->first();
                if($visitorRecordsCheck){
                    
                    $visitorRecordKeys = new VisitorRecordKeys();
                    $visitorRecordKeysDatas = $visitorRecordKeys->where('records_id', $visitorRecordsCheck['id'])->where('status', 0)->first();
                    if(!$visitorRecordKeysDatas){
                        $db = \Config\Database::connect();
    
                        $visitor_builder = $db->table('visitor_records');
                        $visitorRecords = $visitor_builder->where('id', $visitorRecordsCheck['id']);
                        $data = [
                            'purpose_entry'  => 'LOG-IN-OUT'
                        ];
            
                        if($visitorRecords->update($data)){
                            return $this->respond(['status' => 1, 'message' => 'Visitor signout to management.'], 200);
                        }else{
                            return $this->respond(['status' => 0, 'message' => 'Visitor not signout.please, try again.'], 200);
                        }
                    }else{
                        return $this->respond(['status' => 0, 'message' => 'Key not retrun. Please, retrun key and try again.'], 200);
                    }
                }else{
                    return $this->respond(['status' => 2, 'message' => 'Visitor not login.'], 200);
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

    public function register()
    {
        $rules = [
            'state_id' => ['rules' => 'required'],
            'visitor_type_id' => ['rules' => 'required'],
            'first_name' => ['rules' => 'required|min_length[3]|max_length[255]'],
            'last_name' => ['rules' => 'required|min_length[3]|max_length[255]'],
            'company_name' => ['rules' => 'permit_empty|min_length[3]|max_length[255]'],
            'mobile_number' => ['rules' => 'required|min_length[10]|max_length[10]|is_unique[visitors.mobile_number]'],
            'wwcc' => ['rules' => 'permit_empty|min_length[3]|max_length[255]'],
            'email' => ['rules' => 'required|min_length[4]|max_length[255]|valid_email|is_unique[visitors.email]'],
            'is_covid_or_sickness' => ['rules' => 'permit_empty|in_list[0,1]'],
            'location_service' => ['rules' => 'permit_empty|in_list[0,1]'],
            'visitor_person' => ['rules' => 'permit_empty'],
            'password' => ['rules' => 'required|min_length[8]|max_length[255]']
        ];

        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {

            helper('text');

            $VisitorModel = new Visitor();

            $filename = null;

            if ($body->profile_image) {
                $base64_image = $body->profile_image;
                list($type, $data) = explode(';', $base64_image);
                list(, $data) = explode(',', $data);

                $image_data = base64_decode($data);
                $filename = time().uniqid().'.png';
                
                $folder = '../public/uploads/visitor_profile/'; 
                
                if (!file_exists($folder)) {
                    mkdir($folder, 0777, true);
                } 
                
                file_put_contents($folder . $filename, $image_data);
            }else{
                $filename = '';
            }

            $unique_key = random_string('alnum', 16);

            $data = [
                'state_id'              => $body->state_id,
                'visitor_type_id'       => $body->visitor_type_id,
                'visitor_person'        => $body->visitor_person ?? '',
                'first_name'            => $body->first_name,
                'last_name'             => $body->last_name,
                'company_name'          => $body->company_name ?? '',
                'mobile_number'         => $body->mobile_number,
                'wwcc'                  => $body->wwcc ?? '',
                'photo'                 => $filename,
                'form_data'             => isset($body->form_data) ? serialize($body->form_data) : '',
                'email'                 => $body->email,
                'is_covid_or_sickness'  => $body->is_covid_or_sickness ?? '',
                'location_service'      => $body->location_service ?? '',
                'latitude'              => $body->latitude ?? '',
                'longitude'             => $body->longitude ?? '',
                'unique_key'            => $unique_key,
                'password'              => password_hash($body->password, PASSWORD_DEFAULT)
            ];

            $VisitorModel->insert($data);

            return $this->respond(['status' => 1,'message' => 'Visitor has register.', 'data' => [
                'first_name'        => $body->first_name,
                'last_name'         => $body->last_name,
                'email'             => $body->email,
                'unique_key'        => $unique_key,
            ]], 200);
        } else {
            $response = [
                'status' => 0,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->fail($response, 409);
        }
    }
    
    public function emailCheck()
    {
        $rules = [
            'email' => ['rules' => 'required|valid_email']
        ];
        
        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {

            helper('text');

            $VisitorModel = new Visitor();
            
            $visitor = $VisitorModel->where('email', $body->email)->first();

            if (is_null($visitor)) {
                return $this->respond(['status' => 1,'message' => 'Email is available.'], 200);
            }else{
                return $this->respond(['status' => 0,'message' => 'Email not available.'], 200);
            } 
            
        } else {
            $response = [
                'status' => 0,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->fail($response, 409);
        }
    }
    
    public function mobileCheck()
    {
        $rules = [
            'mobile_number' => ['rules' => 'required|min_length[10]|max_length[10]']
        ];
        
        $body = json_decode($this->request->getBody());
        
        if ($this->validate($rules)) {

            helper('text');

            $VisitorModel = new Visitor();
            
            $visitor = $VisitorModel->where('mobile_number', $body->mobile_number)->first();

            if (is_null($visitor)) {
                return $this->respond(['status' => 1,'message' => 'Mobile number is available.'], 200);
            }else{
                return $this->respond(['status' => 0,'message' => 'Mobile number not available.'], 200);
            } 
            
        } else {
            $response = [
                'status' => 0,
                'errors' => $this->validator->getErrors(),
                'message' => 'Invalid Inputs'
            ];
            return $this->fail($response, 409);
        }
    }
}
