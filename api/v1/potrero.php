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

    function ValidarToken($token, $bd) {
        $resultado = array();
        $resultado = Auth::GetData($token);
        $sql = "SELECT
        u.Id,
        u.Correo Login,
        CONCAT(pp.PrimerNombre,' ',pp.SegundoNombre) Nombres,
        CONCAT(pp.PrimerApellido,' ',pp.SegundoApellido) Apellidos
        FROM
        usuario u
        INNER JOIN persona pp on u.PersonaId=pp.Id
        WHERE u.Id='".$resultado->id."';";
        $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
        if (count($resultadoSql)) {
            return true;
        }
        return false;
    }

    global $clienteId, $servidor, $puerto, $usuario, $pass, $basedatos;
    $bd=new BaseDatos($servidor,$puerto,$usuario,$pass,$basedatos);
	if($bd->conectado)
	{
        switch($requestMethod) {
            case "OPTIONS":
                break;
            case "POST":
                break;
            case "GET":
                if(isset($headers["Authorization"]))
                    $token = $headers["Authorization"];
                else {
                    header("HTTP/1.1 401 Unauthorized");
                    return;
                }
                if(isset($token) and !empty($token)) {
                    $token = trim(str_replace("Bearer"," ",$token));
                    if (empty($token)) {
                        header("HTTP/1.1 401 Unauthorized");
                        return;
                    }
                    if(@Auth::Check($token) !== null and @Auth::Check($token)) {
                        if (ValidarToken($token, $bd)) {
                            $resultado = array();

                            $sql = "SELECT
                                pp.Id,
                                ub.Nombre Ubicacion,
                                pp.Nombre Potrero
                            FROM
                                potrero pp
                                INNER JOIN ubicacion ub ON pp.UbicacionId = ub.Id;";
                            $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                            if (count($resultadoSql)) {
                                foreach ($resultadoSql as $item) {
                                    $resultado[] = array(
                                        'Id' => $item->Id,
                                        'Ubicacion' => $item->Ubicacion,
                                        'Potrero' => $item->Potrero
                                    );
                                }
                            }

                            echo json_encode($resultado);
                            return;
                        }
                        else
                            header("HTTP/1.1 401 Unauthorized");
                    }
                    else
                        header("HTTP/1.1 401 Unauthorized");
                }
                else
                    header("HTTP/1.1 401 Unauthorized");
                return;
            default:
                header("HTTP/1.0 405 Method Not Allowed");
                break;
        }
    }
    else
        header('HTTP/1.1 500 Internal Server Error');
?>