<?php
    require_once("vendor/autoload.php");
    require_once("config.php");
    require_once("auth.php");
    require_once("librerias/basedatos.php");

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    $requestMethod = $_SERVER["REQUEST_METHOD"];
    
    $headers=array();

    foreach (getallheaders() as $name => $value) {
        $headers[$name] = $value;
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json);
    
    global $clienteId, $servidor, $puerto, $usuario, $pass, $basedatos;
    
    $bd=new BaseDatos($servidor,$puerto,$usuario,$pass,$basedatos);
	if($bd->conectado)
	{
        switch($requestMethod) {
            case "OPTIONS":
                break;
            case "GET":
                $resultado = array();
                $resultado["passwordHash"] = hash('sha512', $data->password);
                $resultado["accessKey"] = Auth::SignIn(['login' => $data->login]);
                echo json_encode($resultado);
                break;
            default:
                header("HTTP/1.0 405 Method Not Allowed");
                break;
        }
    }
    else
        header("HTTP/1.1 404 Not Found");
?>