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



$router->handle($_SERVER['REQUEST_URI']);
