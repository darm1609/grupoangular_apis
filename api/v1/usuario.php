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

                $tokenData = Auth::GetData($token);
                $usuarioId = $tokenData->id;

                $sql_persona = "INSERT INTO persona (PrimerNombre, SegundoNombre, PrimerApellido, SegundoApellido, EsPropietario, FechaDeCreacion, CreadoPorUsuarioId) VALUES (";

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

                $propietario = "0";
                if ($data->propietario)
                    $propietario = "1";

                if (!isset($data->propietario))
                    $sql_persona .= "NULL, ";
                else
                    $sql_persona .= $propietario. ", ";

                $sql_persona .= time() . ", ";
                
                $sql_persona .= $usuarioId . ");";

                if($bd->ejecutarConsulta($sql_persona)) {
                    $id_persona = $bd->ultimo_result;

                    if (isset($data->usuario) and $data->usuario != null) {
                        $sql_usuario = "INSERT INTO usuario (PersonaId, Correo, Password, FechaDeCreacion, CreadoPorUsuarioId) VALUES (";

                        $sql_usuario .= $id_persona. ", ";

                        if (!isset($data->usuario->login))
                            $sql_usuario .= "NULL, ";
                        else
                            $sql_usuario .= "'" . $data->usuario->login . "', ";

                        if (!isset($data->usuario->password))
                            $sql_usuario .= "NULL, ";
                        else
                            $sql_usuario .= "'" . $data->usuario->password . "', ";

                        $sql_usuario .= time() . ", ";
                
                        $sql_usuario .= $usuarioId . ");";

                        if($bd->ejecutarConsulta($sql_usuario)) {
                            $id_usuario = $bd->ultimo_result;

                            foreach ($data->usuario->perfiles as $i => $v) {
                                $sql_usuario_perfil = "INSERT INTO usuario_perfiles (UsuarioId, PerfilId) VALUES (";
                                
                                $sql_usuario_perfil .= $id_usuario . ", ";
        
                                $sql_usuario_perfil .= $v->Id . ");";
        
                                if (!$bd->ejecutarConsulta($sql_usuario_perfil)) {
                                    header('HTTP/1.1 500 Internal Server Error');
                                    return;
                                }
                            }
                        }
                    }
                }

                echo json_encode($resultado);
                return;
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

                if (isset($_GET["login"])) {
                    $resultado = array();
                    $sql = "SELECT * FROM usuario WHERE Correo = '".$_GET["login"]."';";
                    $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                    if (count($resultadoSql)) {
                        header("HTTP/1.1 200 Found");
                        echo json_encode($resultado);
                    }
                    else {
                        header("HTTP/1.1 404 Not Found");
                        echo json_encode($resultado);
                    }
                    return;
                }

                if (isset($_GET["opcion"]) and $_GET["opcion"] == "usuarios") {
                    $resultado = array();
                    $sql = "SELECT
                        uu.Id,
                        uu.Correo Login,
                        (CASE WHEN uu.Habilitado = 1 THEN 'Sí' ELSE 'No' END) Habilitado,
                        pp.PrimerNombre,
                        pp.SegundoNombre,
                        pp.PrimerApellido,
                        pp.SegundoApellido,
                        uu.FechaDeCreacion,
                        CONCAT(ppc.PrimerNombre,' ',ppc.PrimerApellido) NombreDeUsuarioCreador,
                        uu.FechaDeModificacion,
                        CONCAT(ppa.PrimerNombre,' ',ppa.PrimerApellido) NombreDeUsuarioModificador
                    FROM
                        usuario uu
                        INNER JOIN persona pp ON uu.PersonaId = pp.Id
                        LEFT JOIN usuario uuc ON uu.CreadoPorUsuarioId = uuc.Id
                        LEFT JOIN persona ppc ON uuc.PersonaId = ppc.Id
                        LEFT JOIN usuario uua ON uu.ModificadoPorUsuarioId = uua.Id
                        LEFT JOIN persona ppa ON uua.PersonaId = ppa.Id;";
                    $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                    foreach ($resultadoSql as $item) {
                        $fechaDeCreacion = null;
                        $fechaDeModificacion = null;                
                        if (!empty($item->FechaDeCreacion))
                            $fechaDeCreacion = date("d-m-Y",$item->FechaDeCreacion);
                        if (!empty($item->FechaDeModificacion))
                            $fechaDeModificacion = date("d-m-Y",$item->FechaDeModificacion);
                        $resultado[] = array(
                            'Id' => $item->Id,
                            'Login' => $item->Login,
                            'Habilitado' => $item->Habilitado,
                            'PrimerNombre' => $item->PrimerNombre,
                            'SegundoNombre' => $item->SegundoNombre,
                            'PrimerApellido' => $item->PrimerApellido,
                            'SegundoApellido' => $item->SegundoApellido,
                            'FechaDeCreacion' => $fechaDeCreacion,
                            'CreadoPor' => $item->NombreDeUsuarioCreador,
                            'FechaDeModificacion' => $fechaDeModificacion,
                            'ModificadoPor' => $item->NombreDeUsuarioModificador,
                        );
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