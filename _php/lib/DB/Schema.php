<?php
namespace DB;

class Schema extends \PDO {

  protected $_query;
  protected $_params;
  protected $_default_query;
  protected $_default_params;

  protected $_schema;
  protected $_tableNames;

  /**
   * Builds and execute a SQL statement for creating a new DB table.
   *
   * @param string $name the name of the table to be created. The name will be properly quoted by the method.
   * @param array $columns the columns (name=>definition) in the new table.
   * @param string $options additional SQL fragment that will be appended to the generated SQL.
   * @param boolean $dropIsExists.
   */
  public function createTable(string $name, array $columns, string $options='', bool $dropIsExists=false) {
    $cols = array();
    foreach($columns as $col=>$type)
      array_push($cols, "\t".$this->quote($col).' '.$this->getColumnType($type));

    $sql = "CREATE TABLE IF NOT EXISTS ".$this->quote($name)." (\n".implode(",\n",$cols)."\n)";

    if(!$options)
      $sql .= ' '.$options;

    if($dropIsExists && $this->hasTable($name))
      $this->dropTable($name);
    if(!is_null($this->_tableNames) && !$this->hasTable($name))
      array_push($this->_tableNames, $name);

    $this->exec($sql);

    return $this;
  }

  /****/
  public function renameTable(string $name, string $newName) {
    $this->exec('RENAME TABLE ' . $this->quote($name) . ' TO ' . $this->quote($newName));
    return $this;
  }

  /****/
  public function dropTable($name) {
    $this->exec("DROP TABLE ".$this->quote($name));
    if(!is_null($this->_tableNames)) unset($this->_tableNames[array_search($name, $this->_tableNames)]);
    return $this;
  }

  /****/
  public function truncateTable($name) {
    $this->exec("TRUNCATE TABLE ".$this->quote($name));
    return $this;
  }

  /****/
  public function addColumn($table, $column, $type) {
    /*
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
        . ' ADD ' . $this->db->quoteColumnName($column) . ' '
        . $this->getColumnType($type);
    */
    return $this;
  }

  /****/
  public function reset() {
    $this->_query = is_array($this->_default_query) ? $this->_default_query : array();
    $this->_params = is_array($this->_default_params) ? $this->_default_params : array();
    return $this;
  }

  /****/
  public function select($columns='*', string $option='') {
    if($columns == '*')
        return $this;

    if(is_string($columns) && strpos($columns,'(')!==false)
      $this->_query['select']=$columns;
    else {
      if(!is_array($columns))
        $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);

      foreach($columns as $i=>$column)
          if(is_object($column))
            $columns[$i] = (string)$column;
          elseif(strpos($column,'(') === false)
            if(preg_match('/^(.*?)(?i:\s+as\s+|\s+)(.*)$/', $column, $matches))  // with alias
              $columns[$i] = $this->quote($matches[1]) . ' AS ' . $this->quote($matches[2]);
            else
              $columns[$i] = $this->quote($column);

      $this->_query['select'] = implode(', ', $columns);
    }

    if($option!='') $this->_query['select'] = $option . ' ' . $this->_query['select'];

