<?php

use Phalcon\Mvc\Controller;

class TableReservationController extends Controller
{
    public function reserveAction()
    {
        // Get data from the request 
        $data = $this->request->getJsonRawBody(true);

        if (!$data) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'No data received.'
            ]);
        }

        // Extract values from the request body
        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $phoneNumber = $data['phone_number'] ?? null;
        $reservationDate = $data['reservation_date'] ?? null;
        $startTime = $data['start_time'] ?? null;
        $endTime = $data['end_time'] ?? null;
        $tableId = $data['table_id'] ?? null;
        $services = $data['services'] ?? [];

        // Check if table_id is provided
        if (!$tableId) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Table ID is missing.'
            ]);
        }

        // Check if the table exists
        $table = Table::findFirstById($tableId);

        if (!$table) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Table not available.'
            ]);
        }

        // Check if the table is available 
        if ($table->status == 0) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Table not available. It has been booked.'
            ]);
        }

        // Find or create the customer
        $customer = Customers::findFirst([
            'conditions' => 'email = ?0',
            'bind' => [$email]
        ]);

        if (!$customer) {
            $customer = new Customers();
            $customer->name = $name;
            $customer->email = $email;
            $customer->phone_number = $phoneNumber;
            if (!$customer->save()) {
                $messages = $customer->getMessages();
                $errorMessages = [];
                foreach ($messages as $message) {
                    $errorMessages[] = $message->getMessage();
                }
                return $this->response->setJsonContent([
                    'status' => 'error',
                    'message' => 'Failed to save customer details.',
                    'errors' => $errorMessages
                ]);
            }
        }

        // Calculate the number of hours for the reservation
        $startTimeObj = new DateTime($startTime);
        $endTimeObj = new DateTime($endTime);
        $interval = $startTimeObj->diff($endTimeObj);
        $numberOfHours = $interval->h + ($interval->i / 60); 

        // Get the price per hour
        $pricePerHour = $table->deposit_per_hour;

        // Calculate the table reservation amount
        $tableAmount = $numberOfHours * $pricePerHour;

        // Calculate services amount
        $servicesAmount = 0;
        foreach ($services as $service) {
            $serviceModel = Service::findFirstById($service['service_id']);
            if ($serviceModel) {
                // Calculate the cost of the service
                $serviceCost = $service['number_of_times'] * $serviceModel->price;
                $servicesAmount += $serviceCost;
            }
        }

        // Calculate the total amount 
        $totalAmount = $tableAmount + $servicesAmount;

        // Save the table reservation
        $tableReservation = new TableReservations();
        $tableReservation->customer_id = $customer->id; 
        $tableReservation->table_id = $tableId;
        $tableReservation->reservation_date = $reservationDate;
        $tableReservation->start_time = $startTime;
        $tableReservation->end_time = $endTime;
        $tableReservation->price_per_hour = $pricePerHour; 
        $tableReservation->number_of_hours = $numberOfHours; 
        $tableReservation->amount = $tableAmount; 
        $tableReservation->total_amount = $totalAmount; 
        $tableReservation->status = 1; 
        $tableReservation->created_at = date('Y-m-d H:i:s');
        $tableReservation->updated_at = date('Y-m-d H:i:s');

        // Save the table reservation and handle any potential errors
        if (!$tableReservation->save()) {
            $messages = $tableReservation->getMessages();
            $errorMessages = [];
            foreach ($messages as $message) {
                $errorMessages[] = $message->getMessage();
            }
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Failed to save table reservation.',
                'errors' => $errorMessages
            ]);
        }

        // Now save the services after the reservation 
        foreach ($services as $service) {
            $serviceModel = Service::findFirstById($service['service_id']);
            if ($serviceModel) {
                // Calculate the cost of the service
                $serviceCost = $service['number_of_times'] * $serviceModel->price;

                // Save the service details to table reservation services table
                $tableReservationService = new TableReservationServices();
                $tableReservationService->reservation_id = $tableReservation->id; 
                $tableReservationService->service_id = $service['service_id'];
                $tableReservationService->number_of_times = $service['number_of_times'];
                $tableReservationService->amount = $serviceCost; 
                $tableReservationService->created_at = date('Y-m-d H:i:s');
                $tableReservationService->updated_at = date('Y-m-d H:i:s');
                $tableReservationService->price = $serviceModel->price; 

                if (!$tableReservationService->save()) {
                    $messages = $tableReservationService->getMessages();
                    $errorMessages = [];
                    foreach ($messages as $message) {
                        $errorMessages[] = $message->getMessage();
                    }
                    return $this->response->setJsonContent([
                        'status' => 'error',
                        'message' => 'Failed to save table reservation services.',
                        'errors' => $errorMessages
                    ]);
                }
            }
        }

        // Update the table status to reserved
        $table->status = 0; 
        if (!$table->save()) {
            return $this->response->setJsonContent([
                'status' => 'error',
                'message' => 'Failed to update table status.'
            ]);
        }

        // Return success response with the total amount
        return $this->response->setJsonContent([
            'status' => 'success',
            'message' => 'Table reserved successfully.',
            'reservation_id' => $tableReservation->id,
            'total_amount' => $totalAmount 
        ]);
    }
}
