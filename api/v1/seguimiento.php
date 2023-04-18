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

    function BorrarSeguimiento($id, $bd) {
        $sql = "DELETE FROM seguimiento WHERE Id = '" . $id . "';";
        if (!$bd->ejecutarConsultaUpdateDelete($sql))
            return false;
        return true;
    }

    global $clienteId, $servidor, $puerto, $usuario, $pass, $basedatos;
    $bd=new BaseDatos($servidor,$puerto,$usuario,$pass,$basedatos);
	if($bd->conectado)
	{
        switch($requestMethod) {
            case "OPTIONS":
                break;
            case "POST":
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

                if(!property_exists($data,"animalId") or !property_exists($data,"fecha") or 
                !property_exists($data,"estadoId") or !property_exists($data,"observacion"))
                {
                    header('HTTP/1.1 500 Internal Server Error');
                    return;
                }

                $tokenData = Auth::GetData($token);
                $usuarioId = $tokenData->id;

                $resultado = array();

                $sql = "INSERT INTO seguimiento (AnimalId, FechaHora, EstadoId, Observacion, FechaDeCreacion, CreadoPorUsuarioId) VALUES (";
                
                $sql .= $data->animalId . ", ";

                if (!isset($data->fecha))
                    $sql .= "NULL, ";
                else
                    $sql .= strtotime($data->fecha) . ", ";

                if (!isset($data->estadoId))
                    $sql .= "NULL, ";
                else
                    $sql .= "'" . $data->estadoId . "', ";

                if (!isset($data->observacion))
                    $sql .= "NULL, ";
                else
                    $sql .= "'" . $data->observacion . "', ";

                $sql .= time() . ", ";
                
                $sql .= $usuarioId . ");";

                if($bd->ejecutarConsulta($sql)) {
                    echo json_encode($data);
                    return;
                }

                header('HTTP/1.1 500 Internal Server Error');
                return;

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

                $resultado = array();

                if (isset($_GET["opcion"]) and $_GET["opcion"] == "estados")
                {
                    $sql = "SELECT 
                        Id,
                        Nombre
                    FROM
                        seguimiento_estado;";

                    $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                    if (count($resultadoSql)) {
                        foreach ($resultadoSql as $item) {
                            $resultado[] = array(
                                'Id' => $item->Id,
                                'Nombre' => $item->Nombre
                            );
                        }
                    }

                    echo json_encode($resultado);
                    return;
                }

                if (!isset($_GET["animalid"])) {
                    header('HTTP/1.1 500 Internal Server Error');
                    return;
                }

                $sql = "SELECT
                    ss.Id,
                    aa.Codigo,
                    aa.Nombre,
                    ss.FechaHora,
                    se.Nombre Estado,
                    ss.Observacion,
                    ss.FechaDeCreacion,
                    ss.CreadoPorUsuarioId,
                    uu.Correo Usuario,
                    CONCAT(pp.PrimerNombre,' ',pp.PrimerApellido) NombreDeUsuario
                FROM
                    animal aa                    
                    INNER JOIN seguimiento ss ON aa.Id = ss.AnimalId
                    INNER JOIN usuario uu ON ss.CreadoPorUsuarioId = uu.Id
                    INNER JOIN persona pp ON uu.PersonaId = pp.Id
                    INNER JOIN seguimiento_estado se ON se.Id = ss.EstadoId
                WHERE ss.AnimalId = '".$_GET["animalid"]."' ORDER BY ss.FechaHora desc;"; 
                 $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                 if (count($resultadoSql)) {
                     foreach ($resultadoSql as $item) {
                        $fecha = $item->FechaHora;
                        if (!empty($fecha))
                            $fecha = date("d-m-Y", $item->FechaHora);
                        else
                            $fecha = null;
                         $resultado[] = array(
                            'Id' => $item->Id,
                            'Codigo' => $item->Codigo,
                            'Nombre' => $item->Nombre,
                            'FechaHora' => $fecha,
                            'Estado' => $item->Estado,
                            'Observacion' => $item->Observacion,
                            'FechaDeCreacion' => date("d-m-Y", $item->FechaDeCreacion),
                            'CreadoPorUsuarioId' => $item->CreadoPorUsuarioId,
                            'Usuario' => $item->Usuario,
                            'NombreDeUsuario' => $item->NombreDeUsuario
                         );
                     }
                 }
                echo json_encode($resultado);
                break;
            case "DELETE":
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

                if (!BorrarSeguimiento($_GET["id"], $bd))
                {
                    header('HTTP/1.1 500 Internal Server Error');
                    return;
                }
                
                $resultado = array();
                echo json_encode($resultado);
                break;
            default:
                header("HTTP/1.0 405 Method Not Allowed");
                break;
        }
    }
    else
        header('HTTP/1.1 500 Internal Server Error');
?>