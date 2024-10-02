<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class RoomReservationStatusController extends Controller
{
        // Function to extract role from JWT
        private function getRoleFromToken()
        {
            // Get the token from the Authorization header
            $authHeader = $this->request->getHeader('Authorization');
            if (!$authHeader) {
                return null;
            }
        
            // Extract the JWT from the Bearer token
            $token = str_replace('Bearer ', '', $authHeader);
        
            try {
                // Get the secret key from the config file
                $secretKey = $this->di->get('config')->jwt->secret_key;
        
                // Decode the JWT using Firebase JWT
                $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
        
                // Return the role from the token's payload
                return $decoded->role ?? null;
            } catch (\Exception $e) {
                return null; // If decoding fails
            }
        }
        

    // Confirm room reservation
    public function confirmAction($id)
    {
        // Get the role from the token
        $role = $this->getRoleFromToken();
        if (!$role || !in_array($role, ['General Admin', 'Room Admin'])) {
            return $this->response->setStatusCode(403, 'Forbidden')->setJsonContent([
                "status" => "error",
                "message" => "Access denied. You do not have permission to confirm reservations."
            ]);
        }

        // Fetch the room reservation by ID
        $reservation = RoomReservation::findFirst($id);
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

    // Cancel room reservation
    public function cancelAction($id)
    {
        // Get the role from the token
        $role = $this->getRoleFromToken();
        if (!$role || !in_array($role, ['General Admin', 'Room Admin' , 'Customer'])) {
            return $this->response->setStatusCode(403, 'Forbidden')->setJsonContent([
                "status" => "error",
                "message" => "Access denied. You do not have permission to cancel reservations."
            ]);
        }

        // Fetch the room reservation by ID
        $reservation = RoomReservation::findFirst($id);
        if (!$reservation) {
            return $this->response->setJsonContent([
                "status" => "error",
                "message" => "Reservation not found."
            ]);
        }

        // Update the status to canceled
        $reservation->status = 'canceled';

        // Change the status of the room back to available
        $room = Room::findFirst($reservation->room_id);
        if ($room) {
            $room->status = 1; // Available
            $room->save(); // Save room status
        }

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
