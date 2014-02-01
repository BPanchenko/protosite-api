<?php
	
	// build params
	$request = trim($_GET['request'],'/');
	$router_pattern = "/^[\/]?([a-z]+)[\/]?([a-zA-Z0-9-]*)[\/]?$/";
	if(!preg_match($router_pattern, $request, $matches)) die($request);
	unset($_GET['request']);
	
	$class_name = ucfirst($matches[1]);
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
		
		try{
			$Item = $Collection->create($_POST)->save()->fetch();
			
			if(is_array($_POST['photo_ids'])) {
				$Photos = new Photos();
				foreach($_POST['photo_ids'] as $_key=>$_id) {
					$Photo = $Photos->create($_id)
									->fetch()->moveTo($Collection->folder())
												->rename($Item->get('name'))
												->save();
					$attached_info = array();
					if(isset($_POST['photo_is_mark'][$_key])) {
						$attached_info['is_mark'] = $_POST['photo_is_mark'][$_key];
						$Photo->set('is_mark', $_POST['photo_is_mark'][$_key]);
					}
					if(isset($_POST['photo_descriptions'][$_key])) {
						$attached_info['description'] = $_POST['photo_descriptions'][$_key];
						$Photo->set('description', $_POST['photo_descriptions'][$_key]);
					}
					$Photo->attached($Item, $attached_info);
					$Photo->addTo($Photos);
				}
				
				if(isset($_POST['photo_is_mark'])) {
					$Item->set('photos',$Photos->where(array(
						'is_mark' => 0
					)));
					$Item->set('marks',$Photos->where(array(
						'is_mark' => 1
					)));
				} else {
					$Item->set('photos',$Photos);
				}
				$Item->remove('photo_ids,photo_descriptions,photo_is_mark');
			}
			
			
			// TODO: доделать поля feilds
//			$Response->data = $Item->toArray('name,name_translit,sort');
			$Response->data = $Item->toArray();
			echo_response($Response);
		} catch(Exception $e) {
			$Response->meta->error_type = $e->getMessage();
			if($e->getMessage()=="CreateProductFiled") {
				$Response->meta->error_attribute = "all";
				$Response->meta->error_code = 401;
				$Response->meta->error_message->en = "Failed create entity";
				$Response->meta->error_message->ru = "Ошибка создания сущности";
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
	$method_name = strtolower($_SERVER['REQUEST_METHOD'].'_'.str_replace('-','_',$matches[2]));
	if(method_exists($Collection, $method_name)) {
		$REQUEST_DATA = $_GLOBAL['_'.strtoupper($_SERVER['REQUEST_METHOD'])];
		if(empty($REQUEST_DATA)) $REQUEST_DATA = $_GET;
		
		// Additional Data
		if($Collection instanceof Products && $method_name=='get_reference_data') {
			if(isset($AuthUser) && in_array($AuthUser->get('access_permissions'),array('unlimited','admin','editor'))) {
				//                                     для адмнистраторов сайта добавляются данные о владельцах предметов|
				$REQUEST_DATA['reference_fields'] = 'authors,categories,countries,factories,materials,shops,technologies,owners';
				$cache_file = $_SERVER['DOCUMENT_ROOT']."/api/_cache/".strtolower(get_class($Collection)).'.'.$method_name."(".$REQUEST_DATA['reference_fields'].").json";
			}
		}
		
		if(empty($matches[3])) {
			if(empty($cache_file)) $cache_file = $_SERVER['DOCUMENT_ROOT']."/api/_cache/".strtolower(get_class($Collection)).'.'.$method_name.".json";
//			if(file_exists($cache_file) && (time()-filemtime($cache_file) < 3000)){
			if(file_exists($cache_file)){
				$Response->data = json_decode(file_get_contents($cache_file));
			} else {
				$Response->data = call_user_func(array($Collection, $method_name), $REQUEST_DATA);
				$cache_handle = fopen($cache_file,'w');
				fwrite($cache_handle, json_encode($Response->data));
				fclose($cache_handle);
			}
		} else {
			$Response->data = call_user_func(array($Collection, $method_name), $matches[3], $REQUEST_DATA);
		}
		echo_response($Response);
	}
	
	if(!is_numeric($matches[2])) echo_response($Response);
	
	// Requested Item
	$Item = $Collection->create(array( 'id' => (int)$matches[2] ));
	$Item->fetch();
	
	if($Item instanceof Page) {
		if($_SERVER['REQUEST_METHOD'] == 'PUT') $Item->setHtml($_PUT['html']);
		if($_SERVER['REQUEST_METHOD'] == 'GET') $Item->setHtml();
	}
	
	if($_SERVER['REQUEST_METHOD'] == 'PUT') {
		$Item->save($_PUT);
		pa($Item->changed);
		
		if(empty($_PUT['photo_ids'])) {
			$Item->removeLinks('photo_id')->remove('photos,marks');
			
		} else {
			if(!is_array($_PUT['photo_ids'])) throw new Exception("Wrong type _PUT['photo_ids']");
			
			// удаление ссылок, которые не были переданы
			$link_ids = $Item->getIdLinks('photo_id');
			foreach($link_ids as $id) {
				if(!in_array($id, $_PUT['photo_ids'])) $Item->removeLink('photo_id', $id);
			}
			
			$Photos = new Photos();
			foreach($_PUT['photo_ids'] as $_key=>$_id) if($_id) {
				$Photo = $Photos->create($_id)->fetch();
				if(!$Item->hasLink(array( 'photo_id'=>$Photo->id ))) {
					//        |вызывать только в такой последовательности|
					$Photo->rename($Item->get('name'))->moveTo($Collection->folder())->save();
				}
				$attached_info = array();
				if(isset($_PUT['photo_is_mark'][$_key])) {
					$attached_info['is_mark'] = $_PUT['photo_is_mark'][$_key];
					$Photo->set('is_mark', $_PUT['photo_is_mark'][$_key]);
				}
				if(isset($_PUT['photo_descriptions'][$_key])) {
					$attached_info['description'] = $_PUT['photo_descriptions'][$_key];
					$Photo->set('description', $_PUT['photo_descriptions'][$_key]);
				}
				$Photo->attached($Item, $attached_info);
				$Photo->addTo($Photos);
			}
			
			if(isset($_PUT['photo_is_mark'])) {
				$Item->remove('photos')->set('photos',$Photos->where(array(
					'is_mark' => 0
				)));
				$Item->remove('marks')->set('marks',$Photos->where(array(
					'is_mark' => 1
				)));
			} else {
				$Item->remove('photos')->set('photos',$Photos);
			}
			$Item->remove('photo_ids,photo_descriptions,photo_is_mark');
		}
			
		
	} elseif($_SERVER['REQUEST_METHOD'] == 'DELETE') {
		$Response->data = $Item->delete();
		echo_response($Response);
	}
	$Response->data = $Item->toArray($_GET['fields']);
	
	echo_response($Response);
?>