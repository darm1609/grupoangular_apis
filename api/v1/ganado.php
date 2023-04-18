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

    function InfoGanadoById($id,$bd) {
        $resultado = array();
        $sql = "SELECT
            ani.Id Id,
            ani.Codigo Codigo,
            ani.Nombre Nombre,
            ani.FechaDeNacimiento,
            ani.Sexo,
            pot.Id PotreroId,
            pot.Nombre Potrero,
            ani.MadreId,
            ani.PadreId,
            ani.Color,
            ani.Descripcion
        FROM
            animal ani
            LEFT JOIN potrero pot ON ani.PotreroId = pot.Id
        WHERE
            ani.Id = '".$id."';";
        $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
        if (count($resultadoSql)) {
            foreach ($resultadoSql as $item) {
                $resultado = array(
                    'Id' => $item->Id,
                    'PotreroId' => $item->PotreroId,
                    'Potrero' => $item->Potrero,
                    'Codigo' => $item->Codigo,
                    'Nombre' => $item->Nombre,
                    'FechaDeNacimiento' => $item->FechaDeNacimiento ? date("d-m-Y", $item->FechaDeNacimiento) : null,
                    'Sexo' => $item->Sexo,
                    'Color' => $item->Color,
                    'Descripcion' => $item->Descripcion,
                    'MadreId' => $item->MadreId,
                    'PadreId' => $item->PadreId,
                    'Raza' => array(),
                    'Hierro' => array(),
                    'Propietario' => array(),
                    'Seguimiento' => array()
                );
            }

            $sql = "SELECT
                r.Id RazaId,
                r.Nombre Raza,
                ar.Porcentaje
            FROM
                raza r
                INNER JOIN animal_raza ar on r.Id = ar.RazaId
            WHERE
                ar.AnimalId = '".$id."';";
            $resultadoSqlRaza = json_decode($bd->ejecutarConsultaJson($sql));
            if (count($resultadoSqlRaza)) {
                foreach ($resultadoSqlRaza as $itemRaza) {
                    $resultado["Raza"][] = array(
                        'RazaId' => $itemRaza->RazaId,
                        'Porcentaje' => $itemRaza->Porcentaje,
                        'Raza' => $itemRaza->Raza
                    );
                }
            }

            $sql = "SELECT
                h.Id HierroId,
                h.Nombre Hierro,
                ha.FechaDeHerraje,
                h.Comentario
            FROM
                hierro h
                INNER JOIN hierro_animal ha on h.Id = ha.HierroId
            WHERE
                ha.AnimalId = '".$id."';";
            $resultadoSqlHierro = json_decode($bd->ejecutarConsultaJson($sql));
            if (count($resultadoSqlHierro)) {
                foreach ($resultadoSqlHierro as $itemHierro) {
                    $resultado["Hierro"][] = array(
                        'HierroId' => $itemHierro->HierroId,
                        'Nombre' => $itemHierro->Hierro,
                        'FechaDeHerraje' => $itemHierro->FechaDeHerraje ? date("d-m-Y", $itemHierro->FechaDeHerraje) : null,
                        'Comentario' => $itemHierro->Comentario
                    );
                }
            }

            $sql = "SELECT
                pp.Id PersonaId,
                CONCAT(pp.PrimerNombre,' ', pp.PrimerApellido) Propietario,
                pa.Porcentaje
            FROM
                propietario_animal pa
                INNER JOIN persona pp ON pa.PersonaId = pp.Id
            WHERE
                pa.AnimalId = '".$id."';";
            $resultadoSqlPropietario = json_decode($bd->ejecutarConsultaJson($sql));
            if (count($resultadoSqlPropietario)) {
                foreach ($resultadoSqlPropietario as $itemPropietario) {
                    $resultado["Propietario"][] = array(
                        'PersonaId' => $itemPropietario->PersonaId,
                        'Nombre' => $itemPropietario->Propietario,
                        'Porcentaje' => $itemPropietario->Porcentaje
                    );
                }
            }

            $sql = "SELECT
                s.Id SeguimientoId, 
                s.FechaHora FechaDeSeguimiento,
                se.Nombre Estado,
                s.Observacion
            FROM
                seguimiento s
            INNER JOIN seguimiento_estado se ON s.EstadoId = se.Id
            WHERE
                s.AnimalId = '".$id."'
            ORDER BY s.FechaHora desc, s.Id desc;";
            $resultadoSqlSeguimiento = json_decode($bd->ejecutarConsultaJson($sql));
            if (count($resultadoSqlSeguimiento)) {
                foreach ($resultadoSqlSeguimiento as $itemSeguimiento) {
                    $resultado["Seguimiento"][] = array(
                        'SeguimientoId' => $itemSeguimiento->SeguimientoId,
                        'Fecha' => $itemSeguimiento->FechaDeSeguimiento ? date("d-m-Y", $itemSeguimiento->FechaDeSeguimiento) : null,
                        'Estado' => $itemSeguimiento->Estado,
                        'Observacion' => $itemSeguimiento->Observacion
                    );
                }
            }
        }
        return $resultado;
    }

    function crearArreglo($animalId, $bd) {
        $resultado = array();
        if ($animalId == NULL) {
            $sql = "SELECT
                ani.Id Id,
                ani.Codigo Codigo,
                COALESCE(ani.Nombre, 'Sin Nombre') Nombre,
                ani.FechaDeNacimiento,
                ani.Sexo,
                pot.Nombre Potrero
            FROM
                animal ani
                INNER JOIN potrero pot ON ani.PotreroId = pot.Id
            WHERE
                ani.MadreId is NULL AND
                ani.PadreId is NULL";
        }
        else {
            $sql = "SELECT
                ani.Id Id,
                ani.Codigo Codigo,
                COALESCE(ani.Nombre, 'Sin Nombre') Nombre,
                ani.FechaDeNacimiento,
                ani.Sexo,
                pot.Nombre Potrero
            FROM
                animal ani
                INNER JOIN potrero pot ON ani.PotreroId = pot.Id
            WHERE 
                ani.MadreId = '".$animalId."' OR 
                ani.PadreId = '".$animalId."';";
        }
        $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
        if (count($resultadoSql)) {
            foreach ($resultadoSql as $item) {
                $resultado[] = array(
                    'Potrero' => $item->Potrero,
                    'Codigo' => $item->Codigo,
                    'Nombre' => $item->Nombre,
                    'FechaDeNacimiento' => date("d-m-Y", $item->FechaDeNacimiento),
                    'Sexo' => $item->Sexo,
                    'Raza' => array(),
                    'Hierro' => array(),
                    'Propietario' => array(),
                    'Hijos' => crearArreglo($item->Id, $bd)
                );
                $pos = count($resultado) - 1;
                $sql = "SELECT
                    r.Nombre Raza,
                    ar.Porcentaje
                FROM
                    raza r
                    INNER JOIN animal_raza ar on r.Id = ar.RazaId
                WHERE
                    ar.AnimalId = '".$item->Id."';";
                $resultadoSqlRaza = json_decode($bd->ejecutarConsultaJson($sql));
                if (count($resultadoSqlRaza)) {
                    foreach ($resultadoSqlRaza as $itemRaza) {
                        $resultado[$pos]["Raza"][] = array(
                            'Porcentaje' => $itemRaza->Porcentaje,
                            'Raza' => $itemRaza->Raza
                        );
                    }
                }
                $sql = "SELECT
                    h.Nombre Hierro,
                    COALESCE(h.Comentario, 'Sin Nombre') Comentario
                FROM
                    hierro h
                    INNER JOIN hierro_animal ha on h.Id = ha.HierroId
                WHERE
                    ha.AnimalId = '".$item->Id."';";
                $resultadoSqlHierro = json_decode($bd->ejecutarConsultaJson($sql));
                if (count($resultadoSqlHierro)) {
                    foreach ($resultadoSqlHierro as $itemhierro) {
                        $resultado[$pos]["Hierro"][] = array(
                            'Hierro' => $itemhierro->Hierro,
                            'Comentario' => $itemhierro->Comentario
                        );
                    }
                }
                $sql = "SELECT
                    CONCAT(p.PrimerNombre,' ',p.SegundoNombre,' ',p.PrimerApellido,' ',p.SegundoApellido) Nombre,
                    pa.Porcentaje
                FROM
                    persona p
                    INNER JOIN propietario_animal pa on p.Id = pa.PersonaId
                WHERE
                    pa.AnimalId = '".$item->Id."';";
                $resultadoSqlPropietario = json_decode($bd->ejecutarConsultaJson($sql));
                if (count($resultadoSqlPropietario)) {
                    foreach ($resultadoSqlPropietario as $itempersona) {
                        $resultado[$pos]["Propietario"][] = array(
                            'Nombre' => $itempersona->Nombre,
                            'Porcentaje'=> $itempersona->Porcentaje
                        );
                    }
                }
            }
        }
        return $resultado;
    }

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

    function BorrarAnimal($id_animal, $bd) {
        $sql_animal_raza = "DELETE FROM animal_raza WHERE AnimalId = '" . $id_animal . "';";
        $sql_hierro_animal = "DELETE FROM hierro_animal WHERE AnimalId = '" . $id_animal . "';";
        $sql_propietario_animal = "DELETE FROM propietario_animal WHERE AnimalId = '" . $id_animal . "';";
        $sql_seguimiento = "DELETE FROM seguimiento WHERE AnimalId = '" . $id_animal . "';";
        //$sql_animal_madre = "DELETE FROM animal WHERE MadreId = '" . $id_animal . "';";
        //$sql_animal_padre = "DELETE FROM animal WHERE PadreId = '" . $id_animal . "';";
        $sql_animal_madre = "UPDATE animal SET MadreId = NULL WHERE MadreId = '" . $id_animal . "'";
        $sql_animal_padre = "UPDATE animal SET PadreId = NULL WHERE PadreId = '" . $id_animal . "'";
        $sql_animal = "DELETE FROM animal WHERE Id = '" . $id_animal . "';";

        if (!$bd->ejecutarConsultaUpdateDelete($sql_animal_raza))
            return false;
        if (!$bd->ejecutarConsultaUpdateDelete($sql_hierro_animal))
            return false;
        if (!$bd->ejecutarConsultaUpdateDelete($sql_propietario_animal))
            return false;
        if (!$bd->ejecutarConsultaUpdateDelete($sql_seguimiento))
            return false;
        if (!$bd->ejecutarConsultaUpdateDelete($sql_animal_madre))
            return false;
        if (!$bd->ejecutarConsultaUpdateDelete($sql_animal_padre))
            return false;
        if (!$bd->ejecutarConsultaUpdateDelete($sql_animal))
            return false;
        return true;
    }

    function ActualizarDatos($data, $usuarioId, $bd) {
        $sql = "UPDATE animal SET ";
        if (!isset($data->codigo))
            $sql .= "Codigo = NULL, ";
        else
            $sql .= "Codigo = '".$data->codigo."', ";
        if (!isset($data->nombre))
            $sql .= "Nombre = NULL, ";
        else
            $sql .= "Nombre = '".$data->nombre."', ";
        if (!isset($data->madreId))
            $sql .= "MadreId = NULL, ";
        else
            $sql .= "MadreId = '".$data->madreId."', ";
        if (!isset($data->padreId))
            $sql .= "PadreId = NULL, ";
        else
            $sql .= "PadreId = '".$data->padreId."', ";
        if (!isset($data->sexo))
            $sql .= "Sexo = NULL, ";
        else
            $sql .= "Sexo = '".$data->sexo."', ";
        if (!isset($data->fechaDeNacimiento))
            $sql .= "FechaDeNacimiento = NULL, ";
        else
            $sql .= "FechaDeNacimiento = ".strtotime($data->fechaDeNacimiento) . ", ";
        if (!isset($data->potreroId))
            $sql .= "PotreroId = NULL, ";
        else
            $sql .= "PotreroId = ".$data->potreroId. ", ";
        if (!isset($data->color))
            $sql .= "Color = NULL, ";
        else
            $sql .= "Color = '".$data->color."', ";
        if (!isset($data->descripcion))
            $sql .= "Descripcion = NULL, ";
        else
            $sql .= "Descripcion = '".$data->descripcion."', ";
        $sql .= "ModificadoPorUsuarioId = ".$usuarioId. ", ";
        $sql .= "FechaDeModificacion = ".time(). ", ";
        $sql[strlen($sql) - 2] = " ";
        $sql = trim($sql);
        $sql .= " WHERE Id = '".$data->id."';";
        
        if (!$bd->ejecutarConsultaUpdateDelete($sql))
            return false;

        //Propietarios
        foreach ($data->propietarios as $v) {
            $sql = "SELECT * FROM propietario_animal WHERE AnimalId = '".$data->id."' AND PersonaId = ".$v->id.";";
            $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
            if (count($resultadoSql)) { //Existe
                $sql = "UPDATE propietario_animal SET ";
                if (!isset($v->id))
                    $sql .= "PersonaId = NULL, ";
                else
                    $sql .= "PersonaId = ".$v->id.", ";
                if (!isset($v->porcentaje))
                    $sql .= "Porcentaje = NULL, ";
                else
                    $sql .= "Porcentaje = ".$v->porcentaje.", ";
                $sql .= "ModificadoPorUsuarioId = ".$usuarioId. ", ";
                $sql .= "FechaDeModificacion = ".time(). ", ";
                $sql[strlen($sql) - 2] = " ";
                $sql = trim($sql);
                $sql .= " WHERE Id = ".$resultadoSql[0]->Id.";";
                if (!$bd->ejecutarConsultaUpdateDelete($sql))
                    return false;
            }
            else { //No existe
                $sql = "INSERT INTO propietario_animal (PersonaId, AnimalId, Porcentaje, FechaDeCreacion, CreadoPorUsuarioId, FechaDeModificacion, ModificadoPorUsuarioId) VALUES (";
                $sql .= $v->id.", ";
                $sql .= $data->id.", ";
                $sql .= $v->porcentaje.", ";
                $sql .= time().", ";
                $sql .= $usuarioId.", ";
                $sql .= time().", ";
                $sql .= $usuarioId.");";
                if(!$bd->ejecutarConsulta($sql))
                    return false;
            }
        }
        //Luego buscar los que esten en bd que no existen en mi json para eliminarlos
        $sql = "SELECT * FROM propietario_animal WHERE AnimalId = '".$data->id."';";
        $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
        if (count($resultadoSql)) {
            foreach ($resultadoSql as $item) {
                $existe = false;
                foreach ($data->propietarios as $v) {
                    if ($data->id == $item->AnimalId and $v->id == $item->PersonaId) {
                        $existe = true;
                    }
                }
                if (!$existe) { //Se debe borrar
                    $sql = "DELETE FROM propietario_animal WHERE Id = ".$item->Id.";";
                    if (!$bd->ejecutarConsultaUpdateDelete($sql))
                        return false;
                }
            }
        }

        //Hierros
        foreach ($data->hierros as $v) { 
            $sql = "SELECT * FROM hierro_animal WHERE AnimalId = '".$data->id."' AND HierroId = ".$v->id.";";
            $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
            if (count($resultadoSql)) { //Existe
                $sql = "UPDATE hierro_animal SET ";
                if (!isset($v->fechaDeHerraje))
                    $sql .= "FechaDeHerraje = NULL, ";
                else
                    $sql .= "FechaDeHerraje = ".strtotime($v->fechaDeHerraje).", ";
                $sql .= "ModificadoPorUsuarioId = ".$usuarioId. ", ";
                $sql .= "FechaDeModificacion = ".time(). ", ";
                $sql[strlen($sql) - 2] = " ";
                $sql = trim($sql);
                $sql .= " WHERE Id = ".$resultadoSql[0]->Id.";";
                if (!$bd->ejecutarConsultaUpdateDelete($sql))
                    return false;
            }
            else { //No existe
                $sql = "INSERT INTO hierro_animal (HierroId, AnimalId, FechaDeHerraje, FechaDeCreacion, CreadoPorUsuarioId, FechaDeModificacion, ModificadoPorUsuarioId) VALUES (";
                $sql .= $v->id.", ";
                $sql .= $data->id.", ";
                if (!isset($v->fechaDeHerraje))
                    $sql .= "NULL, ";
                else
                    $sql .= strtotime($v->fechaDeHerraje).", ";
                $sql .= time().", ";
                $sql .= $usuarioId.", ";
                $sql .= time().", ";
                $sql .= $usuarioId.");";
                if(!$bd->ejecutarConsulta($sql))
                    return false;
            }
        }
        //Luego buscar los que esten en bd que no existen en mi json para eliminarlos
        $sql = "SELECT * FROM hierro_animal WHERE AnimalId = '".$data->id."';";
        $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
        if (count($resultadoSql)) {
            foreach ($resultadoSql as $item) {
                $existe = false;
                foreach ($data->hierros as $v) {
                    if ($data->id == $item->AnimalId and $v->id == $item->HierroId) {
                        $existe = true;
                    }
                }
                if (!$existe) { //Se debe borrar
                    $sql = "DELETE FROM hierro_animal WHERE Id = ".$item->Id.";";
                    if (!$bd->ejecutarConsultaUpdateDelete($sql))
                        return false;
                }
            }
        }

        //Raza
        foreach ($data->razas as $v) {
            $sql = "SELECT * FROM animal_raza WHERE AnimalId = '".$data->id."' AND RazaId = ".$v->id.";";
            $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
            if (count($resultadoSql)) { //Existe
                $sql = "UPDATE animal_raza SET ";
                if (!isset($v->id))
                    $sql .= "RazaId = NULL, ";
                else
                    $sql .= "RazaId = ".$v->id.", ";
                if (!isset($v->porcentaje))
                    $sql .= "Porcentaje = NULL, ";
                else
                    $sql .= "Porcentaje = ".$v->porcentaje.", ";
                $sql .= "ModificadoPorUsuarioId = ".$usuarioId. ", ";
                $sql .= "FechaDeModificacion = ".time(). ", ";
                $sql[strlen($sql) - 2] = " ";
                $sql = trim($sql);
                $sql .= " WHERE Id = ".$resultadoSql[0]->Id.";";
                if (!$bd->ejecutarConsultaUpdateDelete($sql))
                    return false;
            }
            else { //No existe
                $sql = "INSERT INTO animal_raza (RazaId, AnimalId, Porcentaje, FechaDeCreacion, CreadoPorUsuarioId, FechaDeModificacion, ModificadoPorUsuarioId) VALUES (";
                $sql .= $v->id.", ";
                $sql .= $data->id.", ";
                $sql .= $v->porcentaje.", ";
                $sql .= time().", ";
                $sql .= $usuarioId.", ";
                $sql .= time().", ";
                $sql .= $usuarioId.");";
                if(!$bd->ejecutarConsulta($sql))
                    return false;
            }
        }
        //Luego buscar los que esten en bd que no existen en mi json para eliminarlos
        $sql = "SELECT * FROM animal_raza WHERE AnimalId = '".$data->id."';";
        $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
        if (count($resultadoSql)) {
            foreach ($resultadoSql as $item) {
                $existe = false;
                foreach ($data->razas as $v) {
                    if ($data->id == $item->AnimalId and $v->id == $item->RazaId) {
                        $existe = true;
                    }
                }
                if (!$existe) { //Se debe borrar
                    $sql = "DELETE FROM animal_raza WHERE Id = ".$item->Id.";";
                    if (!$bd->ejecutarConsultaUpdateDelete($sql))
                        return false;
                }
            }
        }

        return $data;
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

                if(!property_exists($data,"codigo") or !property_exists($data,"nombre") or 
                !property_exists($data,"fechaDeNacimiento") or !property_exists($data,"sexo") or 
                !property_exists($data,"potreroId") or !property_exists($data,"propietarios") or
                !property_exists($data,"hierros") or !property_exists($data,"madreId") or 
                !property_exists($data,"padreId") or !property_exists($data,"razas") or 
                !property_exists($data,"seguimiento"))
                {
                    header('HTTP/1.1 500 Internal Server Error');
                    return;
                }

                $tokenData = Auth::GetData($token);
                $usuarioId = $tokenData->id;

                $sql_animal = "INSERT INTO animal (Codigo, MadreId, PadreId, Nombre, FechaDeNacimiento, Sexo, PotreroId, Color, Descripcion, FechaDeCreacion, CreadoPorUsuarioId) VALUES (";
                
                if (!isset($data->codigo))
                    $sql_animal .= "NULL, ";
                else
                    $sql_animal .= "'" . $data->codigo . "', ";

                if (!isset($data->madreId))
                    $sql_animal .= "NULL, ";
                else
                    $sql_animal .= $data->madreId . ", ";

                if (!isset($data->padreId))
                    $sql_animal .= "NULL, ";
                else
                    $sql_animal .= $data->padreId . ", ";
                
                if (!isset($data->nombre))
                    $sql_animal .= "NULL, ";
                else
                    $sql_animal .= "'" . $data->nombre . "', ";

                if (!isset($data->fechaDeNacimiento))
                    $sql_animal .= "NULL, ";
                else
                    $sql_animal .= strtotime($data->fechaDeNacimiento) . ", ";

                $sql_animal .= "'" . $data->sexo . "', ";

                if (!isset($data->potreroId))
                    $sql_animal .= "NULL, ";
                else
                    $sql_animal .= $data->potreroId . ", ";

                if (!isset($data->color))
                    $sql_animal .= "NULL, ";
                else
                    $sql_animal .= "'" . $data->color . "', ";

                if (!isset($data->descripcion))
                    $sql_animal .= "NULL, ";
                else
                    $sql_animal .= "'" . $data->descripcion . "', ";

                $sql_animal .= time() . ", ";
                
                $sql_animal .= $usuarioId . ");";

                if($bd->ejecutarConsulta($sql_animal)) {
                    $id_animal = $bd->ultimo_result;
                    
                    if (isset($data->propietarios)) {
                        foreach ($data->propietarios as $i => $v) {
                            $sql_propietario = "INSERT INTO propietario_animal (PersonaId, AnimalId, Porcentaje, FechaDeCreacion, CreadoPorUsuarioId) VALUES (";
                            
                            $sql_propietario .= $v->id . ", ";
    
                            $sql_propietario .= $id_animal . ", ";
    
                            $sql_propietario .= $v->porcentaje . ", ";
    
                            $sql_propietario .= time() . ", ";
    
                            $sql_propietario .= $usuarioId . ");";
    
                            if (!$bd->ejecutarConsulta($sql_propietario)) {
                                BorrarAnimal($id_animal, $bd);
                                header('HTTP/1.1 500 Internal Server Error');
                                return;
                            }
                        }
                    }

                    if (isset($data->hierros)) {
                        foreach ($data->hierros as $i => $v) {
                            $sql_hierros = "INSERT INTO hierro_animal (HierroId, AnimalId, FechaDeHerraje, FechaDeCreacion, CreadoPorUsuarioId) VALUES (";
    
                            $sql_hierros .= $v->id . ", ";
    
                            $sql_hierros .= $id_animal . ", ";
    
                            if ($v->fechaDeHerraje != null)
                                $sql_hierros .= strtotime($v->fechaDeHerraje) . ", ";
                            else
                                $sql_hierros .= "NULL, ";
    
                            $sql_hierros .= time() . ", ";
    
                            $sql_hierros .= $usuarioId . ");";
    
                            if (!$bd->ejecutarConsulta($sql_hierros)) {
                                BorrarAnimal($id_animal, $bd);
                                header('HTTP/1.1 500 Internal Server Error');
                                return;
                            }
                        }
                    }

                    if (isset($data->razas)) {
                        foreach ($data->razas as $i => $v) {
                            $sql_razas = "INSERT INTO animal_raza (AnimalId, RazaId, Porcentaje, FechaDeCreacion, CreadoPorUsuarioId) VALUES (";
    
                            $sql_razas .= $id_animal . ", ";
    
                            $sql_razas .= $v->id . ", ";
    
                            $sql_razas .= $v->porcentaje . ", ";
    
                            $sql_razas .= time() . ", ";
    
                            $sql_razas .= $usuarioId . ");";
    
                            if (!$bd->ejecutarConsulta($sql_razas)) {
                                BorrarAnimal($id_animal, $bd);
                                header('HTTP/1.1 500 Internal Server Error');
                                return;
                            }
                        }
                    }

                    if (isset($data->seguimiento->estadoId)) {
                        $sql_seguimiento = "INSERT INTO seguimiento (AnimalId, FechaHora, EstadoId, Observacion, FechaDeCreacion, CreadoPorUsuarioId) VALUES (";

                        $sql_seguimiento .= $id_animal . ", ";

                        if (!isset($data->seguimiento->fecha))
                            $sql_seguimiento .= "NULL, ";
                        else
                            $sql_seguimiento .= strtotime($data->seguimiento->fecha) . ", ";

                        if (!isset($data->seguimiento->estadoId))
                            $sql_seguimiento .= "NULL, ";
                        else
                            $sql_seguimiento .= "'" . $data->seguimiento->estadoId . "', ";

                        if (!isset($data->seguimiento->observacion))
                            $sql_seguimiento .= "NULL, ";
                        else
                            $sql_seguimiento .= "'" . $data->seguimiento->observacion . "', ";

                        $sql_seguimiento .= time() . ", ";

                        $sql_seguimiento .= $usuarioId . ");";

                        if (!$bd->ejecutarConsulta($sql_seguimiento)) {
                            BorrarAnimal($id_animal, $bd);
                            header('HTTP/1.1 500 Internal Server Error');
                            return;
                        }
                    }
                }

                echo json_encode($resultado);
                return;
                break;
            case "GET":
                if (isset($_GET["opcion"]) and $_GET["opcion"] == "admin")
                {
                    $resultado = array();
                    $resultado = crearArreglo(NULL, $bd);
                    echo json_encode($resultado);
                    return;
                }
                if (isset($_GET["opcion"]) and $_GET["opcion"] == "todo")
                {
                    $resultado = array();
                    $sql = "SELECT 
                        aa.Codigo
                    FROM
                        animal aa";
                    $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                    if (count($resultadoSql)) {
                        foreach ($resultadoSql as $item) {
                            $resultado[] = array(
                                'Codigo' => $item->Codigo
                            );
                        }
                    }
                    echo json_encode($resultado);
                    return;
                }
                if (isset($_GET["opcion"]) and $_GET["opcion"] == "resumen")
                {
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
                                
                                $sql = "SELECT COUNT(*) Cantidad FROM animal;";
                                $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                                $resultado["CantidadDeGanado"] = $resultadoSql[0]->Cantidad;

                                $sql = "SELECT COUNT(*) Cantidad FROM animal WHERE sexo = 'Macho';";
                                $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                                $resultado["CantidadDeGanadoMacho"] = $resultadoSql[0]->Cantidad;

                                $sql = "SELECT COUNT(*) Cantidad FROM animal WHERE sexo = 'Hembra';";
                                $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                                $resultado["CantidadDeGanadoHembra"] = $resultadoSql[0]->Cantidad;

                                $sql = "SELECT
                                    pa.PersonaId PersonaId,
                                    CONCAT(pp.PrimerNombre,' ',pp.PrimerApellido) AS Propietario,
                                    COUNT(DISTINCT pa.AnimalId) AS Cantidad,
                                    SUM(CASE WHEN aa.Sexo = 'Macho' THEN 1 ELSE 0 END) AS Machos,
                                    SUM(CASE WHEN aa.Sexo = 'Hembra' THEN 1 ELSE 0 END) AS Hembras
                                FROM
                                    persona pp
                                    INNER JOIN propietario_animal pa ON pp.Id = pa.PersonaId
                                    INNER JOIN animal aa ON pa.AnimalId = aa.Id
                                GROUP BY pp.PrimerNombre, pp.PrimerApellido;";
                                $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                                if (count($resultadoSql)) {
                                    $resultado["Propietarios"] = array();
                                    foreach ($resultadoSql as $item) {
                                        $resultado["Propietarios"][] = array(
                                            'PersonaId' => $item->PersonaId,
                                            'Propietario' => $item->Propietario,
                                            'Cantidad' => $item->Cantidad,
                                            'Machos' => $item->Machos,
                                            'Hembras' => $item->Hembras
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
                        return;
                    }
                }
                if (isset($_GET["opcion"]) and $_GET["opcion"] == "ganado")
                {
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
                        if (@Auth::Check($token) !== null and @Auth::Check($token)) {
                            if (ValidarToken($token, $bd)) {

                                $resultado = array();

                                if (isset($_GET["id"])) {

                                    $id = $_GET["id"];

                                    $resultado = InfoGanadoById($id,$bd);

                                    echo json_encode($resultado);

                                    return;
                                }

                                $sql = "SELECT 
                                    aa.Id,
                                    aa.Codigo,
                                    aa.Nombre,
                                    aa.FechaDeNacimiento,
                                    aa.Sexo,
                                    aa.Color,
                                    aa.Descripcion,
                                    (select Codigo from animal where Id = aa.MadreId) CodigoMadre,
                                    (select Nombre from animal where Id = aa.MadreId) NombreMadre,
                                    (select Codigo from animal where Id = aa.PadreId) CodigoPadre,
                                    (select Nombre from animal where Id = aa.PadreId) NombrePadre,
                                    (select FechaHora from seguimiento where AnimalId = aa.Id order by FechaHora desc, Id desc limit 1) FechaSeguimiento,
                                    (select se.Nombre Estado from seguimiento s inner join seguimiento_estado se on s.EstadoId = se.Id where s.AnimalId = aa.Id order by s.FechaHora desc, s.Id desc limit 1) Seguimiento,
                                    aa.FechaDeCreacion,
                                    CONCAT(pp.PrimerNombre,' ',pp.PrimerApellido) NombreDeUsuarioCreador,
                                    aa.FechaDeModificacion,
                                    CONCAT(ppa.PrimerNombre,' ',ppa.PrimerApellido) NombreDeUsuarioModificador
                                FROM
                                    animal aa
                                    INNER JOIN usuario uu ON aa.CreadoPorUsuarioId = uu.Id
                                    INNER JOIN persona pp ON uu.PersonaId = pp.Id
                                    LEFT JOIN usuario uua ON aa.ModificadoPorUsuarioId = uua.Id
                                    LEFT JOIN persona ppa ON uua.PersonaId = ppa.Id;";

                                $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                                if (count($resultadoSql)) {
                                    foreach ($resultadoSql as $item) {
                                        $fechaDeNacimiento = null;
                                        $fechaSeguimiento = null;
                                        $fechaDeCreacion = null;
                                        $fechaDeModificacion = null;
                                        if (!empty($item->FechaDeNacimiento))
                                            $fechaDeNacimiento = date("d-m-Y",$item->FechaDeNacimiento);
                                        if (!empty($item->FechaSeguimiento))
                                            $fechaSeguimiento = date("d-m-Y",$item->FechaSeguimiento);
                                        if (!empty($item->FechaDeCreacion))
                                            $fechaDeCreacion = date("d-m-Y",$item->FechaDeCreacion);
                                        if (!empty($item->FechaDeModificacion))
                                            $fechaDeModificacion = date("d-m-Y",$item->FechaDeModificacion);
                                        $resultado[] = array(
                                            'Id' => $item->Id,
                                            'Codigo' => $item->Codigo,
                                            'Nombre' => $item->Nombre,
                                            'FechaDeNacimiento' => $fechaDeNacimiento,
                                            'Sexo' => $item->Sexo,
                                            'Color' => $item->Color,
                                            'Descripcion' => $item->Descripcion,
                                            'CodigoMadre' => $item->CodigoMadre,
                                            'NombreMadre' => $item->NombreMadre,
                                            'CodigoPadre' => $item->CodigoPadre,
                                            'NombrePadre' => $item->NombrePadre,
                                            'FechaDeSeguimiento' => $fechaSeguimiento,
                                            'Seguimiento' => $item->Seguimiento,
                                            'FechaDeCreacion' => $fechaDeCreacion,
                                            'CreadoPor' => $item->NombreDeUsuarioCreador,
                                            'FechaDeModificacion' => $fechaDeModificacion,
                                            'ModificadoPor' => $item->NombreDeUsuarioModificador,
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
                }
                if (isset($_GET["opcion"]) and $_GET["opcion"] == "propietario")
                {
                    if (!isset($_GET["id"]) or empty($_GET["id"]))
                    {
                        header('HTTP/1.1 500 Internal Server Error');
                        return;
                    }

                    if(isset($headers["Authorization"])) 
                    {
                        $token = $headers["Authorization"];
                        $token = trim(str_replace("Bearer"," ",$token));
                        if (empty($token)) {
                            header("HTTP/1.1 401 Unauthorized");
                            return;
                        }
                    }
                    else 
                    {
                        header("HTTP/1.1 401 Unauthorized");
                        return;
                    }

                    $resultado = array();

                    if (@Auth::Check($token) !== null and @Auth::Check($token)) {

                        $sql = "SELECT
                            a.Id,
                            a.Codigo,
                            a.Nombre,
                            a.Sexo,
                            CONCAT(p.PrimerNombre, ' ', p.PrimerApellido) Persona
                        FROM
                            persona p
                            INNER JOIN propietario_animal pa ON p.Id = pa.PersonaId AND p.Id = " . $_GET["id"] . "
                            INNER JOIN animal a ON pa.AnimalId = a.Id;";
                        
                        $resultadoSql = json_decode($bd->ejecutarConsultaJson($sql));
                        if (count($resultadoSql)) {
                            foreach ($resultadoSql as $item) {
                                $resultado[] = array(
                                    'Id' => $item->Id,
                                    'Codigo' => $item->Codigo,
                                    'Nombre' => $item->Nombre,
                                    'Sexo' => $item->Sexo,
                                    'Persona' => $item->Persona
                                );
                            }
                        }
                    }
                    else {
                        header("HTTP/1.1 401 Unauthorized");
                        return;
                    }

                    echo json_encode($resultado);
                    return;
                }
            case "PUT":
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

                if(!property_exists($data,"codigo") or !property_exists($data,"nombre") or 
                !property_exists($data,"fechaDeNacimiento") or !property_exists($data,"sexo") or 
                !property_exists($data,"potreroId") or !property_exists($data,"propietarios") or
                !property_exists($data,"hierros") or !property_exists($data,"madreId") or 
                !property_exists($data,"padreId") or !property_exists($data,"razas"))
                {
                    header('HTTP/1.1 500 Internal Server Error');
                    return;
                }

                $tokenResult = Auth::GetData($token);

                $resultado = ActualizarDatos($data, $tokenResult->id, $bd);

                echo json_encode($resultado);
                return;
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

                if (!isset($_GET["id"])) {
                    header('HTTP/1.1 500 Internal Server Error');
                    return;
                }

                if (!BorrarAnimal($_GET["id"], $bd))
                {
                    header('HTTP/1.1 500 Internal Server Error');
                    return;
                }

                break;
            default:
                header("HTTP/1.0 405 Method Not Allowed");
                break;
        }
    }
    else
        header('HTTP/1.1 500 Internal Server Error');
?>