    return $this;
  }

  /****/
  public function selectDistinct($columns='*') {
    $this->select($columns)->_query['distinct'] = true;
    return $this;
  }

  /****/
  public function from($tables) {
    if(is_string($tables) && strpos($tables,'(')!==false)
      $this->_query['from']=$tables;
    else {
      if(!is_array($tables))
        $tables=preg_split('/\s*,\s*/',trim($tables),-1,PREG_SPLIT_NO_EMPTY);

      foreach($tables as $i=>$table)
        if(strpos($table,'(')===false)
          if(preg_match('/^(.*?)(?i:\s+as\s+|\s+)(.*)$/',$table,$matches))  // with alias
            $tables[$i]=$this->quote($matches[1]).' '.$this->quote($matches[2]);
          else
            $tables[$i]=$this->quote($table);

      $this->_query['from']=implode(', ',$tables);
    }

    return $this;
  }

  /****/
  public function where(string $conditions, array $params = array()): self {
    $this->_query['where'] = $conditions;
    foreach($params as $name=>$value) $this->_params[$name]=$value;
    return $this;
  }

  /****/
  public function andWhere(string $conditions, array $params = array()): self {
    $this->_query['where'] .= ' AND ' . $conditions;
    foreach($params as $name=>$value) $this->_params[$name] = $value;
    return $this;
  }

  /****/
  public function orWhere(string $conditions, array $params = array()): self {
    $this->_query['where'] .= ' OR ' . $conditions;
    foreach($params as $name=>$value) $this->_params[$name]=$value;
    return $this;
  }

  /****/
  public function groupBy($sql): self {
    $this->_query['group'] =  $sql;
    return $this;
  }

  /****/
  public function order($columns): self {
    if(!is_array($columns))
      $columns=preg_split('/\s*,\s*/',trim($columns),-1,PREG_SPLIT_NO_EMPTY);

    foreach($columns as $i=>$column)
      if(preg_match('/^(.*?)\s+(asc|desc)$/i', $column, $matches))
        $columns[$i] = $this->quote($matches[1]).' '.strtoupper($matches[2]);
      else
        $columns[$i] = $this->quote($column);

    $this->_query['order']=implode(', ',$columns);

    return $this;
  }

  /****/
  public function offset($offset): self {
    $this->_query['offset'] = (int)$offset;
    return $this;
  }

  /****/
  public function limit($limit, $offset=NULL): self {
    $this->_query['limit'] = $limit;
    if(!is_null($offset)) $this->offset($offset);
    return $this;
  }

  /****/
  public function fetch($fetch_style = \PDO::FETCH_ASSOC) {
    $sql = $this->_buildQuery();
    try {
      if(count($this->_params)) {
        $sth = $this->prepare($sql);
        $sth->execute($this->_params);
      } else {
        $sth = $this->query($sql);
      }
    } catch (PDOException $e) {
      print $e->getMessage();
    }

    return $sth->fetch($fetch_style);
  }

  /****/
  public function fetchColumn() {
    $sql = $this->_buildQuery();

    if(count($this->_params)) {
      $sth = $this->prepare($sql);
      $sth->execute($this->_params);
    } else {
      $sth = $this->query($sql);
    }

    $this->reset();

    return $sth->fetchColumn();
  }

  /****/
  public function fetchAll($fetch_style = \PDO::FETCH_OBJ): array {
    $sql = $this->_buildQuery();
    try {
      if(count($this->_params)) {
        $sth = $this->prepare($sql);
        $sth->execute($this->_params);
      } else {
        $sth = $this->query($sql);
      }
    } catch (PDOException $e) {
      print $e->getMessage();
    }

    $this->reset();

    return $sth->fetchAll($fetch_style);
  }

  /****/
  public function save(string $table, array $columns) {
    $params=array();
    $names=array();
    $placeholders=array();
    $equalities=array();

    if(!$columns['created']) $columns['created'] = date("Y-m-d H:i:s");

    foreach($columns as $name=>$value) {
      $names[] = $this->quote($name);
      $placeholders[] = ':' . $name;
      $params[':' . $name] = $value;

      if(!in_array($name, array('updated', 'created')))
          $equalities[] = end($names) . '=' . end($placeholders);
    }

    $sql = 'INSERT INTO ' . $this->quote($table)
            . ' (' . implode(', ',$names) . ')'
            . ' VALUES (' . implode(', ', $placeholders) . ')'
            . ' ON DUPLICATE KEY UPDATE ' . implode(", ",$equalities);

    $this->prepare($sql)
         ->execute($params);

    return $this;
  }

  /****/
  public function insert(string $table, array $columns) {
    $params=array();
    $names=array();
    $placeholders=array();

    foreach($columns as $name=>$value) {
      $names[] = $this->quote($name);
      $placeholders[] = ':' . $name;
      $params[':' . $name] = $value;
    }

    $sql='INSERT INTO ' . $this->quote($table)
          . ' (' . implode(', ',$names) . ')'
          . ' VALUES (' . implode(', ', $placeholders) . ')';

    $this->prepare($sql)
         ->execute($params);

    return $this;
  }

  /****/
  public function update(string $table, array $columns, $conditions='', array $params=array()) {
    $keys = array_keys($columns);
    $update_parts = array();

    foreach($keys as $key) array_push($update_parts, $this->quote($key)."=:".$key);

    $sql = "UPDATE ".$this->quote($table)." SET " . implode(", ",$update_parts);
    if($conditions) $sql .= " WHERE " . $conditions;

    return $this->prepare($sql)->execute(array_merge($columns, $params));
  }

  /****/
  public function hasTable(string $name): bool {
    if(is_null($this->_tableNames)) $this->tableNames();
    return in_array($name, $this->_tableNames);
  }

  /****/
  public function quote(string $string, $paramtype = NULL): string {
    $string = str_replace(array('`', '"', "'"), '', $string);

    if(strrpos($string,'.') === false) {
      return '`' . $string . '`';
    } else {
      $parts = array_map(function ($part) {
        return "`$part`";
      }, explode('.',$string));
    }

    return implode('.', $parts);
  }

  /****/
  public function getColumnType($type): string {
    if(isset($this->columnTypes[$type])) {
      return $this->columnTypes[$type];

    } elseif(($pos=strpos($type,' '))!==false) {
      $t=substr($type,0,$pos);
      return (isset($this->columnTypes[$t]) ? $this->columnTypes[$t] : $t).substr($type,$pos);

    } else {
      return $type;
    }
  }

  private function _buildQuery(): string {

    $sql=!empty($this->_query['distinct']) ? 'SELECT DISTINCT' : 'SELECT';
    $sql.=' '.(!empty($this->_query['select']) ? $this->_query['select'] : '*');

    if(!empty($this->_query['from']))
        $sql.="\nFROM ".$this->_query['from'];

    if(!empty($this->_query['join']))
        $sql.="\n".(is_array($this->_query['join']) ? implode("\n",$this->_query['join']) : $this->_query['join']);

    if(!empty($this->_query['where']))
        $sql.="\nWHERE ".$this->_query['where'];

    if(!empty($this->_query['group']))
        $sql.="\nGROUP BY ".$this->_query['group'];

    if(!empty($this->_query['having']))
        $sql.="\nHAVING ".$this->_query['having'];

    if(!empty($this->_query['union']))
        $sql.="\nUNION (\n".(is_array($this->_query['union']) ? implode("\n) UNION (\n",$this->_query['union']) : $this->_query['union']) . ')';

    if(!empty($this->_query['order']))
        $sql.="\nORDER BY ".$this->_query['order'];

    if(!empty($this->_query['limit']))
        $sql.="\nLIMIT ".$this->_query['limit'];

    if(!empty($this->_query['offset']))
        $sql.="\nOFFSET ".$this->_query['offset'];

    return $sql;
  }
}
?>