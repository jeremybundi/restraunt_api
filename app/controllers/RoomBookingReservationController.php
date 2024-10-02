<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Phalcon\Mvc\Model\Query\Builder;

class RoomBookingReservationController extends Controller
{
    // Action to list all reservations
    public function listAllReservationsAction()
    {
        $builder = new Builder();
        $builder->columns([
            'RoomReservation.id AS reservation_id',
            'RoomReservation.customer_id',
            'RoomReservation.check_in',
            'RoomReservation.check_out',
            'RoomReservation.price_per_day',
            'RoomReservation.number_of_days',
            'RoomReservation.amount',
            'RoomReservation.total_amount',
            'Room.image_url',
            'Room.room_number',
            'Room.capacity',
            'Customers.name AS customer_name',
            'Customers.email AS customer_email'
        ])
        ->from('RoomReservation')
        ->join('Room', 'RoomReservation.room_id = Room.id')
        ->join('RoomReservationServices', 'RoomReservation.id = RoomReservationServices.reservation_id')
        ->join('Service', 'RoomReservationServices.service_id = Service.id')
        ->join('Customers', 'RoomReservation.customer_id = Customers.id') // Join with Customers table
        ->groupBy('RoomReservation.id');

        $reservations = $builder->getQuery()->execute();

        // Format the response
        $responseData = [];
        foreach ($reservations as $reservation) {
            // Initialize the reservation data
            $reservationData = [
                'reservation_id' => $reservation->reservation_id,
                'customer_id' => $reservation->customer_id,
                'customer_name' => $reservation->customer_name, // Include customer name
                'customer_email' => $reservation->customer_email, // Include customer email
                'check_in' => $reservation->check_in,
                'check_out' => $reservation->check_out,
                'price_per_day' => $reservation->price_per_day,
                'number_of_days' => $reservation->number_of_days,
                'amount' => $reservation->amount,
                'total_amount' => $reservation->total_amount,
                'room' => [
                    'image_url' => $reservation->image_url,
                    'room_number' => $reservation->room_number,
                    'capacity' => $reservation->capacity,
                ],
                'services' => [],
            ];

            // Fetch services for this reservation
            $services = RoomReservationServices::find([
                'conditions' => 'reservation_id = ?0',
                'bind' => [$reservation->reservation_id],
            ]);

            foreach ($services as $service) {
                $serviceData = Service::findFirst($service->service_id);
                if ($serviceData) {
                    $reservationData['services'][] = [
                        'service_name' => $serviceData->service_name,
                        'price' => $service->price,
                        'number_of_times' => $service->number_of_times,
                        'amount' => $service->amount,
                    ];
                }
            }

            $responseData[] = $reservationData;
        }

        // Return the response as JSON
        return $this->responseJson(['status' => 'success', 'data' => $responseData]);
    }

    // Action to get reservations for a specific customer (remains unchanged)
    public function getCustomerReservationsAction($customerId)
    {
        // Validate the customer ID
        if (!is_numeric($customerId)) {
            return $this->responseJson(['status' => 'error', 'message' => 'Invalid customer ID']);
        }

        // Fetch reservations for the given customer ID
        $reservations = RoomReservation::find([
            'conditions' => 'customer_id = ?0',
            'bind' => [$customerId],
        ]);

        // If no reservations found, return an appropriate message
        if (count($reservations) === 0) {
            return $this->responseJson(['status' => 'success', 'message' => 'No reservations found for this customer']);
        }

        // Format the response
        $responseData = [];
        foreach ($reservations as $reservation) {
            // Initialize the reservation data
            $reservationData = [
                'reservation_id' => $reservation->id,
                'customer_id' => $reservation->customer_id,
                'check_in' => $reservation->check_in,
                'check_out' => $reservation->check_out,
                'price_per_day' => $reservation->price_per_day,
                'number_of_days' => $reservation->number_of_days,
                'amount' => $reservation->amount,
                'total_amount' => $reservation->total_amount,
                'room' => null,
                'services' => [],
            ];

            // Fetch room details
            $room = Room::findFirst($reservation->room_id);
            if ($room) {
                $reservationData['room'] = [
                    'image_url' => $room->image_url,
                    'room_number' => $room->room_number,
                    'capacity' => $room->capacity,
                ];
            }

            // Fetch services for this reservation
            $services = RoomReservationServices::find([
                'conditions' => 'reservation_id = ?0',
                'bind' => [$reservation->id],
            ]);

            foreach ($services as $service) {
                $serviceData = Service::findFirst($service->service_id);
                if ($serviceData) {
                    $reservationData['services'][] = [
                        'service_name' => $serviceData->service_name,
                        'price' => $service->price,
                        'number_of_times' => $service->number_of_times,
                        'amount' => $service->amount,
                    ];
                }
            }

            $responseData[] = $reservationData;
        }

        // Return the response as JSON
        return $this->responseJson(['status' => 'success', 'data' => $responseData]);
    }

    // Helper function to return JSON response
    private function responseJson($data)
    {
        $response = new Response();
        $response->setJsonContent($data);
        return $response;
    }
}
