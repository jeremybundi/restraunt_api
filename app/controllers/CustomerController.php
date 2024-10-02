<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Firebase\JWT\JWT;

class CustomerController extends Controller
{
    private $config;

    public function onConstruct()
    {
        // Load your config file to get JWT secret key and other settings
        $this->config = include APP_PATH . '/config/config.php';
    }

    public function loginAction()
    {
        $request = $this->request->getJsonRawBody();

        // Validate email is provided
        if (empty($request->email)) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'message' => 'Email is required']);
            return $response;
        }

        // Find the customer by email
        $customer = Customers::findFirst([
            'conditions' => 'email = :email:',
            'bind'       => ['email' => $request->email]
        ]);

        if (!$customer) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'message' => 'Customer not found']);
            return $response;
        }

        // Generate and store OTP
        $otp = rand(100000, 999999);
        $customer->otp = $otp;
        if (!$customer->save()) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'message' => 'Failed to generate OTP']);
            return $response;
        }

        // Send OTP to email
        try {
            $this->sendOtpEmail($customer->email, $otp);
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

        // Validate email and OTP
        if (empty($request->email) || empty($request->otp)) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'message' => 'Email and OTP are required']);
            return $response;
        }

        // Find the customer by email and OTP
        $customer = Customers::findFirst([
            'conditions' => 'email = :email: AND otp = :otp:',
            'bind'       => ['email' => $request->email, 'otp' => $request->otp]
        ]);

        if (!$customer) {
            $response = new Response();
            $response->setJsonContent(['status' => 'error', 'message' => 'Invalid OTP']);
            return $response;
        }

        // Generate JWT token with "customer" role
        $token = $this->generateJwt($customer->id);

        // Send success response with token and role
        $response = new Response();
        $response->setJsonContent([
            'status' => 'success',
            'token'  => $token,
            'role'   => 'customer'
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

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code';
            $mail->Body    = "Your OTP code is: <b>$otp</b>";

            $mail->send();
        } catch (Exception $e) {
            throw new Exception('OTP could not be sent. Mailer Error: ' . $mail->ErrorInfo);
        }
    }

    private function generateJwt($customerId)
    {
        $key = $this->config['jwt']['secret_key']; // Secret key from your config file
        $payload = [
            'iss' => 'your-issuer',    // Issuer of the token
            'aud' => 'your-audience',  // Audience of the token
            'iat' => time(),           // Issued at
            'exp' => time() + 3600,    // Expiration time (1 hour)
            'customerId' => $customerId,
            'role' => 'customer'
        ];

        return JWT::encode($payload, $key, 'HS256'); // Generate JWT with HS256 algorithm
    }
}
