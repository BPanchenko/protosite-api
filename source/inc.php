<?php
require_once 'lib/Api.php';
require_once 'lib/ApiException.php';
require_once 'lib/Data.php';
require_once 'lib/DB_MySQL.php';
require_once 'lib/DB_SQLite.php';
require_once 'lib/File.php';
require_once 'lib/Table.php';
require_once 'lib/Model.php';
require_once 'lib/Collection.php';

	
	DB_MySQL::connect('bp_sov-art');
//	$GLOBALS['DB_SQLite'] = DB_SQLite::connect('storage.sqlite');
	
	function __autoload($classname) {
		$filename = ucfirst($classname);
		$map_classes = array(
			"Factory" => "Factories",
			"User" => "Users"
		);
		if(array_key_exists($filename, $map_classes)) {
			$filename = $map_classes[$filename];
		}
		
		$dir = "classes/";
		$path = str_replace('.:','',get_include_path())."/";
		if(is_file($path . $dir . $filename .".php")) {
			$file = $path . $dir . $filename .".php";
		}
		if(!empty($file)) include_once $file;
	}
	
	function exception_error_handler($errno, $errstr, $errfile, $errline ) {
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	}
//	set_error_handler("exception_error_handler");
?>