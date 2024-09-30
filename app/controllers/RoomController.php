<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;

class RoomController extends Controller
{
    public function addAction()
    {
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
                $validation->add('price', new PresenceOf([
                    'message' => 'price is required'
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
                $room->price = $roomData->price;
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
            // Handle single room addition if no array is provided
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
            $validation->add('price', new PresenceOf([
                'message' => 'price is required'
            ]));

            $messages = $validation->validate((array)$request);
            if (count($messages) > 0) {
                return $this->response->setJsonContent([
                    'status' => 'error',
                    'messages' => $messages
                ]);
            }

            // Create a new room for single room request
            $room = new Room();
            $room->room_number = $request->room_number;
            $room->room_type = $request->room_type;
            $room->capacity = $request->capacity;
            $room->price = $request->price;
            $room->image_url = $request->image_url; // New column
            $room->status = 'available'; // Default status
            $room->created_at = date('Y-m-d H:i:s');
            $room->updated_at = date('Y-m-d H:i:s');

            if ($room->save() === false) {
                return $this->response->setJsonContent([
                    'status' => 'error',
                    'messages' => $room->getMessages()
                ]);
            }

            // Successful addition of a single room
            return $this->response->setJsonContent([
                'status' => 'success',
                'message' => 'Room added successfully'
            ]);
        }

        // Default response for unsupported request format
        return $this->response->setJsonContent([
            'status' => 'error',
            'message' => 'Invalid request format'
        ]);
    }
    public function editAction($id)
    {
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
        $room->price = $request->price;
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
        // Fetch all rooms
        $rooms = Room::find();
        $response = new Response();
        $response->setJsonContent(['status' => 'success', 'data' => $rooms]);
        return $response;
    }
}
