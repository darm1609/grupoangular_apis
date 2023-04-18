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
                $resultado = array();
                if(isset($data->login) and isset($data->password)) {
                    if(!empty($data->login) and !empty($data->password)) {
                        $data->password = hash('sha512', $data->password);
                        $sql="SELECT
                        u.Id,
                        u.Correo Login,
                        CONCAT(pp.PrimerNombre,' ',pp.SegundoNombre) Nombres,
                        CONCAT(pp.PrimerApellido,' ',pp.SegundoApellido) Apellidos,
                        pp.PrimerNombre PrimerNombre,
                        pp.SegundoNombre SegundoNombre,
                        pp.PrimerApellido PrimerApellido,
                        pp.SegundoApellido SegundoApellido
                        FROM
                        usuario u
                        INNER JOIN persona pp on u.PersonaId=pp.Id
                        WHERE u.Correo='".$data->login."' AND u.Password='".$data->password."';";
                        $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                        if (count($resultadoSql)) {
                            $resultado["tokenKey"] = Auth::SignIn([ 'id' => $resultadoSql[0]->Id,
                                                                    'login' => $resultadoSql[0]->Login,
                                                                    'nombres' => $resultadoSql[0]->Nombres,
                                                                    'apellidos' => $resultadoSql[0]->Apellidos,
                                                                    'primerNombre' => $resultadoSql[0]->PrimerNombre,
                                                                    'segundoNombre' => $resultadoSql[0]->SegundoNombre,
                                                                    'primerApellido' => $resultadoSql[0]->PrimerApellido,
                                                                    'segundoApellido' => $resultadoSql[0]->SegundoApellido]);
                            $resultado["login"] = $resultadoSql[0]->Login;                                        
                            $resultado["nombres"] = $resultadoSql[0]->Nombres;
                            $resultado["apellidos"] = $resultadoSql[0]->Apellidos;
                            $resultado["primerNombre"] = $resultadoSql[0]->PrimerNombre;
                            $resultado["segundoNombre"] = $resultadoSql[0]->SegundoNombre;
                            $resultado["primerApellido"] = $resultadoSql[0]->PrimerApellido;
                            $resultado["segundoApellido"] = $resultadoSql[0]->SegundoApellido;
                            echo json_encode($resultado);
                            return;
                        }
                        else
                            header("HTTP/1.1 401 Unauthorized");
                    }
                }
                else {
                    header('HTTP/1.1 500 Internal Server Error');
                }
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
                            $resultado = array();
                            $resultado["login"] = $resultadoSql[0]->Login;
                            $resultado["nombres"] = $resultadoSql[0]->Nombres;
                            $resultado["apellidos"] = $resultadoSql[0]->Apellidos;
                            echo json_encode($resultado);
                            return;
                        }
                        else {
                            header("HTTP/1.1 401 Unauthorized");
                            return;
                        }
                    }
                    else
                        header("HTTP/1.1 401 Unauthorized");
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