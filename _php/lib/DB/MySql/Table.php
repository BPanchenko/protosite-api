<?php
namespace DB\MySql;
require_once dirname(__FILE__) . '/Schema.php';

class Table {
  use \DB\traitTable;

  protected $_dns;
  protected $_name;
  protected $_schema;
  protected $_fullname;

  protected $_columns;
  protected $_default_query;
  protected $_primary_key;

  private $_dbh;

  function __construct(string $table, string $user = '', string $pass = '') {
    $this->_dns = str_replace('`', '', $table);
    $temp = explode('.', str_replace('mysql:', '', $this->_dns));

    if(strpos($this->_dns, 'mysql:') !== 0 || !count($temp)) {
      throw new SystemException('WrongMySqlTableName');
    }

    $this->_schema = count($temp) == 2 ? $temp[0] : DB_NAME;
    $this->_name = count($temp) == 2 ? $temp[1] : $temp[0];
    $this->_fullname = '`' . $this->_schema . '`.`' . $this->_name . '`';

    $this->_dbh = new \DB\MySql\Schema($this->_schema, $user, $pass);

    $this->_default_query = array(
      'from' => $this->_name
    );

    $this->setTimeZone('+00:00')->columns();
  }

  /****/
  public function columns() {
    if($this->_columns) return $this->_columns;

    // fetch the table structure
    $_sql = "SHOW FULL COLUMNS FROM `".$this->name()."`;";
    $_sth = $this->_dbh->query($_sql);

    while($_row = $_sth->fetch(\PDO::FETCH_OBJ)) {
      $_name = $_row->Field;
      $_is_primary_key = strtolower($_row->Key) == 'pri';
      
      $this->_columns[$_name]['comment'] = $_row->Comment;
      $this->_columns[$_name]['is_primary'] = $_is_primary_key;
      $this->_columns[$_name]['type'] = $_row->Type;
      $this->_columns[$_name]['short_type'] = $this->_getShortFieldType($_row->Type);

      if($_is_primary_key) $this->_setPrimaryKey($_name);
    }

    return $this->_columns;
  }

  /* < TODO: use php trait ... */

  /****/
  public function getUniqValue($column, $value) {
    // sequence number in the value
    if(preg_match("/\((\d+)\)$/", $value, $matches)) $a = $matches[1];
    else $a = 0;
    // calc the new value
    while($a<10000 && $this->_dbh->query("select COUNT(*) as `count` from ".$this->_name." where ".$column."='".$value."'")->fetchColumn()) {
      $a++;
      $a==2 ? $value .= "(".$a.")" : $value = str_replace("(".($a-1).")", "(".$a.")", $value);
    }
    return $value;
  }

  /****/
  public function fields() {
    if(!$this->_columns) $this->columns();
    return array_keys($this->_columns);
  }

  /****/
  public function columnType($column_name) {
    if(!$this->_columns) $this->columns();
    return $this->hasColumn($column_name) ? $this->_columns[$column_name]['type'] : NULL;
  }

  public function insert(array $columns): self {
    $this->_dbh->insert($this->_name, $columns);
    return $this;
  }

  public function setTimeZone(string $value): self {
    $_sql = "SET time_zone = '{$value}';";
    $this->_dbh->query($_sql);
    return $this;
  }

  /****/
  private function _setPrimaryKey($column_name) {

    if(is_null($this->_primary_key))
      $this->_primary_key = $column_name;
    elseif(is_array($this->_primary_key))
      array_push($this->_primary_key, $column_name);
    elseif(is_string($this->_primary_key))
      $this->_primary_key = array($this->_primary_key, $column_name);

    return $this->primaryKey();
  }

  public function fetch() {
    $this->_dbh->from($this->_name);
    return $this->_dbh->fetch();
  }

  public function fetchColumn() {
    $this->_dbh->from($this->_name);
    return $this->_dbh->fetchColumn();
  }

  public function fetchAll($fetch_style = \PDO::FETCH_OBJ): array {
    $this->_dbh->from($this->_name);
    return $this->_dbh->fetchAll($fetch_style);
  }

  public function reset(): \DB\Schema {
    return $this->_dbh->reset();
  }

  public function truncate(): bool {
    $this->_dbh->exec("truncate table ".$this->_name);
    return true;
  }

  public function update(array $columns = array(), $conditions='', array $params=array()): bool {
    return $this->_dbh->update($this->_name, $columns, $conditions, $params);
  }

  public function drop() {
    return $this->_dbh->dropTable($this->_name);
  }

}
?>