<?php
namespace DB;

trait traitTable
{

  public function hasColumn(string $column_name = ''): bool {
    if(!$this->_columns) $this->columns();
    return array_key_exists($column_name, $this->_columns);
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

    return parent::save($this->name(), $data);
  }
}
?>