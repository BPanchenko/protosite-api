<?php
date_default_timezone_set('UTC');

define('DB_NAME', 'bp_api');
define('DB_ROOT_HOST', 'bp.mysql');
define('DB_ROOT_USER', 'bp_api');
define('DB_ROOT_PASS', 'nua-B4ZA');
define('DB_STORAGE_DIR', '/home/bp/api.protosite.rocks/docs/_php/storage');

define('API_ROOT', '/');
define('LOG_DIR', '/home/bp/api.protosite.rocks/docs/_php/logs');
define('PHOTOS_UPLOAD_DIR', '/home/bp/api.protosite.rocks/docs/upload/photos/temp');
define('PHOTOS_PATH', 'http://api.protosite.rocks/upload/photos');
define('STATIC_JSON_DIR', '/home/bp/api.protosite.rocks/docs/static');

define('FETCH_DEFAULT_COUNT', 200);
define('FETCH_DEFAULT_OFFSET', 0);
?>