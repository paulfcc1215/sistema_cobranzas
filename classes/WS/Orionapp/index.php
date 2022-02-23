<?php

  error_reporting(E_ALL);
  require '../../../config.php';

  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json; charset=UTF-8");
  header("Access-Control-Allow-Methods: POST");
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


  if ($_SERVER['REQUEST_METHOD'] == 'POST')
  {
    $servicios_permitidos = array('login','validar_usuario','entrega','save');

    switch ($_POST['type']){
      case 'login':
        $q = 'SELECT * FROM '
      break;
    }




    print_arr($servicios_permitidos);
    print_arr($_SERVER);
    die();
      $input = $_POST;
      $sql = "INSERT INTO posts
            (title, status, content, user_id)
            VALUES
            (:title, :status, :content, :user_id)";
      $statement = $dbConn->prepare($sql);
      bindAllValues($statement, $input);
      $statement->execute();
      $postId = $dbConn->lastInsertId();
      if($postId)
      {
        $input['id'] = $postId;
        header("HTTP/1.1 200 OK");
        echo json_encode($input);
        exit();
    }
  }else{
    header("HTTP/1.1 400 Bad Request");
  }
