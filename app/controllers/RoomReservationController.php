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

        // Start a database transaction
        $this->db->begin();

        try {
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
                    throw new \Exception('Failed to save customer: ' . implode(', ', $customer->getMessages()));
                }
            }

            // Fetch the room details
            $room = Room::findFirst($roomId);
            if (!$room || $room->status == 0) {
                throw new \Exception('Room not available.');
            }

            // Calculate price per day and number of days
            $pricePerDay = $room->price_per_night;
            $numberOfDays = (strtotime($checkOutDate) - strtotime($checkInDate)) / (60 * 60 * 24);

            // Set room amount
            $roomAmount = $numberOfDays * $pricePerDay;

            // Initialize total amount with room amount
            $totalAmount = $roomAmount;

            // Create a new room reservation with a placeholder total amount
            $reservation = new RoomReservation();
            $reservation->customer_id = $customer->id;
            $reservation->room_id = $roomId;
            $reservation->check_in = $checkInDate;
            $reservation->check_out = $checkOutDate;
            $reservation->price_per_day = $pricePerDay;
            $reservation->number_of_days = $numberOfDays;
            $reservation->amount = $roomAmount;
            $reservation->total_amount = 0; // Placeholder for total amount
            $reservation->created_at = date('Y-m-d H:i:s'); // Set the current timestamp

            // Attempt to save the reservation
            if (!$reservation->save()) {
                throw new \Exception('Failed to save room reservation: ' . implode(', ', $reservation->getMessages()));
            }

            // If there are services to add, process them
            if (!empty($services)) {
                foreach ($services as $serviceData) {
                    $serviceId = $serviceData->service_id ?? null;
                    $numberOfTimes = $serviceData->number_of_times ?? 1;
                    $service = Service::findFirst($serviceId);

                    if ($service) {
                        // Calculate service amount and update total
                        $serviceAmount = $service->price * $numberOfTimes;
                        $totalAmount += $serviceAmount;

                        // Create a new RoomReservationServices record
                        $reservationService = new RoomReservationServices();
                        $reservationService->service_id = $serviceId;
                        $reservationService->number_of_times = $numberOfTimes;
                        $reservationService->price = $service->price;
                        $reservationService->amount = $serviceAmount;
                        $reservationService->reservation_id = $reservation->id; // Link to the reservation

                        // Save the service details
                        if (!$reservationService->save()) {
                            throw new \Exception('Failed to save service: ' . implode(', ', $reservationService->getMessages()));
                        }
                    }
                }
            }

            // Update the reservation's total amount after all services are saved
            $reservation->total_amount = $totalAmount;

            // Attempt to save the updated total amount
            if (!$reservation->save()) {
                throw new \Exception('Failed to update total amount for the reservation: ' . implode(', ', $reservation->getMessages()));
            }

            // Update the room status to 0 (booked)
            $room->status = 0;
            if (!$room->save()) {
                throw new \Exception('Failed to update room status.');
            }

            // Commit the transaction
            $this->db->commit();

            return $this->responseJson(['status' => 'success', 'message' => 'Room reserved successfully.', 'reservation_id' => $reservation->id]);

        } catch (\Exception $e) {
            // Rollback the transaction in case of failure
            $this->db->rollback();
            return $this->responseJson(['status' => 'error', 'message' => 'Operation failed: ' . $e->getMessage()]);
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
