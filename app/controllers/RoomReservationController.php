<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

class RoomReservationController extends Controller
{
    public function reserveAction()
    {
        // Get the JSON body from the request
        $body = $this->request->getJsonRawBody();

        // Extract data from the JSON body
        $customerName = $body->name ?? null;
        $customerEmail = $body->email ?? null;
        $customerPhone = $body->phone_number ?? null;
        $checkInDate = $body->check_in ?? null;
        $checkOutDate = $body->check_out ?? null;
        $roomId = $body->room_id ?? null; 
        $services = $body->services ?? []; 

        // Validate the input
        if (is_null($customerName) || is_null($customerEmail) || is_null($customerPhone) ||
            is_null($checkInDate) || is_null($checkOutDate) || is_null($roomId)) {
            return $this->responseJson(['status' => 'error', 'message' => 'Missing required fields.']);
        }

        // Check if the customer already exists
        $customer = Customers::findFirst([
            'conditions' => 'email = ?0',
            'bind'       => [$customerEmail]
        ]);

        // If customer doesn't exist, create a new customer record
        if (!$customer) {
            $customer = new Customers();
            $customer->name = $customerName;
            $customer->email = $customerEmail;
            $customer->phone_number = $customerPhone;

            if (!$customer->save()) {
                return $this->responseJson(['status' => 'error', 'message' => 'Failed to save customer.']);
            }
        }

        // Fetch the room details
        $room = Room::findFirst($roomId);
        if (!$room || $room->status == 0) {
            return $this->responseJson(['status' => 'error', 'message' => 'Room not available.']);
        }

        // Create a new room reservation
        $reservation = new RoomReservation();
        $reservation->customer_id = $customer->id; 
        $reservation->room_id = $roomId;
        $reservation->check_in = $checkInDate;
        $reservation->check_out = $checkOutDate;

        // Calculate price per day and number of days
        $pricePerDay = $room->price_per_night; 
        $numberOfDays = (strtotime($checkOutDate) - strtotime($checkInDate)) / (60 * 60 * 24);

        // Set reservation details
        $roomAmount = $numberOfDays * $pricePerDay; 
        $reservation->price_per_day = $pricePerDay;
        $reservation->number_of_days = $numberOfDays;
        $reservation->amount = $roomAmount; 

        // Initialize total amount with room amount
        $totalAmount = $roomAmount; 

        // If there are services to add, process them
        if (!empty($services)) {
            foreach ($services as $serviceData) {
                $serviceId = $serviceData->service_id ?? null;
                $numberOfTimes = $serviceData->number_of_times ?? 1; 
                $service = Service::findFirst($serviceId);

                if ($service) {
                    // Create a new RoomReservationServices record
                    $reservationService = new RoomReservationServices();
                    $reservationService->reservation_id = $reservation->id; 
                    $reservationService->service_id = $serviceId;
                    $reservationService->price = $service->price; 
                    $reservationService->number_of_times = $numberOfTimes;
                    $reservationService->amount = $service->price * $numberOfTimes; 

                    // Update the total amount for the reservation
                    $totalAmount += $reservationService->amount; 

                    if (!$reservationService->save()) {
                        return $this->responseJson(['status' => 'error', 'message' => 'Failed to save service to reservation.']);
                    }
                }
            }
        }

        // Set the total amount in the reservation
        $reservation->total_amount = $totalAmount; 

        // Attempt to save the reservation
        if ($reservation->save()) {
            // Update the room status to 0 (booked)
            $room->status = 0;
            $room->save(); 

            return $this->responseJson(['status' => 'success', 'message' => 'Room reserved successfully.', 'reservation_id' => $reservation->id]);
        } else {
            // Capture specific error messages if saving fails
            $messages = $reservation->getMessages();
            $errorMessages = [];
            foreach ($messages as $message) {
                $errorMessages[] = $message->getMessage();
            }
            return $this->responseJson(['status' => 'error', 'message' => 'Failed to save room reservation.', 'errors' => $errorMessages]);
        }
    }

    // Helper function to return JSON response
    private function responseJson($data)
    {
        $response = new Response();
        $response->setJsonContent($data);
        return $response;
    }
}
