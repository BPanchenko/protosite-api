<?php
namespace base;

abstract class Component {
  const EVENT_CHANGE = 'change';

  public $tb = null;
  public $tbs = null;

  protected $_children = array();
  protected $_default_fetch_options = array(
    'fields' => array(),
    'excluded_fields' => array('is_del'),
    'where' => 'is_del:0',
    'order' => null,
    'count' => FETCH_DEFAULT_COUNT,
    'offset' => FETCH_DEFAULT_OFFSET
  );
  protected $_fetch_options = array();
  protected $_parent;

  private $_events = array();


  function __construct(array $data = array(), \base\Component $parent = null) {

    if(!$this->isAccessible()) throw new \AppException('AccessDenied');
    if(!is_null($parent)) $this->attachTo($parent);
    if(is_string($this->tb)) $this->tb = self::initTable($this->tb);

    if(is_array($this->tbs))
      foreach($this->tbs as $_tb_name=>$_tb_dns) {
        if($_tb_dns instanceof \PDO) continue;

        if(strpos($_tb_dns, '{model_id}') !== false && !$this->isNew()) {
          $_tb_dns = str_replace('{model_id}', $this->id, $_tb_dns);
          $this->tbs[$_tb_name] = self::initTable($_tb_dns);
        } else
          $this->tbs[$_tb_name] = self::initTable($_tb_dns);
      }
  }

  public function attach($child)
  {
    if($child instanceof \base\Component) array_push($this->_children, $parent_object);
    return $this;
  }

  public function attachTo($parent_object): self
  {
    if($parent_object instanceof \base\Component) {
      $this->_parent = $parent_object;
      $parent_object->attach($this);
    }

    if($this instanceof \base\Model && $parent_object instanceof \base\Collection) {
      $this->collection = $parent_object;
      if($this->collection->tb instanceof \PDO && $this->tb == $this->collection->tb->dns)
        $this->tb = $this->collection->tb;
    }

    return $this;
  }


  /* Events bus
   ========================================================================== */

  public function trigger($name, $event = null)
  {
    if(empty($this->_events[$name])) return $this;

    if(is_null($event)) $event = array();
    if(is_null($event['target'])) $event['target'] = $this;

    foreach ($this->_events[$name] as $handler) {
      $event['data'] = $handler[1];
      call_user_func($handler[0], $event);
    }

    return $this;
  }

  public function on(string $name, callable $handler, $data = null, bool $append = true)
  {
    if ($append || empty($this->_events[$name])) {
      $this->_events[$name][] = array($handler, $data);
    } else {
      array_unshift($this->_events[$name], array($handler, $data));
    }
    return $this;
  }

  public function off($name, $handler = null)
  {
    if (empty($this->_events[$name])) return false;

    if (is_null($handler)) {
      unset($this->_events[$name]);
      return true;
    } else {
      $removed = false;
      foreach ($this->_events[$name] as $i => $event) {
        if ($event[0] === $handler) {
          unset($this->_events[$name][$i]);
          $removed = true;
        }
      }
      if ($removed) {
        $this->_events[$name] = array_values($this->_events[$name]);
      }
      return $removed;
    }
  }


  /* Synchronization of the component with database support
   ========================================================================== */

  public function fetch($options = array())
  {
    $options = $this->_prepareFetchOptions($options);

    // fetch Model
    if($this instanceof \base\Model) {
      $this->tb->reset()
        ->select($options['fields'])
        ->where($options['where'])
        ->limit(1);

      $res = $this->parse($this->tb->fetch(\PDO::FETCH_ASSOC));
    }

    // fetch Collection
    if($this instanceof \base\Collection) {
      //
      $this->tb->reset()
        ->select($options['fields'])
        ->where($options['where'])
        ->order($options['order'])
        ->limit($options['count'])
        ->offset($options['offset']);
      //
      $res = $this->tb->fetchAll(\PDO::FETCH_ASSOC);

      // set total
      $this->tb->reset()
        ->select("count(*)")
        ->where($options['where']);
      $this->total = (int)$this->tb->fetchColumn();

      //
      $this->paging = $this->buildPaging($options['offset'], $options['count'], $this->total);
    }

    return $this->set($res);
  }

  public function buildPaging($offset = 0, $count = 20, $total = 0)
  {
    $current = floor($offset / $count);
    $prev = $current - 1;
    $last = floor($total / $count);
    $next = $current + 1;

    if($next > $last) $next = $last;
    if($prev < 0) $prev = $current;

    return array(
        'current' => $current,
        'prev' => $prev,
        'next' => $next,
        'last' => $last
    );
  }

