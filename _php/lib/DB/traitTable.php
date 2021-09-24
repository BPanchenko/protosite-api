<?php
namespace DB;

trait traitTable
{

  public function dns() {
    return $this->_dns;
  }

  public function drop() {
    return $this->_dbh->dropTable($this->_name);
  }

  public function hasColumn(string $column_name = ''): bool {
    if(!$this->_columns) $this->columns();
    return array_key_exists($column_name, $this->_columns);
  }

  public function name() {
    return $this->_name;
  }

  public function lastInsertId() {
    return $this->_dbh->lastInsertId();
  }

  public function save(array $data) {
    
    // check columns among the fields of database table
    foreach($data as $column=>$value) {
      if(!$this->hasColumn($column)) {
        unset($data[$column]);
      }
    }

    // creation date of the entity
    if($this->hasColumn('created')) $data['created'] = date("Y-m-d H:i:s");

    return $this->_dbh->save($this->name(), $data);
  }

  private function _getShortFieldType(string $origin): string {
    $origin = strtolower($origin);
    $type = '';
    $reg_numeric = '/^(?:int|tinyint|smallint|mediumint|bigint|float|decimal|double|real).*/';
    $reg_string = '/^(?:binary|char|enum|tinytext|text|mediumtext|longtext|varchar|varbinary).*/';

    if($origin == 'tinyint(1)') $type = 'boolean';
    elseif(in_array($origin, ['boolean', 'date', 'time', 'datetime', 'timestamp'])) {
      $type = $origin;
    }
    elseif(preg_match($reg_numeric, $origin)) $type = 'numeric';
    elseif(preg_match($reg_string, $origin)) $type = 'string';

    return $type;
  }
}
?>