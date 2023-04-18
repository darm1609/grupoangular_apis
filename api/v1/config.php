<?php	
	global $servidor;
	global $puerto;
	global $usuario;
	global $pass;
	global $basedatos;
	global $clienteId;
	global $rutaDeAdjuntos;
	$servidor='localhost';
	$puerto='3306';
	$usuario='root';
	$pass='123456';
	$basedatos='vinkasof_santafe';
	$clienteId=1;
	$rutaDeAdjuntos="adjuntos/";
	ini_set("display_errors","On");
	//date_default_timezone_set("America/Bogota");
	date_default_timezone_set("UTC");
	
	header('Access-Control-Allow-Origin: *');
	header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, X-Auth-Token, Accept, Access-Control-Request-Method, Authorization");
	header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
?>