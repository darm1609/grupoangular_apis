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

    $data = json_decode(file_get_contents('php://input'));

    global $clienteId, $servidor, $puerto, $usuario, $pass, $basedatos;
    $bd=new BaseDatos($servidor,$puerto,$usuario,$pass,$basedatos);
	if($bd->conectado)
	{
        switch($requestMethod) {
            case "OPTIONS":
                break;
            case "POST":

                $sql = "SELECT * FROM usuario WHERE Correo = '". $data->usuario->login ."';";
                $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                if (count($resultadoSql)) {
                    header("HTTP/1.1 409 Conflict");
                    $message = array("message" => "Login already exists");
                    echo json_encode($message);
                    return;
                }

                $sql_persona = "INSERT INTO persona (PrimerNombre, SegundoNombre, PrimerApellido, SegundoApellido, FechaDeCreacion) VALUES (";

                if (!isset($data->primerNombre))
                    $sql_persona .= "NULL, ";
                else
                    $sql_persona .= "'" . $data->primerNombre . "', ";

                if (!isset($data->segundoNombre))
                    $sql_persona .= "NULL, ";
                else
                    $sql_persona .= "'" . $data->segundoNombre . "', ";

                if (!isset($data->primerApellido))
                    $sql_persona .= "NULL, ";
                else
                    $sql_persona .= "'" . $data->primerApellido . "', ";

                if (!isset($data->segundoApellido))
                    $sql_persona .= "NULL, ";
                else
                    $sql_persona .= "'" . $data->segundoApellido . "', ";

                $sql_persona .= time() . ");";

                if($bd->ejecutarConsulta($sql_persona)) {
                    $id_persona = $bd->ultimo_result;

                    if (isset($data->usuario) and $data->usuario != null) {
                        $sql_usuario = "INSERT INTO usuario (PersonaId, Correo, Password, FechaDeCreacion) VALUES (";

                        $sql_usuario .= $id_persona. ", ";

                        if (!isset($data->usuario->login))
                            $sql_usuario .= "NULL, ";
                        else
                            $sql_usuario .= "'" . $data->usuario->login . "', ";

                        if (!isset($data->usuario->password))
                            $sql_usuario .= "NULL, ";
                        else
                            $sql_usuario .= "'" . hash('sha512', $data->usuario->password) . "', ";

                        $sql_usuario .= time() . ");";

                        if($bd->ejecutarConsulta($sql_usuario)) {
                            $id_usuario = $bd->ultimo_result;

                        }
                    }
                }

                header("HTTP/1.1 201 Created");
                $message = array("message" => "Resource created");
                echo json_encode($message);
                return;
            case "GET":
                header("HTTP/1.0 405 Method Not Allowed");
                break;
            case "DELETE":
                header("HTTP/1.0 405 Method Not Allowed");
                break;
            default:
                header("HTTP/1.0 405 Method Not Allowed");
                break;
        }
    }
    else
        header('HTTP/1.1 500 Internal Server Error');
?>