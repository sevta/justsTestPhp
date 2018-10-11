<?php

require __DIR__ . '/vendor/autoload.php';
require './config.php';

$settings = require __DIR__ . '/config.php';

$app = new Slim\App($settings);

$container = $app->getContainer();

$app->add(new \Slim\Middleware\Session([
  'name' => 'session',
  'autorefresh' => true,
  'lifetime' => '1 hour'
]));

$container['session'] = function ($c) {
  return new \SlimSession\Helper;
};

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
	$pdo = new PDO("mysql:host=" . $settings['host'] . ";port=3306;dbname=" . $settings['dbname'],
		$settings['user'], $settings['pass']
	);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  return $pdo;
};

$app->get('/' , function ($req , $res) {
  $auth = $this->session->auth;
  if ($auth) {
    return $res->withHeader('Location' , '/home');
  } else {
    $stmt = $this->db->query("SELECT * FROM users");
    $user = $stmt->fetchAll();
    $messages = $this->flash->getMessages();
    // echo $messages;
    // return $res->withJson($user);
    return $this->view->render($res , 'index.twig' , ['users' => $user , "message" => $messages]);
  }
});

$app->post('/post' , function ($req , $res) {
    $data = $req->getParams();
    $username = $data['username'];
    $pass = $data['password'];
    $pass2 = $data['password2'];

    if ($pass !== $pass2) {
      $this->flash->addMessage('message' , 'Password not same!');
      return $res->withHeader('Location', '/');
    } else {
      if (empty($username) || empty($pass)) {
        $this->flash->addMessage('message' , 'username or pass cannot be empty');
        return $res->withStatus(302)->withHeader('Location' , '/');
      } else {
        $check = $this->db->prepare('SELECT username FROM users WHERE username=? ');
  
        $check->execute([$username]);
  
        $result = $check->rowCount();
        var_dump($result);
  
        if ($result >= 1) {
          $this->flash->addMessage('message' , 'username has been registered');
          return $res->withHeader('Location' , '/');
        } else {
          $hash = password_hash($pass , PASSWORD_BCRYPT);
          try {
            $stmt = $this->db->prepare('INSERT INTO users (username  , password) VALUES (? , ?)');
            $stmt->execute([
                $username , $hash
            ]);
            $this->flash->addMessage('message' , 'success add file');
            return $res->withStatus(302)->withHeader('Location' , '/');
          } catch (PDOException $e) {
              return $e->getMessage();
          };
        }
      }  
    }
});

$app->post('/login' , function ($req , $res) {
  $data = $req->getParams();
  $username = $data['username'];
  $pass = $data['password'];
  $userpass = null;
  if (empty($username) || empty($pass)) {
    $this->flash->addMessage('message' , 'username or password cannot be empty');
    return $res->withHeader('Location' , '/login');
  } else {
    $stmt = $this->db->prepare('SELECT username , password FROM users WHERE username=?');
    $stmt->execute([$username]);
    $checkusername = $stmt->rowCount();
    $allusers = $stmt->fetch();
    $userpass = $allusers['password'];
    $verify = password_verify($pass , $userpass);
    // var_dump($verify);
    if ($checkusername <= 0) {
      $this->flash->addMessage('message' , 'username tidak terdaftar');
      return $res->withHeader('Location' , '/login');
    } else if ($verify) {
      $this->session->set('auth' , $username);
      $val = $this->session->auth;
      return $res->withHeader('Location' , '/home');
      die();
    }
  }
});

$app->get('/login' , function ($req , $res) {
  $auth = $this->session->auth;
  
  if ($auth !== null) {
    return $res->withHeader('Location' , '/home');
  } else {
    $message = $this->flash->getMessages();
    return $this->view->render($res , 'auth/login.twig' , ['message' => $message]);
  }
});

$app->get('/home' , function ($req , $res) {
  $auth = $this->session->auth;
  if ($auth) {
    return $this->view->render($res , 'home/home.twig' , ['user' => $auth]);
  } else {
    $this->flash->addMessage('message' , 'not auth');
    return $res->withHeader('Location' , '/');
  }
});

$app->get('/logout' , function ($req , $res) {
  $this->session::destroy();
  $this->flash->addMessage('message' , 'logout');
  return $res->withHeader('Location' , '/login');
});

$app->run();