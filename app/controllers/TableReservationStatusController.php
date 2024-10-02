<?php

use Phalcon\Mvc\Controller;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TableReservationStatusController extends Controller
{
    private function getRoleFromToken()
    {
        // Get the token from the Authorization header
        $authHeader = $this->request->getHeader('Authorization');
        if (!$authHeader) {
            return "No authorization header provided.";
        }

        // Extract the JWT from the Bearer token
        $token = str_replace('Bearer ', '', $authHeader);
        
        // Log the token for debugging
        error_log("Authorization Header: " . $authHeader);
        
        try {
            // Get the secret key from the config file
            $secretKey = $this->di->get('config')->jwt->secret_key;

            // Decode the JWT using Firebase JWT
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));

            // Return the role from the token's payload
            return $decoded->role ?? "Role is missing in the JWT payload.";
        } catch (Firebase\JWT\ExpiredException $e) {
            return "JWT has expired.";
        } catch (Firebase\JWT\SignatureInvalidException $e) {
            return "Invalid JWT signature.";
        } catch (Firebase\JWT\BeforeValidException $e) {
            return "JWT is not yet valid.";
        } catch (Exception $e) {
            return "JWT decode error: " . $e->getMessage();
        }
    }

    // Confirm table reservation
    public function confirmAction($id)
    {
        // Get the role from the token
        $role = $this->getRoleFromToken();
        if ($role === null || !in_array($role, ['General Admin', 'Tables Admin'])) {
            return $this->response->setStatusCode(403, 'Forbidden')->setJsonContent([
                "status" => "error",
                "message" => "Access denied. You do not have permission to confirm reservations. Your role is: " . ($role ?? 'undefined')
            ]);
        }

        // Fetch the reservation by ID
        $reservation = TableReservations::findFirst($id);
        if (!$reservation) {
            return $this->response->setJsonContent([
                "status" => "error",
                "message" => "Reservation not found."
            ]);
        }

        // Update the status to confirmed
        $reservation->status = 'confirmed';

        if ($reservation->save()) {
            return $this->response->setJsonContent([
                "status" => "success",
                "message" => "Reservation confirmed.",
                "reservation" => $reservation
            ]);
        }

        return $this->response->setJsonContent([
            "status" => "error",
            "message" => "Failed to confirm reservation.",
            "errors" => $reservation->getMessages()
        ]);
    }

    // Cancel table reservation
    public function cancelAction($id)
    {
        // Get the role from the token
        $role = $this->getRoleFromToken();
        if ($role === null || !in_array($role, ['General Admin', 'Tables Admin', 'Customer'])) {
            return $this->response->setStatusCode(403, 'Forbidden')->setJsonContent([
                "status" => "error",
                "message" => "Access denied. You do not have permission to cancel reservations. Your role is: " . ($role ?? 'undefined')
            ]);
        }

        // Fetch the reservation by ID
        $reservation = TableReservations::findFirst($id);
        if (!$reservation) {
            return $this->response->setJsonContent([
                "status" => "error",
                "message" => "Reservation not found."
            ]);
        }

        // Update the status to canceled
        $reservation->status = 'canceled';

        // Fetch the associated table
        $table = Table::findFirst($reservation->table_id);
        if ($table) {
            // Update the status of the table to available
            $table->status = 1;

            // Attempt to save the table status
            if (!$table->save()) {
                return $this->response->setJsonContent([
                    "status" => "error",
                    "message" => "Failed to update table status.",
                    "errors" => $table->getMessages()
                ]);
            }
        }

        // Attempt to save the reservation
        if ($reservation->save()) {
            return $this->response->setJsonContent([
                "status" => "success",
                "message" => "Reservation cancelled.",
                "reservation" => $reservation
            ]);
        }

        return $this->response->setJsonContent([
            "status" => "error",
            "message" => "Failed to cancel reservation.",
            "errors" => $reservation->getMessages()
        ]);
    }
}
