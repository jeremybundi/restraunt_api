<?php
use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

class ServiceController extends Controller
{
    // Add a new service or multiple services
    public function addAction()
{
    $response = new Response();
    $data = $this->request->getJsonRawBody();
    
    // Ensure that the input has a "services" key and it's an array
    if (!isset($data->services) || !is_array($data->services)) {
        $response->setStatusCode(400, 'Bad Request');
        $response->setJsonContent(['status' => 'error', 'message' => 'The request should contain a "services" array']);
        return $response;
    }

    $services = $data->services;
    $errors = [];
    $successCount = 0;

    foreach ($services as $serviceData) {
        // Validate each service
        if (empty($serviceData->service_name) || !isset($serviceData->price) || empty($serviceData->service_type)) {
            $errors[] = "Service name, price, and type are required for each service";
            continue;
        }

        // Create a new Service
        $service = new Service();
        $service->service_name = $serviceData->service_name;
        $service->price = $serviceData->price;
        $service->service_type = $serviceData->service_type;

        if ($service->save()) {
            $successCount++;
        } else {
            $errors[] = "Failed to add service: " . $serviceData->service_name;
        }
    }

    if (!empty($errors)) {
        $response->setStatusCode(400, 'Bad Request');
        $response->setJsonContent([
            'status' => 'error', 
            'message' => $errors,
            'services_added' => $successCount
        ]);
    } else {
        $response->setStatusCode(201, 'Created');
        $response->setJsonContent(['status' => 'success', 'message' => "$successCount services added successfully"]);
    }

    return $response;
}

    

    // Edit an existing service
    public function editAction($id)
    {
        $response = new Response();
        $data = $this->request->getJsonRawBody();

        // Find the service by ID
        $service = Service::findFirstById($id);
        if (!$service) {
            $response->setStatusCode(404, 'Not Found');
            $response->setJsonContent(['status' => 'error', 'message' => 'Service not found']);
            return $response;
        }

        // Update the service details
        $service->service_name = $data->service_name ?? $service->service_name;
        $service->price = $data->price ?? $service->price;
        $service->service_type = $data->service_type ?? $service->service_type;

        if ($service->save()) {
            $response->setJsonContent(['status' => 'success', 'message' => 'Service updated successfully']);
        } else {
            $response->setStatusCode(500, 'Internal Server Error');
            $response->setJsonContent(['status' => 'error', 'message' => 'Failed to update service']);
        }

        return $response;
    }

    // Delete a service
    public function deleteAction($id)
    {
        $response = new Response();

        // Find the service by ID
        $service = Service::findFirstById($id);
        if (!$service) {
            $response->setStatusCode(404, 'Not Found');
            $response->setJsonContent(['status' => 'error', 'message' => 'Service not found']);
            return $response;
        }

        if ($service->delete()) {
            $response->setJsonContent(['status' => 'success', 'message' => 'Service deleted successfully']);
        } else {
            $response->setStatusCode(500, 'Internal Server Error');
            $response->setJsonContent(['status' => 'error', 'message' => 'Failed to delete service']);
        }

        return $response;
    }

        // Get all services
    public function getAllAction()
    {
        $response = new Response();
        
        // Fetch all services
        $services = Service::find();
        
        if (!$services) {
            $response->setStatusCode(404, 'Not Found');
            $response->setJsonContent(['status' => 'error', 'message' => 'No services found']);
            return $response;
        }

        $response->setJsonContent(['status' => 'success', 'data' => $services]);
        return $response;
    }

}
