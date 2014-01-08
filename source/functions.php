<?php
function pa($array){
	print "<pre>";
	print_r ($array);
	print "</pre>";
}

function is_valid_callback($subject) {
    $identifier_syntax = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';
    $reserved_words = array('break', 'do', 'instanceof', 'typeof', 'case',
							'else', 'new', 'var', 'catch', 'finally', 'return', 'void', 'continue',
							'for', 'switch', 'while', 'debugger', 'function', 'this', 'with',
							'default', 'if', 'throw', 'delete', 'in', 'try', 'class', 'enum',
							'extends', 'super', 'const', 'export', 'import', 'implements', 'let',
							'private', 'public', 'yield', 'interface', 'package', 'protected',
							'static', 'null', 'true', 'false');
	  
    return preg_match($identifier_syntax, $subject) && !in_array(mb_strtolower($subject, 'UTF-8'), $reserved_words);
}

function echo_response($Response){
	$redirect_uri = $_GET['redirect_uri'] ? $_GET['redirect_uri'] : $_POST['redirect_uri'] ? $_POST['redirect_uri'] : NULL;
	if($redirect_uri) header('Location: '.$redirect_uri);
	
	$callback = $_GET["callback"];
	
	if($Response->data!==NULL) {
		$Response->meta->code=200;
		unset($Response->meta->error_type);
		unset($Response->meta->error_message);
		unset($Response->meta->request);
	}
	
	if($Response->meta->code==400 && !isset($Response->meta->error_type)) $Response->meta->error_type = "APINotFoundError";
	if($Response->meta->code==400 && !isset($Response->meta->error_message)) {
		$Response->meta->error_message->en = "You cannot run the query";
		$Response->meta->error_message->ru = "Невозможно выполнить запрос";
	}
	
	if (is_object($Response) && is_valid_callback($callback)) {
		header("Content-type: text/javascript; charset=utf-8");
		die($callback.'('.json_encode($Response).')');
	} elseif(is_object($Response)) {
		header("Content-Type: application/json; charset=utf-8");
		die(json_encode($Response));
	} else {
		header("Content-Type: text/html; charset=utf-8");
		die($Response);
	}
}

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
?>