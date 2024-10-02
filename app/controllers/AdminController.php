<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Email as EmailValidator;
use Phalcon\Validation\Validator\StringLength as StringLengthValidator;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Firebase\JWT\JWT;

class AdminController extends Controller
{
    // Load the configuration
    private $config;

    public function onConstruct()
    {
        $this->config = include APP_PATH . '/config/config.php';
    }

    public function signupAction()
    {
        $request = $this->request->getJsonRawBody();
        
        // Validation
        $validation = new Validation();
        $validation->add('name', new StringLengthValidator([
            'max' => 100,
            'min' => 2,
            'messageMaximum' => 'Name is too long',
            'messageMinimum' => 'Name is too short'
        ]));
        $validation->add('email', new EmailValidator([
            'message' => 'Email is not valid'
        ]));
        $validation->add('password', new StringLengthValidator([
            'max' => 255,
            'min' => 6,
            'messageMaximum' => 'Password is too long',
            'messageMinimum' => 'Password is too short'
        ]));
    
        $messages = $validation->validate((array)$request);
        if (count($messages) > 0) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'messages' => $messages]);
            return $response;
        }
    
        // Create a new admin
        $admin = new Admin();
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->password = password_hash($request->password, PASSWORD_DEFAULT);
        $admin->phone_number = $request->phone_number;
        $admin->role_id = 3; // Default role (Tables Admin)
        $admin->is_verified = 0; // Not verified by default
    
        if ($admin->save() === false) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'messages' => $admin->getMessages()]);
            return $response;
        }
    
        $response = new Response();
        $response->setJsonContent(['status' => 'success', 'message' => 'Admin registered successfully']);
        return $response;
    }

    public function loginAction()
    {
        $request = $this->request->getJsonRawBody();
    
        // Validation
        if (empty($request->email) || empty($request->password)) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'message' => 'Email and password are required']);
            return $response;
        }
    
        // Check if the admin exists
        $admin = Admin::findFirst([
            'conditions' => 'email = :email:',
            'bind' => ['email' => $request->email]
        ]);
    
        if (!$admin || !password_verify($request->password, $admin->password)) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'message' => 'Invalid credentials']);
            return $response;
        }
    
        // Check if the admin is verified
        if ($admin->is_verified != 1) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'message' => 'Admin is not verified']);
            return $response;
        }
    
        // Generate and send OTP
        $otp = rand(100000, 999999);
        $admin->otp = $otp;
        $admin->save();
    
        // Send OTP via email
        try {
            $this->sendOtpEmail($admin->email, $otp);
            $response = new Response();
            $response->setJsonContent(['status' => 'success', 'message' => 'OTP sent to email']);
            return $response;
        } catch (Exception $e) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'message' => 'Failed to send OTP: ' . $e->getMessage()]);
            return $response;
        }
    }

    public function verifyOtpAction()
    {
        $request = $this->request->getJsonRawBody();

        // Validation
        if (empty($request->email) || empty($request->otp)) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'message' => 'Email and OTP are required']);
            return $response;
        }

        // Check if the admin exists
        $admin = Admin::findFirst([
            'conditions' => 'email = :email:',
            'bind' => ['email' => $request->email]
        ]);

        if (!$admin || $admin->otp !== $request->otp) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'message' => 'Invalid OTP']);
            return $response;
        }

        // Retrieve the admin role name
        $role = $admin->getRole();

        if (!$role) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'message' => 'Role not found']);
            return $response;
        }

        // Generate JWT token with role name
        $token = $this->generateJwt($admin->id, $role->role_name);

        $response = new Response();
        $response->setJsonContent([
            'status' => 'success', 
            'token' => $token, 
            'role' => $role->role_name
        ]);
        return $response;
    }

    private function sendOtpEmail($email, $otp)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->SMTPDebug = 0; 
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'jeremybundi45@gmail.com';
            $mail->Password   = 'mwpfauuqoolgpdwm'; // Replace with your real email password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('jeremybundi45@gmail.com', 'Swahili Resort');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code';
            $mail->Body    = "Your OTP code is: <b>$otp</b>";

            $mail->send();
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    private function generateJwt($adminId, $roleName)
    {
        $key = $this->config->jwt->secret_key; // Your secret key
        $payload = [
            'iss' => 'your-issuer', // Issuer of the token
            'aud' => 'your-audience', // Audience of the token
            'iat' => time(), // Issued at
            'exp' => time() + 36000, // Expiration time (1 hour)
            'adminId' => $adminId,
            'role' => $roleName // Pass role name instead of ID
        ];

        return JWT::encode($payload, $key, 'HS256');
    }
}
