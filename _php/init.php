<?php
require_once __DIR__ . "/headers.php";

define('ROOT_DIR', __DIR__);
define('PHP_DIR', __DIR__);
define('KERNEL_DIR', __DIR__ . "/kernel");

require_once PHP_DIR . "/config.php";

require_once KERNEL_DIR . "/AppException.php";
require_once KERNEL_DIR . "/Log.php";

require_once KERNEL_DIR . "/functions.php";
require_once KERNEL_DIR . "/traits.php";

require_once KERNEL_DIR . "/base/SystemException.php";
require_once KERNEL_DIR . "/base/CaseInsensitiveArray.php";
require_once KERNEL_DIR . "/base/Component.php";
require_once KERNEL_DIR . "/base/Model.php";
require_once KERNEL_DIR . "/base/Collection.php";

require_once KERNEL_DIR . "/DB/Schema.php";
require_once KERNEL_DIR . "/DB/traitTable.php";
require_once KERNEL_DIR . "/DB/MySql/Schema.php";
require_once KERNEL_DIR . "/DB/MySql/Table.php";
require_once KERNEL_DIR . "/DB/SQLite/Schema.php";
require_once KERNEL_DIR . "/DB/SQLite/Table.php";

require_once KERNEL_DIR . "/http/Request.php";
require_once KERNEL_DIR . "/http/Response.php";

require_once ROOT_DIR . "/models/Asteroid.php";
require_once ROOT_DIR . "/collections/Asteroids.php";
require_once ROOT_DIR . "/models/Photo.php";
require_once ROOT_DIR . "/collections/Photos.php";
require_once ROOT_DIR . "/models/Post.php";
require_once ROOT_DIR . "/collections/Posts.php";

?>