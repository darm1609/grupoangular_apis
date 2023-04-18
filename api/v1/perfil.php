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
                if(!isset($headers["Authorization"]) or empty($headers["Authorization"])) {
                    header("HTTP/1.1 401 Unauthorized");
                    return;
                }

                $token = trim(str_replace("Bearer"," ",$headers["Authorization"]));
                if (empty($token)) {
                    header("HTTP/1.1 401 Unauthorized");
                    return;
                }

                if(@Auth::Check($token) == null or !@Auth::Check($token)) {
                    header("HTTP/1.1 401 Unauthorized");
                    return;
                }

                if (!ValidarToken($token, $bd)) {
                    header("HTTP/1.1 401 Unauthorized");
                    return;
                }

                if (!isset($_GET["opcion"])) {
                    header('HTTP/1.1 500 Internal Server Error');
                    return;
                }

                if ($_GET["opcion"] == "todo") {
                    $resultado = array();
                    $sql = "SELECT
                        pe.Id,
                        pe.Nombre Perfil
                    FROM
                        perfiles pe;";
                    $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                    $i = 0;
                    if (count($resultadoSql)) {
                        foreach ($resultadoSql as $item) {
                            $resultado[$i] = array(
                                'Id' => $item->Id,
                                'Perfil' => $item->Perfil,
                                'Permisos' => array()
                            );
                            $sql = "SELECT
                                pe.Id,
                                pe.Permiso Permiso 
                            FROM
                                perfil_permiso pp
                                INNER JOIN permisos pe ON pp.PermisoId = pe.Id
                            WHERE
                                pp.PerfilId = ".$item->Id.";";
                            $resultadoSql2 = json_decode($bd->ejecutarConsultaJson($sql));
                            if (count($resultadoSql2)) {
                                foreach ($resultadoSql2 as $item) {
                                    $resultado[$i]["Permisos"][] = array(
                                        'Id' => $item->Id,
                                        'Permiso' => $item->Permiso
                                    );
                                }
                            }
                            $i++;
                        }
                    }
                    echo json_encode($resultado);
                }
                
                break;
            case "DELETE":
                break;
            default:
                header("HTTP/1.0 405 Method Not Allowed");
                break;
        }
    }
    else
        header('HTTP/1.1 500 Internal Server Error');
?>