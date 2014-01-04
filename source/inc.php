<?php
require_once 'v2/functions.php';
require_once 'v2/lib/Data.php';
require_once 'v2/lib/DB.php';
require_once 'v2/lib/File.php';
require_once 'v2/lib/Table.php';
require_once 'v2/lib/Model.php';
require_once 'v2/lib/Collection.php';
	
	$GLOBALS['DB'] = DB::connect('bp_sov-art');
	
	function __autoload($classname) {
		if(is_file(str_replace('.:','',get_include_path())."/v2/classes/". $classname .".php")) {
			$classfile = "v2/classes/". $classname .".php";
		} elseif(is_file(str_replace('.:','',get_include_path())."/v2/classes/". $classname ."s.php")) {
			$classfile = "v2/classes/". $classname ."s.php";
		}
		if(empty($classfile)) throw new ErrorException("Class ".$classname." not found!");
		include_once $classfile;
	}
	
	function exception_error_handler($errno, $errstr, $errfile, $errline ) {
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	}
//	set_error_handler("exception_error_handler");
?>