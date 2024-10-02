<?php

$router = $di->getRouter();

// Define routes for AdminController
$router->addPost('/admin/signup', [
    'controller' => 'admin',
    'action'     => 'signup'
]);

$router->addPost('/admin/login', [
    'controller' => 'admin',
    'action'     => 'login'
]);

$router->addPost('/admin/verify-otp', [
    'controller' => 'admin',
    'action'     => 'verifyOtp'
]);
// Route for customer login (sends OTP)
$router->addPost('/customer/login', [
    'controller' => 'customer',
    'action'     => 'login'
]);

// Route for verifying OTP
$router->addPost('/customer/verify-otp', [
    'controller' => 'customer',
    'action'     => 'verifyOtp'  
]);
// Route to add a room
$router->add('/room/add', [
    'controller' => 'room',
    'action'     => 'add'
]);

// Route to edit a room
$router->add('/room/edit/{id}', [
    'controller' => 'room',
    'action'     => 'edit'
]);

// Route to delete a room
$router->add('/room/delete/{id}', [
    'controller' => 'room',
    'action'     => 'delete'
]);

//get all
$router->add('/rooms/all', [
    'controller' => 'room',
    'action'     => 'getAll'
]);
//services
$router->addPost('/services', [
    'controller' => 'service',
    'action'     => 'add'
]);
//edit service
$router->addPut('/services/{id}', [
    'controller' => 'service',
    'action'     => 'edit'
]);
//delete service
$router->addDelete('/services/{id}', [
    'controller' => 'service',
    'action'     => 'delete'
]);

//get all services

$router->addGet('/services/all', [
    'controller' => 'service',
    'action' => 'getAll',
]);
//add table
$router->addPost('/tables/add', [
    'controller' => 'table',
    'action' => 'add',
]);
// Route to edit a table
$router->add('/table/edit/{id}', [
    'controller' => 'table',
    'action'     => 'edit'
]);

// Route to delete a table
$router->add('/table/delete/{id}', [
    'controller' => 'table',
    'action'     => 'delete'
]);
//get all tables
$router->add('/tables/all', [
    'controller' => 'table',
    'action' => 'getAll'
]);
//reserve room
$router->addPost('/room/reservation', [
    'controller' => 'roomreservation',
    'action'     => 'reserve'
]);
// Route for table reservations
$router->addPost('/table/reservation', [
    'controller' => 'tableReservation',
    'action'     => 'reserve'
]);
//confim table reserves
$router->addPost('/table/reservations/{id}', [
    'controller' => 'TableReservationStatus',
    'action' => 'confirm'
]);
//cancel reserves
$router->addPut('/table/reservations/{id}', [
    'controller' => 'TableReservationStatus',
    'action' => 'cancel'
]);

// Confirm Room Reservation
$router->addPost('/room/reservation/status/confirm/{id}', [
    'controller' => 'RoomReservationStatus',
    'action' => 'confirm'
]);

// Cancel Room Reservation
$router->addPost('/room/reservation/status/cancel/{id}', [
    'controller' => 'RoomReservationStatus',
    'action' => 'cancel'
]);


$router->handle($_SERVER['REQUEST_URI']);
