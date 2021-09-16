<?php
$start_ts = microtime(true);

$Request = http\Request::init();
$Response = http\Response::init();

if($Request->method == 'OPTIONS' || $Request->uri() == '/favicon.ico') {
  $Response->setStatusCode(200, 'OK');
  $Response->sendHeaders();
  exit();
}

$Log = new Log;
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

if(isset($_GET['fields'])) {
  $_GET['fields'] = explode(',', $_GET['fields']);
}

/**
 * Endpoint based on parts of Request URI
 */

$points = $Request->parts();

/**
 * Make a response based on the static json files.
 * Json files from a static directory are used.
 */

if (isset($_GET['fallback']) && $_GET['fallback'] === 'true') {
  $getValue = function ($inst) { return strtolower($inst->value); };
  $filename = STATIC_DIR . '/' . implode('-', array_map($getValue, $points)) . '.json';

  if (is_file($filename)) {
    $Response->get('meta')->code = 200;
    $Response->setStatusCode(200, 'OK');
    $Response->set('data', json_decode(file_get_contents($filename)));
  } else {
    $Response->get('meta')->code = 404;
    $Response->setStatusCode(404, 'Not Found');
    $Response->get('meta')->error_message = 'File not found';
  }

  $Response->send();
  exit();
}

/**
 * Make a response based on data from the database.
 * The response data makes from a collection or model, depending on the endpoint datum.
 */

try {
  $prev = new stdClass();

  foreach($points as &$part) {

    switch($part->type) {

      case 'class':
        $classname = $part->value;
        $part->type = 'object';
        $part->instance = new $classname(NULL, $prev->instance ?? NULL);
        break;

      case 'string':
        $method_name = strtolower($Request->method) . '_' . camelize($part->value);
        
        if(
          !property_exists($prev, 'type')
          || $prev->type !== 'object'
          || !method_exists($prev->instance, $method_name)
        ) {
          throw new AppException('MethodNotAllowed');
        }

        // go method
        $part->value = call_user_func(
            [&$prev->instance, $method_name],
            $Request->parameters()->toArray(),
            $Request->getBody()
        );

        if($part->value instanceof \base\Component) {
          $part->type = 'object';
          $part->instance = $part->value;
        } else {
          $part->type = 'method';
          $part->instance = $prev->instance;
        }

        break;

      case 'int':
        if($prev->type != 'object') {
          throw new AppException('ParentIsNotObject');
        }
        if(!($prev->instance instanceof \base\Collection)) {
          throw new AppException('ParentIsNotCollection');
        }

        $part->type = 'object';
        $part->instance = $prev->instance->create([ 'id' => $part->value ]);

        break;
    }

    $prev = $part;
  }

  $endpoint = end($points);

  // close endpoint
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

    if($Response->is_empty('data')) {
      $endpoint->instance->prepareResponse($Response);
    }
  } else {
    $Response->get('meta')->code = 200;
    $Response->set('data', $endpoint->value);
  }

} catch (AppException $e) {
  $Response->setStatusCode($e->code());
  $Response->set('meta', $e->toArray());

} catch (SystemException $e) {
  // 500-е ошибки
  $Response->setStatusCode(500, 'Internal Server Error');
  $Response->get('meta')->code = $e->getCode();
  $Response->get('meta')->error_type = $e->getType();
  $Response->get('meta')->error_message = $e->getMessage();
  $Response->get('meta')->request_points = $points;

} catch (PDOException $e) {
  $Response->setStatusCode(500, 'PDOException');
  $Response->get('meta')->code = '500.' . $e->getCode();
  $Response->get('meta')->error_type = 'PDOException';
  $Response->get('meta')->error_message = $e->getMessage();
  $Response->get('meta')->request_points = $points;

} catch (Exception $e) {
  $Response->get('meta')->code = 400;
  $Response->setStatusCode(400, 'Bad Request');
  $Response->get('meta')->error_message = $e->getMessage();
}

$Response->send();

$execution_time = round((microtime(true) - $start_ts), 5);
$Log->write($Request->ip() . "\t" . $Request->uri() . "\t" . $execution_time . "\n");

if(isset($_GET['debug'])) {
  echo "\n\n//*********************************";
  echo "\n// POINTS OF THE REQUEST\n";
  var_dump($points);

  echo "\n\n//*********************************";
  echo "\n// DATE\n";
  echo date("c");

  echo "\n\n//*********************************";
  echo "\n// MEMORY USAGE\n";
  echo round(memory_get_usage() / (1024 * 1024), 2) . "Mb";

  echo "\n\n//*********************************";
  echo "\n// EXECUTION TIME\n";
  echo $execution_time . 's';
  
  echo "\n\n//*********************************";
  echo "\n// LIST OF THE LAST REQUESTS\n";
  var_dump($lastRequests);
}

?>