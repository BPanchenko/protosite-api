<?
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 0");
header("Cache-Control: public, max-age=0, must-revalidate"); 
header("Expires: " . gmdate('D, d М Y H:i:s', time() - 1800) . " GMT");
header("Content-Type: application/json; charset=utf-8");

define('PHP_DIR', dirname(__FILE__));
define('PHP_LIB_DIR', dirname(__FILE__) . "/_lib");

require_once PHP_DIR . "/_settings.php";
require_once PHP_DIR . "/_lib/functions.php";

require_once PHP_LIB_DIR . "/system/SystemException.php";

require_once PHP_LIB_DIR . "/base/CaseInsensitiveArray.php";
require_once PHP_LIB_DIR . "/base/Component.php";
require_once PHP_LIB_DIR . "/base/Collection.php";
require_once PHP_LIB_DIR . "/base/Model.php";

require_once PHP_LIB_DIR . "/DB/Schema.php";
require_once PHP_LIB_DIR . "/DB/MySql/Schema.php";
require_once PHP_LIB_DIR . "/DB/SQLite/Schema.php";
require_once PHP_LIB_DIR . "/DB/Table.php";
require_once PHP_LIB_DIR . "/DB/MySql/Table.php";
require_once PHP_LIB_DIR . "/DB/SQLite/Table.php";

require_once PHP_DIR . "/app/AppException.php";

require_once PHP_LIB_DIR . "/http/Request.php";
require_once PHP_LIB_DIR . "/http/Response.php";

require_once PHP_DIR . "/models/Profile.php";

require_once PHP_DIR . "/collections/Profiles.php";
?>