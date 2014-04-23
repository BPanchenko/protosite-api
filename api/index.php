<?php
require_once 'headers.php';
require_once 'inc.php';
	
	try {
		Api::init();
		
		// the initialization of an object based on the checkpoints of the request
		$obj = NULL;
		foreach(Api::$Request->points as $point) {
			switch($point['type']) {
				case 'class':
					if($obj) {
						$obj = $obj->append(new $point['val'])->child;
					} else {
						$obj = new $point['val'];
					}
				break;
				case 'id':
					if(!$obj) throw new ApiException('IdForAnUndefinedObject', $point);
					$obj = $obj->create($point['val']);
				break;
				case 'method':
					if(!$obj) throw new ApiException('MethodForAnUndefinedObject', $point);
					if(!method_exists($obj, $point['val'])) throw new ApiException('MethodNotExists', $point);
					Api::$Response->data = call_user_func(array($obj, $point['val']), Api::$Request->data);
					goto end;
				break;
				default: throw new ApiException('UnknowCheckpoint', $point);
			}
		}
		
		switch(Api::$Request->method) {
			case 'get':
				Api::$Response->data = $obj->fetch(Api::$Request->data)->toArray(Api::$Request->data['field']);
			break;
			case 'post':
				if($obj instanceof Collection) {
					$obj = $obj->create(Api::$Request->data);
				} elseif($obj instanceof Model) {
					$obj->set(Api::$Request->data);
				}
				Api::$Response->data = $obj->save()->fetch()->toArray(Api::$Request->data['field']);
			break;
			case 'put':
				if(!($obj instanceof Model)) throw new ApiException('ChangeNotModel', $point);
				Api::$Response->data = $obj->set(Api::$Request->data)->save()->fetch()->toArray(Api::$Request->data['field']);
			break;
			case 'delete':
				if(!($obj instanceof Model) || $obj->isNew()) throw new ApiException('ChangeNotModel', $point);
				Api::$Response->data = $obj->delete();
			break;
		}
		
		
	} catch (ApiException $e) {
		Api::$Response->meta = $e->toArray();
	}
	
	end:
	Api::issue();
?>