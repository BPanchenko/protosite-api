<?php

$Log = new system\Log;
$Request = http\Request::init();
$Response = http\Response::init();
$start_ts = microtime(true);

if($Request->method == 'OPTIONS' || $Request->uri() == '/favicon.ico') {
	$Response->setStatusCode(200, 'OK');
	$Response->sendHeaders();
	exit();
}

$Log->write($Request->ip() . "\t" . $Request->uri() . "\n");
$lastRequests = $Log->read();

// calc the count of requests for the period
$for_last5seconds = time() - 5;
$cnt_from_ip = 0;
$for_last20seconds = time() - 20;
$cnt_all_requests = 0;
foreach($lastRequests as $ts => $item) {
    $ts = (float)$ts;
    if($ts > $for_last5seconds && $Request->ip() == $item[0]) $cnt_from_ip++;
    if($ts > $for_last20seconds) $cnt_all_requests++; else break;
}

// exit if
if ($cnt_from_ip > 20 || $cnt_all_requests > 400) {
    $Response->setStatusCode(429);
    $Response->get('meta')->error_message = 'Too Many Requests';

    if($cnt_from_ip > 20)
        $Response->setHeader('Retry-After', 5);
    if($cnt_all_requests > 400)
        $Response->setHeader('Retry-After', 20);

    $Response->send();
    exit();
}

if(isset($_GET['debug'])) {
    echo "\n\n//*********************************";
    echo "\n// DATE\n";
    echo date("c");
}

if(isset($_GET['debug'])) {
    echo "\n\n//*********************************";
    echo "\n// LIST OF THE LAST REQUESTS\n";
    var_dump($lastRequests);
}

if(isset($_GET['fields'])) {
    $_GET['fields'] = explode(',', $_GET['fields']);
}

if($Request->parameters()->has('access_token'))
    $AuthUser = new User(array(
        'access_token' => $Request->parameters('access_token')
    ));

try {
    $points = $Request->parts();

    if(isset($_GET['debug'])) {
        echo "\n\n//*********************************";
        echo "\n// POINTS OF THE REQUEST\n";
        var_dump($points);
    }


    foreach($points as $i=>$part) {
        if($points[$i-1]->type === 'method') {
            $Request->parameters('__uri__', array($part->value));
            unset($points[$i]);
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
                if($_prev->type === 'object')
                    $part->instance->attachTo($_prev->instance);

                break;

            case 'string':
                $method_name = strtolower($Request->method) . '_' . camelize($part->value);
                if($points[$i-1]->type !== 'object' || !method_exists($points[$i-1]->instance, $method_name)) {
                    throw new AppException('MethodNotAllowed');
                }
                $part->type = 'method';
                $part->value = $method_name;
                $part->instance = $points[$i-1]->instance;
                break;

            case 'int':

                if($points[$i-1]->type !== 'object') {
                    throw new AppException('ParentIsNotExists');
                }
                if(!($points[$i-1]->instance instanceof \base\Collection)) {
                    throw new AppException('ParentIsNotCollection');
                }

                $_prev = $points[$i-1];
                $part->type = 'object';

                if(isset($_GET['debug']))
                    var_dump(get_class($_prev->instance) . ' -> create()');

                $part->instance = $_prev->instance->create($part->value);
                break;
        }
    }
    $endpoint = end($points);

    /*
    if(isset($_GET['debug'])) {
        echo "\n\n//*********************************";
        echo "\n// ENDPOINT\n";
        var_dump("@class " . get_class($endpoint));
    }
    */

    // close endpoint
    if($endpoint->type == 'method') {
        $Response->get('meta')->code = 200;

        // go method
		$requestQuery = $Request->parameters()->toArray();
        $requestBody = $Request->getBody();
        $Response->set('data',
			call_user_func(
				array(&$endpoint->instance, $endpoint->value),
				$requestBody, $requestQuery
			)
		);
    }

    if($endpoint->type == 'object') {

        if (!$endpoint->instance->isValid()) {
            $Response->setStatusCode(422, 'Unprocessable Entity');
            $Response->sendHeaders();
            exit();
        }

        $Response->setStatusCode(200, 'OK');

        if(isset($_GET['debug'])) {
            print_r("// Request->parameters()->toArray" . "\n");
            var_dump($Request->parameters()->toArray());
        }

        if ($endpoint->instance instanceof \base\Collection &&
            $Request->method == 'POST'
        ) {
            $Response->setStatusCode(201, 'Created');
			$model = $endpoint->instance->create($Request->getBody());
			$model->save()->fetch();
			$endpoint->instance = $model;

        } elseif (array_search($Request->method, ['PATH', 'PUT']) !== false) {
            $Response->setStatusCode(202, 'Accepted');
            $endpoint->instance->save($Request->getBody())->fetch();

        } elseif ($Request->method == 'DELETE' || $Request->method == 'OPTIONS') {
            $Response->setStatusCode(204, 'No Content');
            $Response->sendHeaders();
            exit();
        } else {
			$endpoint->instance->fetch($Request->parameters()->toArray());
        }

        if($Response->is_empty('data'))
            $endpoint->instance->prepareResponse($Response);
    }

} catch (AppException $e) {
    $Response->setStatusCode($e->code());
    $Response->get('meta')->error_message = $e->message();

} catch (SystemException $e) {
    // 500-е ошибки
    $Response->setStatusCode(500, 'Internal Server Error');
    $Response->get('meta')->code = $e->getCode();
    $Response->get('meta')->error_type = $e->getType();
    $Response->get('meta')->error_message = $e->getMessage();

} catch (PDOException $e) {
    $Response->setStatusCode(500, 'PDOException');
    $Response->get('meta')->code = '500.' . $e->getCode();
    $Response->get('meta')->error_type = 'PDOException';
    $Response->get('meta')->error_message = $e->getMessage();

} catch (Exception $e) {
    $Response->get('meta')->code = 400;
    $Response->setStatusCode(400, 'Bad Request');
    $Response->get('meta')->error_message = $e->getMessage();
}

$Response->send();

if(isset($_GET['debug'])) {
    echo "\n\n//*********************************";
    echo "\n// MEMORY USAGE\n";
    echo memory_get_usage();

    echo "\n\n//*********************************";
    echo "\n// EXECUTION TIME\n";
    echo (microtime(true) - $start_ts).'s';
}
?>