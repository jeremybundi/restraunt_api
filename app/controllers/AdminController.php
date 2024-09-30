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
        $admin->role_id = 3; // Default role
        $admin->is_verified = 0; // Not verified by default
    
        if ($admin->save() === false) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'messages' => $admin->getMessages()]);
            return $response;
        }
    
        // Correct response creation
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

        // Generate JWT token
        $token = $this->generateJwt($admin->id, $admin->role_id);

        $response = new Response();
        $response->setJsonContent(['status' => 'success', 'token' => $token, 'role' => $admin->role_id]);
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
            $mail->Password   = 'your-email-password'; // Make sure to replace this
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('jeremybundi45@gmail.com', 'SWahili Resort');
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

    private function generateJwt($adminId, $roleId)
    {
        $key = $this->config->jwt->secret_key; // Your secret key
        $payload = [
            'iss' => 'your-issuer', // Issuer of the token
            'aud' => 'your-audience', // Audience of the token
            'iat' => time(), // Issued at
            'exp' => time() + 3600, // Expiration time (1 hour)
            'adminId' => $adminId,
            'roleId' => $roleId
        ];

        return JWT::encode($payload, $key, 'HS256'); // Include the algorithm parameter
    }
}
