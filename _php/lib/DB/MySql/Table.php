<?php
namespace DB\MySql;
require_once dirname(__FILE__) . '/Schema.php';

class Table extends \DB\MySql\Schema {
  use \DB\traitTable;

  protected $_dns;
  protected $_name;
  protected $_schema;
  protected $_fullname;

  protected $_columns;
  protected $_default_query;
  protected $_primary_key;

  function __construct($table, $user, $pass) {
    $this->_dns = str_replace('`', '', $table);
    $temp = explode('.', str_replace('mysql:', '', $this->_dns));

    if(strpos($this->_dns, 'mysql:') !== 0 || !count($temp)) {
      throw new SystemException('WrongMySqlTableName');
    }

    $this->_schema = count($temp) == 2 ? $temp[0] : DB_NAME;
    $this->_name = count($temp) == 2 ? $temp[1] : $temp[0];
    $this->_fullname = '`' . $this->_schema . '`.`' . $this->_name . '`';

    parent::__construct($this->_schema, $user, $pass);

    $this->_default_query = array(
      'from' => $this->_name
    );

    $this->columns();
  }

  /****/
  public function columns() {
    if($this->_columns) return $this->_columns;

    // fetch the table structure
    $_sql = "SHOW COLUMNS FROM `".$this->name()."` \n";
    $_sth = $this->query($_sql);
    $_sth->bindParam(":tbl_name", $this->name());
    $_sth->execute();

    while($_row = $_sth->fetch(\PDO::FETCH_OBJ)) {
      $_name = $_row->Field;
      $_type = strtolower($_row->Key) == 'pri' ? 'pk' : NULL;

      if(!$_type)
        foreach ($this->columnTypes as $columnType=>$needle) {
          $_type = $_row->Type == $needle ? $columnType : NULL;
          if($_type) break;
        }

      $this->_columns[$_name]['_inst'] = $_row;
      $this->_columns[$_name]['type'] = $_type;

      if($_type == 'pk') $this->_setPrimaryKey($_name);
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
    while($a<10000 && $this->query("select COUNT(*) as `count` from ".$this->_name." where ".$column."='".$value."'")->fetchColumn()) {
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

  /****/
  public function primaryKey() {
    return $this->_primary_key;
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

  /* SQL-Constructor */

  /****/
  public function fetch() {
    $this->from($this->_name);
    return parent::fetch();
  }

  /****/
  public function fetchColumn() {
    $this->from($this->_name);
    return parent::fetchColumn();
  }

  /****/
  public function fetchAll($fetch_style = \PDO::FETCH_OBJ): array {
    $this->from($this->_name);
    return parent::fetchAll($fetch_style);
  }

  /****/
  public function truncate() {
    $this->exec("truncate table ".$this->_name);
    return true;
  }

  /****/
  public function update(array $columns = array(), $conditions='', array $params=array()): bool {
    return parent::update($this->_name, $columns, $conditions='', $params);
  }

  public function drop() { return $this->dropTable($this->_name); }

  /* </ use php trait ... */
}
?>