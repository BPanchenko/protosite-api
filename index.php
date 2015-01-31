<?
	$Request = http\Request::init();
	$Response = http\Response::init();
	print date("Y-m-d HH:mm", 1421442000);
	if($Request->parameters()->has('access_token'))
		$AuthUser = new User(array(
			'access_token' => $Request->parameters('access_token')
		));
	
	try {
		$points = $Request->parts();
		foreach($points as $i=>$part) {
			if($points[$i-1]->type === 'method') {
				$Request->parameters('__uri__')->push($part->value);
				continue;
			}
			
			switch($part->type) {
				case 'self':
					if(is_null($AuthUser)) {
						throw new AppException('AccessTokenRequired');
					}
					$part->type = 'object';
					$part->instance = new $AuthUser;
				break;
				
				case 'class':
					$classname = $part->value;
					$part->type = 'object';
					$part->instance = new $classname;
					
					$_prev = $points[$i-1];
					if($_prev->type === 'object') {
						$part->instance->attachTo($_prev->instance);
					}
				break;
				
				case 'string':
					if($points[$i-1]->type !== 'object' || !method_exists($points[$i-1]->instance, $Request->method . '__' . $part->value)) {
						throw new AppException('MethodNotExist');
					}
					$part->type = 'method';
					$part->value = $Request->method . '__' . $part->value;
					$part->instance = $points[$i-1]->instance;
				break;
				
				case 'int':
					
					if($points[$i-1]->type !== 'object') {
						throw new AppException('ParentObjectIsNotExists');
					}
					if(!($points[$i-1]->instance instanceof \base\Collection)) {
						throw new AppException('ParentObjectIsNotCollection');
					}
					
					$part->type = 'object';
					$part->instance = $points[$i-1]->instance->create($part->value);
				break;
			}
		}
		$endpoint = end($points);
		
		// close endpoint
		if($endpoint->type == 'method') {
			$Response->get('meta')->code = 200;
			$Response->set('data', call_user_func(array(&$endpoint->instance, $endpoint->value)));
		}
		
		if($endpoint->type === 'object')
			$Response->set('data', $endpoint->fetch()->toJSON());
		
	} catch (AppException $e) {
		$Response->setStatusCode($e->code(), $e->type);
		$Response->get('meta')->error_message = $e->type;
		
	} catch (PDOException $e) {
		$Response->get('meta')->code = '400.' . $e->getCode();
		$Response->get('meta')->error_type = 'PDOException';
		$Response->get('meta')->error_message = $e->getMessage();
		
	} catch (Exception $e) {
		$Response->get('meta')->code = 400;
		$Response->get('meta')->error_message = $e->getMessage();
	}
	
	$Response->send();
?>