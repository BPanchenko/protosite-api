<?php
require_once 'source/functions.php';
require_once 'source/lib/Data.php';
require_once 'source/lib/DB.php';
require_once 'source/lib/File.php';
require_once 'source/lib/Table.php';
require_once 'source/lib/Model.php';
require_once 'source/lib/Collection.php';

	$GLOBALS['DB'] = DB::connect('dbname');
	
	// build params
	$request = trim($_GET['request'],'/');
	$router_pattern = "/^[\/]?([a-z]+)[\/]?([a-z0-9-]*)[\/]?$/";
	if(!preg_match($router_pattern, $request, $matches)) die($request);
	unset($_GET['request']);
	
	$class_name = str_replace(array('News'),array('NewsBriefs'),ucfirst($matches[1]));
	$class = $class_name;
	$Collection = new $class();
	
	if(!$matches[2] && $_SERVER['REQUEST_METHOD'] == 'POST') {
		$options = array();
		if(!isset($AuthUser) || !in_array($AuthUser->get('access_permissions'),array('unlimited','admin','editor'))) {
			$Response->meta->code = 400;
			$Response->meta->error_code = 102;
			$Response->meta->error_type = "AccessDenied";
			$Response->meta->error_message->en = "To execute the query is not enough access rights";
			$response->meta->error_message->ru = "Не хватает прав для создания сущности";
			echo_response($Response);
		}
		if(!empty($_POST['notice'])) $_POST['notice'] = nl2br($_POST['notice']);
		
		try{
			$Response->data = $Collection->create($_POST)->save()->fetch()->toArray();
			echo_response($Response);
		} catch(Exception $e) {
			$Response->meta->error_type = $e->getMessage();
			if($e->getMessage()=="CreateProductFiled") {
				$Response->meta->error_attribute = "all";
				$Response->meta->error_code = 401;
				$Response->meta->error_message->en = "Failed create entity";
				$Response->meta->error_message->ru = "Создание сущности окончилось неудачей";
			}
			echo_response($Response);
		}
	}
	
	if(!$matches[2]) {
	// Requests the list of items
		$Collection->fetch($_GET);
		$Response->data = $Collection->toArray($_GET['fields']);
		echo_response($Response);
	}
	
	// Execute method with GET params
	if(method_exists($Collection,$matches[2])) $Response->data = call_user_func(array($Collection, $matches[2]), $GET);
	
	if(!is_numeric($matches[2])) echo_response($Response);
	// Requested item
	$Item = $Collection->create(array( 'id' => (int)$matches[2] ));
	$Item->fetch();
	
	if($Item instanceof Page) {
		if($_SERVER['REQUEST_METHOD'] == 'PUT') $Item->html($_PUT['html']);
		if($_SERVER['REQUEST_METHOD'] == 'GET') $Item->html();
	}
	
	if($_SERVER['REQUEST_METHOD'] == 'PUT') {
		$Item->save($_PUT);
	} elseif($_SERVER['REQUEST_METHOD'] == 'DELETE') {
		$Response->data = $Item->delete();
		echo_response($Response);
	}
	$Response->data = $Item->toArray($_GET['fields']);
	
	echo_response($Response);
?>