<?php

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\Key; // Make sure to include the Key class
use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

class TableController extends Controller
{
      // Add a new table or multiple tables
      public function addAction()
      {
          $response = new Response();
          
          // Check user role
          $role = $this->checkUserRole();
          if (!$role || !in_array($role, ['General Admin', 'Tables Admin'])) {
              $response->setStatusCode(403, 'Forbidden');
              $response->setJsonContent(['status' => 'error', 'message' => 'Access denied']);
              return $response;
          }
  
          $data = $this->request->getJsonRawBody();
          
          // Ensure that the input has a "tables" key
          if (!isset($data->tables) || !is_array($data->tables)) {
              $response->setStatusCode(400, 'Bad Request');
              $response->setJsonContent(['status' => 'error', 'message' => 'The request should contain a "tables" array']);
              return $response;
          }
  
          $tables = $data->tables;
          $errors = [];
  
          foreach ($tables as $tableData) {
              if (empty($tableData->table_number) || empty($tableData->capacity) || !isset($tableData->status)) {
                  $errors[] = "Table number, capacity, and status are required for each table";
                  continue;
              }
  
              // Create a new Table
              $table = new Table();
              $table->table_number = $tableData->table_number;
              $table->capacity = $tableData->capacity;
              $table->status = $tableData->status;
              $table->image_url = $tableData->image_url ?? null; // Optional
              $table->deposit_per_hour = $tableData->deposit_per_hour ?? null; // Optional
  
              if (!$table->save()) {
                  $errors[] = "Failed to add table: " . $tableData->table_number;
              }
          }
  
          if (!empty($errors)) {
              $response->setStatusCode(400, 'Bad Request');
              $response->setJsonContent(['status' => 'error', 'message' => $errors]);
          } else {
              $response->setStatusCode(201, 'Created');
              $response->setJsonContent(['status' => 'success', 'message' => 'Tables added successfully']);
          }
  
          return $response;
      }
  

    // Get all tables
    public function getAllAction()
    {
        $response = new Response();
        
        // Fetch all tables from the database
        $tables = Table::find();

        // Check if tables were found
        if ($tables->count() > 0) {
            $response->setJsonContent([
                'status' => 'success',
                'data' => $tables->toArray() // Convert the result to an array
            ]);
        } else {
            $response->setJsonContent([
                'status' => 'success',
                'message' => 'No tables found',
                'data' => []
            ]);
        }

        return $response;
    }

    // Edit a table
    public function editAction($id)
    {
        $response = new Response();
        $data = $this->request->getJsonRawBody();

        // Check user role
        $role = $this->checkUserRole();
        if (!$role || !in_array($role, ['General Admin', 'Tables Admin'])) {
            $response->setStatusCode(403, 'Forbidden');
            $response->setJsonContent(['status' => 'error', 'message' => 'Access denied']);
            return $response;
        }

        // Find the table by ID
        $table = Table::findFirstById($id);
        if (!$table) {
            $response->setStatusCode(404, 'Not Found');
            $response->setJsonContent(['status' => 'error', 'message' => 'Table not found']);
            return $response;
        }

        // Update table properties
        $table->table_number = $data->table_number ?? $table->table_number;
        $table->capacity = $data->capacity ?? $table->capacity;
        $table->status = $data->status ?? $table->status;
        $table->image_url = $data->image_url ?? $table->image_url; // Optional
        $table->deposit_per_hour = $data->deposit_per_hour ?? $table->deposit_per_hour; // Optional

        if (!$table->save()) {
            $response->setStatusCode(400, 'Bad Request');
            $response->setJsonContent(['status' => 'error', 'message' => 'Failed to update table']);
        } else {
            $response->setStatusCode(200, 'OK');
            $response->setJsonContent(['status' => 'success', 'message' => 'Table updated successfully']);
        }

        return $response;
    }

    // Delete a table
    public function deleteAction($id)
    {
        $response = new Response();
        
        // Check user role
        $role = $this->checkUserRole();
        if (!$role || !in_array($role, ['General Admin', 'Tables Admin'])) {
            $response->setStatusCode(403, 'Forbidden');
            $response->setJsonContent(['status' => 'error', 'message' => 'Access denied']);
            return $response;
        }

        // Find the table by ID
        $table = Table::findFirstById($id);
        if (!$table) {
            $response->setStatusCode(404, 'Not Found');
            $response->setJsonContent(['status' => 'error', 'message' => 'Table not found']);
            return $response;
        }

        // Attempt to delete the table
        if (!$table->delete()) {
            $response->setStatusCode(400, 'Bad Request');
            $response->setJsonContent(['status' => 'error', 'message' => 'Failed to delete table']);
        } else {
            $response->setStatusCode(200, 'OK');
            $response->setJsonContent(['status' => 'success', 'message' => 'Table deleted successfully']);
        }

        return $response;
    }

    private function checkUserRole()
    {
        $authHeader = $this->request->getHeader('Authorization');

        if (!$authHeader) {
            return null; // Handle missing header
        }

        // Extract the JWT token
        list($jwt) = sscanf($authHeader, 'Bearer %s');
        if (!$jwt) {
            return null; // Handle invalid token
        }

        // Retrieve your secret key from the configuration
        $secretKey = $this->config->jwt->secret_key; // Adjust according to your config structure

        try {
            // Decode the token using the secret key and the algorithm
            $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));

            // Assuming the decoded token has a 'role' property
            return $decoded->role; 
        } catch (ExpiredException $e) {
            return null; // Handle expired token
        } catch (Exception $e) {
            return null; // Handle other token decode errors
        }
    }
}
