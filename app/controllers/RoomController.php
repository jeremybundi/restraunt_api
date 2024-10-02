<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class RoomController extends Controller
{
    private function getTokenFromHeader()
    {
        $authorizationHeader = $this->request->getHeader('Authorization');
        if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function decodeToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->getJwtSecretKey(), 'HS256'));
            return $decoded;
        } catch (\Exception $e) {
            return null; // Token is invalid or expired
        }
    }

    private function checkRole($requiredRoles)
    {
        $token = $this->getTokenFromHeader();
        if (!$token) {
            return false; // No token found
        }

        $decoded = $this->decodeToken($token);
        if (!$decoded || !isset($decoded->role)) {
            return false; // Token invalid or role not set
        }

        return in_array($decoded->role, $requiredRoles);
    }

    public function addAction()
    {
        // Check user role
        if (!$this->checkRole(['General Admin', 'Room Admin'])) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ]);
        }

        $request = $this->request->getJsonRawBody();
        
        // Check if rooms are provided in an array
        if (isset($request->rooms) && is_array($request->rooms)) {
            foreach ($request->rooms as $roomData) {
                // Validation
                $validation = new Validation();
                $validation->add('room_number', new PresenceOf([
                    'message' => 'room_number is required'
                ]));
                $validation->add('room_number', new StringLength([
                    'max' => 50,
                    'messageMaximum' => 'room_number is too long'
                ]));
                $validation->add('room_type', new PresenceOf([
                    'message' => 'room_type is required'
                ]));
                $validation->add('capacity', new PresenceOf([
                    'message' => 'capacity is required'
                ]));
                $validation->add('price_per_night', new PresenceOf([
                    'message' => 'price_per_night is required'
                ]));
    
                $messages = $validation->validate((array)$roomData);
                if (count($messages) > 0) {
                    return $this->response->setJsonContent([
                        'status' => 'error',
                        'messages' => $messages
                    ]);
                }
    
                // Create a new room
                $room = new Room();
                $room->room_number = $roomData->room_number;
                $room->room_type = $roomData->room_type;
                $room->capacity = $roomData->capacity;
                $room->price_per_night = $roomData->price_per_night;
                $room->image_url = $roomData->image_url; // New column
                $room->status = 'available'; // Default status
                $room->created_at = date('Y-m-d H:i:s');
                $room->updated_at = date('Y-m-d H:i:s');
    
                if ($room->save() === false) {
                    return $this->response->setJsonContent([
                        'status' => 'error',
                        'messages' => $room->getMessages()
                    ]);
                }
            }
    
            // Successful addition of all rooms
            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Rooms added successfully'
            ]);
        } else {
            // If no rooms are provided, respond with an error
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Invalid request format: rooms array is required'
            ]);
        }
    }

    public function editAction($id)
    {
        // Check user role
        if (!$this->checkRole(['General Admin', 'Room Admin'])) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ]);
        }

        // Find the room by ID
        $room = Room::findFirst($id);

        // Check if the room exists
        if (!$room) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Room not found'
            ]);
        }

        // Get the JSON data from the request body
        $request = $this->request->getJsonRawBody();

        // Update room properties
        $room->room_number = $request->room_number;
        $room->room_type = $request->room_type;
        $room->capacity = $request->capacity;
        $room->price_per_night = $request->price_per_night; // Fixed the property name
        $room->image_url = $request->image_url; // New column

        // Save the updated room
        if ($room->save() === false) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'messages' => $room->getMessages()
            ]);
        }

        // Successful update
        return $this->response->setJsonContent([
            'status' => 'success',
            'message' => 'Room updated successfully'
        ]);
    }

    public function deleteAction($id)
    {
        // Check user role
        if (!$this->checkRole(['General Admin', 'Room Admin'])) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ]);
        }

        // Find the room by ID
        $room = Room::findFirst($id);
        if (!$room) {
            // Create a response for room not found
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Room not found'
            ]);
        }

        // Attempt to delete the room
        if ($room->delete() === false) {
            // Create a response for delete failure
            return $this->response->setJsonContent([
                'status' => 'error',
                'messages' => $room->getMessages()
            ]);
        }

        // Successful deletion response
        return $this->response->setJsonContent([
            'status' => 'success',
            'message' => 'Room deleted successfully'
        ]);
    }

    public function getAllAction()
    {
        // This action can be accessed by any user; no role check needed
        $rooms = Room::find();
        $response = new Response();
        $response->setJsonContent(['status' => 'success', 'data' => $rooms]);
        return $response;
    }

    private function getJwtSecretKey()
    {
        // Return the JWT secret key from the config
        return $this->config->jwt['secret_key'];
    }
}
