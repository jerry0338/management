<?php

namespace App\Controllers\Api\Management;

use App\Controllers\BaseController;

use App\Models\{Management, ManagementType, ManagementLogin, ManagementStaff};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use \Firebase\JWT\JWT;

class AuthController extends BaseController
{
    use ResponseTrait;

    public function register()
    {
        $rules = [
            'management_type_id' => ['rules' => 'required'],
            'first_name' => ['rules' => 'required|min_length[3]|max_length[255]'],
            'last_name' => ['rules' => 'required|min_length[3]|max_length[255]'],
            'mobile_number' => ['rules' => 'required|min_length[10]|max_length[10]'],
            'title'  => ['rules' => 'required'],
            'email' => ['rules' => 'required|min_length[4]|max_length[255]|valid_email|is_unique[management.email]'],
            'password' => ['rules' => 'required|min_length[8]|max_length[255]']
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {

            helper('text');

            $qr_key = random_string('alnum', 16);

            $model = new Management();

            $filename = null;
            if ($body->profile_image) {
                $base64_image = $body->profile_image;
                list($type, $data) = explode(';', $base64_image);
                list(, $data) = explode(',', $data);

                $image_data = base64_decode($data);
                $filename = time().uniqid().'.png';
                
                $folder = '../public/uploads/management_profile/';
                
                if (!file_exists($folder)) {
                    mkdir($folder, 0777, true);
                } 
                
                file_put_contents($folder . $filename, $image_data);
            }else{
                $filename = '';
            }

            $data = [
                'management_type_id' => $body->management_type_id,
                'first_name'    => $body->first_name,
                'last_name'     => $body->last_name,
                'profile_image' => $filename,
                'mobile_number' => $body->mobile_number,
                'email'         => $body->email,
                'title'         => $body->title,
                'password'      => password_hash($body->password, PASSWORD_DEFAULT),
                'unique_key'    => $qr_key,
                'latitude'      => $body->latitude ?? '',
                'longitude'     => $body->longitude ?? '',
            ];
            if($model->save($data)){
                return $this->respond(['status' => 1, 'message' => 'Management has been registered.'], 200);
            }else{
                return $this->respond(['status' => 0,'message' => 'Management not registered.'], 200);
            }

            
        } else {
            $response = [
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
            $managementModel = new Management();
            $management = $managementModel->where('email', $body->email)->first();
            if (is_null($management)) {
                return $this->respond(['status' => 1,'message' => 'Email is available.'], 200);
            }else{
                return $this->respond(['status' => 0,'message' => 'Email already taken.'], 200);
            }  
        } else {
            return $this->respond(['status' => 0,'message' => 'Invalid Inputs'], 200);
        }
    }
    
    public function login()
    {

        $rules = [
            'email' => ['rules' => 'required|valid_email'],
            'password' => ['rules' => 'required'],
        ];

        $body = json_decode($this->request->getBody());

        if ($this->validate($rules)) {

            $managementModel = new Management();
            $managementTypeModel = new ManagementType();
            $managementStaffModel = new ManagementStaff();

            $email = $body->email;
            $password = $body->password;

            $management = $managementModel->where('email', $email)->first();

            if (is_null($management)) {

                $managementStaff = $managementStaffModel->where('email', $email)->first();
                if (is_null($managementStaff)) {
                    return $this->respond(['status' => 0,'message' => 'Invalid email or password.'], 200);
                }else{
                    $pwd_verify = password_verify($password, $managementStaff['password']);
                    if (!$pwd_verify) {
                        return $this->respond(['status' => 0,'message' => 'Invalid email or password.'], 200);
                    }
                    $management_id = $managementStaff['id'];
                    $unique_key = $managementStaff['unique_key'];
                    $email = $managementStaff['email'];
                    $name = explode(' ', $managementStaff['name']);
                    $first_name = $name[0];
                    $last_name = isset($name[1]) ? $name[1] : '';
                    $managementData = $managementModel->where('id', $managementStaff['management_id'])->select(['title'])->first();
                    $title = $managementData['title'];
                    $mobile_number = $managementStaff['mobile_number'];
                    $profile_image = '';
                    $type = 'staff';
                    $managementType = $managementTypeModel->where('type', $managementStaff['role'])->select(['id as management_type_id', 'type as management_type'])->first();

                    $managementLoginModel = new ManagementLogin();
                    $data = [
                        'staff_id' => $management_id,
                        'date'     => date('Y-m-d'),
                        'time_in' => date('H:i:s')
                    ];                    
                    $managementLoginModel->insert($data);
                }
            }else{
                $pwd_verify = password_verify($password, $management['password']);
                if (!$pwd_verify) {
                    return $this->respond(['status' => 0,'message' => 'Invalid email or password.'], 200);
                }
                $management_id = $management['id'];
                $unique_key = $management['unique_key'];
                $email = $management['email'];
                $first_name = $management['first_name'];
                $last_name = $management['last_name'];
                $title = $management['title'];
                $mobile_number = $management['mobile_number'];
                $folder = 'uploads/management_profile/';
                $baseURL = base_url($folder);
                if (file_exists('../public/' . $folder . $management['profile_image'])) {
                    $profile_image = $baseURL . $management['profile_image'];
                } else {
                    $profile_image = '';
                }
                $type = 'admin';
                $managementType = $managementTypeModel->where('id', $management['management_type_id'])->select(['id as management_type_id', 'type as management_type'])->first();
            }
            $key = getenv('JWT_SECRET');
            $iat = time(); // current timestamp value
            $exp = $iat + 36000;

            $data = [
                "management_id" => $management_id,
                "type" => $type,
                "unique_key" => $unique_key,
                "email" => $email,
                "first_name" => $first_name,
                "last_name" => $last_name,
                "title" => $title,
                "mobile_number" => $mobile_number,
                "profile_image" => $profile_image,
                "management_type" => $managementType,
            ];

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
                'message' => 'Invalid Inputs'
            ];
            return $this->fail($response, 409);
        }
    }
    
    public function forgotPassword()
    {

        $rules = [
            'email' => ['rules' => 'required|valid_email']
        ];

        $bodyData = json_decode($this->request->getBody());
        $db = \Config\Database::connect();
        if ($this->validate($rules)) {
            $managementModel = new Management();
            $management = $managementModel->where('email', $bodyData->email)->first();
            if ($management) {
                $code = rand(100000,999999);
                $body = "<p>UVisitor account</p>";
                $body .= "<h2>Password reset code</h2>";
                $body .= "<p>Please use this code to reset the password for the UVisitor account ".$management['email'].".</p>";
                $body .= "<h3>Here is your code:'".$code."'</h3>";
                $body .= "<p></p>";
                $body .= "<p>Thanks,</p>";
                $body .= "<p>The UVisitor account team</p>";
                $headers = "From: UVisitor<no-reply@uvisitor.com>\r\n";
                $headers .= "Reply-To: UVisitor<no-reply@uvisitor.com>\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
                @mail(strtolower($management['email']), 'UVisitor Password reset code', $body, $headers, '-f no-reply@uvisitor.com');
                
                $managementUpdate = $db->table('management');
                $managementUpdate = $managementUpdate->where('email', $bodyData->email);
                $data = [
                    'code'  => $code
                ];
                $managementUpdate->update($data);
                        
                return $this->respond(['status' => 1,'message' => 'Reset Code sended.', 'code' => $code], 200);
            }else{
                return $this->respond(['status' => 0,'message' => 'Email not available.'], 200);
            } 

            
        } else {
            return $this->respond(['status' => 0,'message' => 'Invalid Inputs'], 200);
        }
    }
    
    public function codeCheck()
    {
        $rules = [
            'email' => ['rules' => 'required|valid_email'],
            'code' => ['rules' => 'required|min_length[6]|max_length[6]']
        ];

        $body = json_decode($this->request->getBody());
        if ($this->validate($rules)) {
            $managementModel = new Management();
            $management = $managementModel->where('email', $body->email)->first();
            if ($management) {
                if($management['code'] == $body->code){
                    return $this->respond(['status' => 1,'message' => 'Code is match.'], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Code is wrong.'], 200);
                }
            }else{
                return $this->respond(['status' => 0,'message' => 'Email not available.'], 200);
            } 
        } else {
            return $this->respond(['status' => 0,'message' => 'Invalid Inputs'], 200);
        }
    }
    
    public function updatePassword()
    {
        $rules = [
            'email' => ['rules' => 'required|valid_email'],
            'password' => ['rules' => 'required|min_length[8]|max_length[255]']
        ];

        $body = json_decode($this->request->getBody());
        $db = \Config\Database::connect();
        if ($this->validate($rules)) {
            $managementModel = new Management();
            $management = $managementModel->where('email', $body->email)->first();

            if ($management) {
                $managementUpdate = $db->table('management');
                $managementUpdate = $managementUpdate->where('email', $body->email);
                $data = [
                    'password'  => password_hash($body->password, PASSWORD_DEFAULT)
                ];
                if($managementUpdate->update($data)){
                    return $this->respond(['status' => 1,'message' => 'Password updated.'], 200);
                }else{
                    return $this->respond(['status' => 0,'message' => 'Password not update.'], 200);
                }
                
            }else{
                return $this->respond(['status' => 0,'message' => 'Email not available.'], 200);
            } 

            
        } else {
            return $this->respond(['status' => 0,'message' => 'Invalid Inputs'], 200);
        }
    }
}
