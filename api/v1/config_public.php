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
	$usuario='vinkasof_santafe_user';
	$pass='qPt]WEN{0~#p';
	$basedatos='vinkasof_santafe';
	$clienteId=1;
	$rutaDeAdjuntos="adjuntos/";
	ini_set("display_errors","Off");
	date_default_timezone_set("UTC");
	header('Access-Control-Allow-Origin: *');
	header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, X-Auth-Token, Accept, Access-Control-Request-Method, Authorization");
	header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
?>