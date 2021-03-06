<?php
namespace DB\SQLite;
require_once dirname(__FILE__) . '/Schema.php';

class Table extends \DB\SQLite\Schema {
  use \DB\traitTable;

  protected $_dns;
  protected $_name = '';
  protected $_columns;
  protected $_default_query;
  protected $_primary_key;

  function __construct($dns) {
    $this->_dns = $dns;
    if(preg_match('/^sqlite:([\w]+)\/([\w]+).([\w]+)/', $dns, $_matches)) {
      $dir = $_matches[1];
      $file_name = $_matches[2];
      $this->_name = $_matches[3];
    } else
      throw new \AppException('WrongSQLiteTableName', $dns);

    parent::__construct($file_name, $dir);

    $this->_default_query = [
        'from' => $this->_name
    ];
    $this->columns();
  }

  /****/
  public function columns(): array {
    if($this->_columns) return $this->_columns;

    // fetch the table structure
    $_sql = "SELECT `sql` FROM `sqlite_master` WHERE `tbl_name` = :tbl_name;";
    $_sth = $this->prepare($_sql);
    $_sth->bindParam(":tbl_name", $this->name());
    $_sth->execute();
    $_sql = $_sth->fetchColumn();
    $_sql = strtolower($_sql);

    $_res = array();

    $_sql = preg_replace("/([^>]+\()/", '', $_sql);
    $_sql = preg_replace("/\)([^>]*)/", '', $_sql);
    $_sql = preg_replace("/[\f\n\r\t\v]*/", '', $_sql);
    $_strs = explode(',', $_sql);

    foreach($_strs as $_str) {
      // preg_match($pattern, $subject, $matches, PREG_OFFSET_CAPTURE, 3);
      preg_match('/`([\w]+)`/', $_str, $_matches_name);
      if(is_string($_matches_name[1])) $_name = $_matches_name[1];

      $_type = strpos($_str, 'integer primary key') ? 'pk' : NULL;

      if(!$_type) {
        foreach ($this->columnTypes as $columnType=>$needle) {
          $_type = strpos($_str, $needle) ? $columnType : NULL;
          if($_type) break;
        }
      }

      $this->_columns[$_name]['_str'] = $_str;
      $this->_columns[$_name]['type'] = $_type;

      if($_type == 'pk') $this->_primary_key = $_name;
    }

    return $this->_columns;
  }

  /* < TODO: use php trait ... */

  /****/
  public function fields(): array {
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

  /* SQL-Constructor */

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
  public function update($columns = array(), $conditions='', $params=array()) {
      return parent::update($this->_name, $columns, $conditions='', $params);
  }

  /* </ use php trait ... */

  private function _prepareTable($dns) {
      $_parts = explode('.', str_replace(array('sqlite:'), '', $dns));

      $_name = $_parts[1];
      $_file_name = trim(substr($_parts[0], strrpos($_parts[0], '/')), '/');
      $_dir = str_replace(('/' . $_file_name), '', $_parts[0]);

      return array( $_name, $_dir, $_file_name );
  }
}
?>