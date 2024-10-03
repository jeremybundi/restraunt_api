<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class TableBookingReservationController extends Controller
{
    private $secretKey;

    public function initialize()
    {
        // Load the secret key from config
        $this->secretKey = $this->config->jwt->secret_key;
    }

    private function decodeToken($token)
    {
        try {
            return JWT::decode($token, new Key($this->secretKey, 'HS256'));
        } catch (ExpiredException $e) {
            return null; // Token expired
        } catch (Exception $e) {
            return null; // Other token decoding errors
        }
    }

    public function getAllReservationsAction()
    {
        // Get token from Bearer Authorization header
        $authorizationHeader = $this->request->getHeader('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);

        // Decode the token to get payload
        $payload = $this->decodeToken($token);

        // Check for valid payload and role
        if (!$payload || !isset($payload->role)) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Unauthorized access.'
            ]);
        }

        // Allow access only for General Admin or Tables Admin
        if ($payload->role !== 'General Admin' && $payload->role !== 'Tables Admin') {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Access denied.'
            ]);
        }

        // Fetch table reservations
        $reservations = $this->modelsManager->createBuilder()
            ->columns([
                'tr.id AS reservation_id',
                'tr.reservation_date',
                'tr.start_time',
                'tr.end_time',
                'tr.price_per_hour AS table_price_per_hour',
                'tr.table_id',
                'c.name AS customer_name',
                'c.email AS customer_email',
                'c.id AS customer_id',
                't.table_number',
                't.capacity AS table_capacity',
                'tr.number_of_hours',
                's.service_name AS service_name',
                's.price AS service_price',
                'trs.number_of_times AS number_of_times',
                'COALESCE((s.price * trs.number_of_times), 0) AS service_subtotal'
            ])
            ->from(['tr' => 'TableReservations'])
            ->leftJoin('Customers', 'c.id = tr.customer_id', 'c')
            ->leftJoin('Table', 't.id = tr.table_id', 't')
            ->leftJoin('TableReservationServices', 'trs.reservation_id = tr.id', 'trs')
            ->leftJoin('Service', 's.id = trs.service_id', 's')
            ->orderBy('tr.id')
            ->getQuery()
            ->execute();

        $data = [];
        foreach ($reservations as $reservation) {
            $reservationId = $reservation->reservation_id;
            if (!isset($data[$reservationId])) {
                $data[$reservationId] = [
                    'reservation_id' => $reservationId,
                    'customer_id' => $reservation->customer_id,
                    'customer_name' => $reservation->customer_name,
                    'customer_email' => $reservation->customer_email,
                    'reservation_date' => $reservation->reservation_date,
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->end_time,
                    'table_number' => $reservation->table_number,
                    'table_capacity' => $reservation->table_capacity,
                    'table_price_per_hour' => $reservation->table_price_per_hour,
                    'number_of_hours' => $reservation->number_of_hours,
                    'service_details' => [],
                    'table_total' => $reservation->table_price_per_hour * $reservation->number_of_hours,
                    'services_total' => 0,
                    'total_amount' => 0
                ];
            }

            if ($reservation->service_name) {
                $serviceDetail = [
                    'service_name' => $reservation->service_name,
                    'service_price' => $reservation->service_price,
                    'number_of_times' => $reservation->number_of_times,
                    'service_subtotal' => $reservation->service_subtotal
                ];
                $data[$reservationId]['service_details'][] = $serviceDetail;
                $data[$reservationId]['services_total'] += $reservation->service_subtotal;
            }

            $data[$reservationId]['total_amount'] = $data[$reservationId]['table_total'] + $data[$reservationId]['services_total'];
        }

        return $this->response->setJsonContent([
            'status' => 'success',
            'data' => array_values($data)
        ]);
    }

    public function getReservationsByCustomerIdAction()
    {
        // Get token from Bearer Authorization header
        $authorizationHeader = $this->request->getHeader('Authorization');
        $token = str_replace('Bearer ', '', $authorizationHeader);

        // Decode the token to get payload
        $payload = $this->decodeToken($token);

        // Check for valid payload and fetch customer ID
        if (!$payload || !isset($payload->customerId)) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Unauthorized access.'
            ]);
        }

        $customerId = $payload->customerId; 

        // Fetch customer details
        $customer = Customers::findFirst($customerId);
        if (!$customer) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Customer not found.'
            ]);
        }

        // Fetch reservations for the customer
        $tableReservations = TableReservations::find([
            'conditions' => 'customer_id = :customer_id:',
            'bind' => ['customer_id' => $customerId],
        ]);

        $reservationDetails = [];
        foreach ($tableReservations as $reservation) {
            $table = Table::findFirst($reservation->table_id);
            if ($table) {
                $reservationServices = TableReservationServices::find([
                    'conditions' => 'reservation_id = :reservation_id:',
                    'bind' => ['reservation_id' => $reservation->id],
                ]);

                $serviceDetails = [];
                $servicesTotal = 0;
                foreach ($reservationServices as $service) {
                    $servicesTotal += $service->amount;
                    $serviceDetails[] = [
                        'service_id' => $service->service_id,
                        'number_of_times' => $service->number_of_times,
                        'amount' => $service->amount,
                    ];
                }

                $totalAmount = $reservation->total_amount + $servicesTotal;

                $reservationDetails[] = [
                    'reservation_id' => $reservation->id,
                    'reservation_date' => $reservation->reservation_date,
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->end_time,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_id' => $customer->id,
                    'table_number' => $table->table_number,
                    'table_capacity' => $table->capacity,
                    'number_of_hours' => $reservation->number_of_hours,
                    'price_per_hour' => $reservation->price_per_hour,
                    'service_details' => $serviceDetails,
                    'table_total' => $reservation->amount,
                    'services_total' => $servicesTotal,
                    'total_amount' => $totalAmount,
                ];
            }
        }

        return $this->response->setJsonContent([
            'status' => 'success',
            'data' => $reservationDetails
        ]);
    }
}
