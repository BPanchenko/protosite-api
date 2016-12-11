<?php
define('PHP_DIR', __DIR__);
define('PHP_LIB_DIR', __DIR__ . "/lib");

require_once PHP_DIR . "/config.php";

require_once PHP_LIB_DIR . "/functions.php";
require_once PHP_LIB_DIR . "/traits.php";

require_once PHP_LIB_DIR . "/base/CaseInsensitiveArray.php";
require_once PHP_LIB_DIR . "/base/Component.php";
require_once PHP_LIB_DIR . "/base/Model.php";
require_once PHP_LIB_DIR . "/base/Collection.php";

require_once PHP_LIB_DIR . "/DB/Schema.php";
require_once PHP_LIB_DIR . "/DB/MySql/Schema.php";
require_once PHP_LIB_DIR . "/DB/SQLite/Schema.php";
require_once PHP_LIB_DIR . "/DB/traitTable.php";
require_once PHP_LIB_DIR . "/DB/MySql/Table.php";
require_once PHP_LIB_DIR . "/DB/SQLite/Table.php";

require_once PHP_LIB_DIR . "/http/Request.php";
require_once PHP_LIB_DIR . "/http/Response.php";

?>