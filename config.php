<?php 

return [
  'settings' => [
    // Slim Settings
    'determineRouteBeforeAppMiddleware' => false,
    'displayErrorDetails' => true,
    'db' => [
        "driver" => "mysql" ,
        "host" => "localhost" ,
        'dbname' => 'slim',
        'user' => 'root',
        'pass' => '',
    ]
  ]
];