  protected function _prepareFetchOptions($options)
  {
    $_opt = $this->_fetch_options + $this->_default_fetch_options;

    if(is_array($options))
      $options = $options + $_opt;
    else
      $options = $_opt;


    if(!empty($options['fields']))
      $options['fields'] = str2array($options['fields']);
    else
      $options['fields'] = $this->tb->fields();

    if(is_string($options['order'])) {
      $_orders = explode(',', $options['order']);
      foreach($_orders as $_i=>$_order) {
        if($options['order'] == 'random')
          $_orders[$_i] = 'RAND()';
        elseif(strpos($_order, '-') === 0)
          $_orders[$_i] = '`' . substr($_order, 1) . '` DESC';
        else
          $_orders[$_i] = '`' . $_order . '` ASC';
      }
      $options['order'] = join(', ', $_orders);

    } elseif(is_string($this->tb->primaryKey()))
      $options['order'] = '`' . $this->tb->primaryKey() . '` DESC';

    //
    if(isset($options['excluded_fields'])) {
      $options['excluded_fields'] = str2array($options['excluded_fields']);
      foreach($options['excluded_fields'] as $_field) {
        $i = array_search($_field, $options['fields']);
        if($i) unset($options['fields'][$i]);
      }
    }

    if(isset($_GET['debug'])) {
      var_dump("// Fetch options");
      var_dump($options);
    }


    // build where expression
    $_conditions = array();

    if($this instanceof \base\Collection) {
      if(is_array($options['where']))
        $_conditions = $options['where'];

      else if(is_string($options['where'])) {
        $_conditions = explode(';', $options['where']);
        foreach($_conditions as $_i => $_condition) {
          preg_match('/([^\s!]+)([!]?:)([^:\s]+)/', $_condition, $_temp);

          if(count($_temp) != 4) {
            unset($_conditions[$_i]);
            continue;
          }

          $column = $_temp[1];
          $expr = $_temp[2];
          $value = strpos($_temp[3], ',') === false ? $_temp[3] : explode(',', $_temp[3]);

          if($this->tb->hasColumn($column)) {
            $column = '`' . $this->tb->name() . '`.`' . $column . '`';
          } else {
            unset($_conditions[$_i]);
            continue;
          }

          if(is_array($value)) {
            $expr = str_replace(array('!:', ':'), array(' NOT IN ', 'IN'), $expr);
            $value = array_map(function($val) {
                return is_numeric($val) ? $val : "\"$val\"";
            }, $value);
            $value = '(' . join(',', $value) . ')';
          } else if(is_numeric($value)) {
            $expr = str_replace(array('!:', ':'), array('!=', '='), $expr);
          } else {
            $expr = str_replace(array('!:', ':'), array('NOT LIKE', 'LIKE'), $expr);
            $value = '"' . addslashes($value) . '"';
          }

          $_conditions[$_i] = $column . ' ' . $expr . ' ' . $value;
        }
      }

    } else if($this instanceof \base\Model) {
      if($this->tb->hasColumn(static::$idAttribute))
        array_push($_conditions, "`" . static::$idAttribute . "` = '" . $this->id . "'");
      else
        array_push($_conditions, "`" . $this->tb->primaryKey() . "` = '" . $this->id . "'");
    }

    $options['where'] = join(' AND ', $_conditions);

    return $options;
  }

  /**
   * @method initTable - helper method component framework.
   * The initialization of an object of class Table.
   * @param $table -
   */
  public static function initTable($table) {
    if($table instanceof \DB\Schema)
      return $table;

    if(empty($table))
      throw new \SystemException('EmptyTableName');

    if(strpos($table, 'sqlite:') === 0)
      $table = new \DB\SQLite\Table($table);

    if(strpos($table, 'mysql:') === 0)
      $table = new \DB\MySql\Table($table);

    if(!($table instanceof \DB\Schema))
      throw new \SystemException('FailInitTable');

    return $table;
  }


  /* Insert or update a component in the database
   ========================================================================== */

  public function save($data): self {
    if($data instanceof \stdClass)
      $data = json_decode(json_encode($data), true);

    if(is_array($data) && count($data))
      $this->set($this->parse($data));

    // save models of collection
    if($this instanceof \base\Collection) {
      foreach($this as $model) $model->save();
      return $this;
    }

    // save model
    $this->tb->save($this->toArray());
    $this->set(static::$idAttribute, $this->tb->lastInsertId());

    return $this;
  }


  /* Prepare api response
   ========================================================================== */

  public function prepareResponse($Response) {
    $Response->set('data', $this->toArray());
    return $this;
  }


  /* Helpers
   ========================================================================== */

  public function isAccessible(): bool { return true; }
  public function isValid(): bool { return true; }


  /* Magic methods
   ========================================================================== */

  public function __toString() { return '{' . get_called_class() . ':' . json_encode($this->toArray()) .'}'; }
  public function __call($name, $arguments) { echo "Call undefined method '$name' " . implode(', ', $arguments). "\n"; }
  public static function __callStatic($name, $arguments) { echo "Call undefined static method '$name' " . implode(', ', $arguments). "\n"; }
}

?>