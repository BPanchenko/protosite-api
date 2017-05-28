<?php
namespace DB\SQLite;
require_once dirname(__FILE__) . '/../Schema.php';

class Schema extends \DB\Schema {

  public $columnTypes = [
    'pk' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
    'bigpk' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
    'string' => 'varchar(255)',
    'text' => 'text',
    'integer' => 'integer',
    'bigint' => 'bigint(20)',
    'float' => 'float',
    'decimal' => 'decimal',
    'datetime' => 'datetime',
    'timestamp' => 'timestamp',
    'time' => 'time',
    'date' => 'date',
    'binary' => 'blob',
    'boolean' => 'tinyint(1)',
    'money' => 'decimal(19,4)'
  ];

  function __construct($schema = 'memory', $dir='/') {
    $this->_schema = $schema;
    $dsn = 'sqlite:';
    $dir = '/'.trim($dir, '/').'/';
    $dsn .= ($schema == 'memory') ? ":memory:" : DB_STORAGE_DIR . $dir . $schema . ".sqlite";

    try {
      parent::__construct($dsn);
      $this->exec('PRAGMA journal_mode=WAL;');
      $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
      echo 'Connection failed: '.$e->getMessage();
    }
  }

  public function begin() {
    $this->exec("BEGIN;");
    return $this;
  }
  public function commit() {
    $this->exec("COMMIT;");
    return $this;
  }

  public function createTable($name, array $columns, $dropIsExists=false) {
    return parent::createTable($name, $columns, NULL, $dropIsExists);
  }

  /**
   * Returns all table names in the database.
   * @return array all table names in the database.
   */
  public function tableNames() {
    if(empty($this->_tableNames)) {
      $sql = "SELECT DISTINCT tbl_name FROM sqlite_master WHERE tbl_name<>'sqlite_sequence'";
      $this->_tableNames = $this->query($sql)->fetchAll(\PDO::FETCH_COLUMN, 0);
    }
    return $this->_tableNames;
  }
}
?>