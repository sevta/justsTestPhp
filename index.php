<?php

require __DIR__ . '/vendor/autoload.php';
require './config.php';

session_start();

$settings = require __DIR__ . '/config.php';

$app = new Slim\App($settings);
$container = $app->getContainer();

$capsule = new Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$container['view'] = function ($container) {
    $templates = __DIR__ . '/pages/';
    $view = new Slim\Views\Twig($templates);
    return $view;
};

$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};

$container['db'] = function ($c) {
	$settings = $c->get('settings')['db'];
	$pdo = new PDO("mysql:host=" . $settings['host'] . ";port=3307;dbname=" . $settings['dbname'],
		$settings['user'], $settings['pass']
	);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  return $pdo;
};

$app->get('/' , function ($req , $res) {
    $stmt = $this->db->query("SELECT * FROM users");
    $user = $stmt->fetchAll();
    $messages = $this->flash->getMessages();

    // echo $messages;
    
    // return $res->withJson($user);
    return $this->view->render($res , 'index.twig' , ['users' => $user , "message" => $messages]);
});

$app->post('/post' , function ($req , $res) {
    $data = $req->getParams();
    try {
        $stmt = $this->db->prepare('INSERT INTO users (username , age , nick) VALUES (? , ? , ?)');
        $stmt->execute([
            $data['username'] , $data['age'] , $data['nick']
        ]);
        $this->flash->addMessage('message' , 'success add file');
        return $res->withStatus(302)->withHeader('Location' , '/');
    } catch (Exception $e) {
        return $e->getMessage();
    };
    

    // return $res->withJson($data['username']);
});

$app->get('/login' , function ($req , $res) {
    $users = [
        [
            "username" => "tesi adu" ,
            "age" => 40
        ] ,
        [
            "username" => "tesi adu" ,
            "age" => 40
        ] ,
        [
            "username" => "tesi adu" ,
            "age" => 40
        ] ,
        [
            "username" => "tesi adu" ,
            "age" => 40
        ] ,
    ];
    foreach ($users as $user) {
        echo $user['username'];
    };
});

$app->run();