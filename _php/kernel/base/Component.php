<?php
namespace base;

abstract class Component {
  const EVENT_CHANGE = 'change';

  public array $paging = [];
  public $tb = null;
  public $tbs = null;

  protected $_children = [];
  protected $_default_fetch_options = [
      'fields' => [],
      'excluded_fields' => ['is_del'],
      'where' => 'is_del:0',
      'sort' => null,
      'count' => FETCH_DEFAULT_COUNT,
      'offset' => FETCH_DEFAULT_OFFSET
  ];
  protected $_fetch_options = [];
  protected $_parent;

  private $_events = [];


  function __construct($data = [], $parent = null) {

    if (!is_null($parent)) $this->attachTo($parent);
    
    if (is_string($this->tb)) $this->tb = self::initTable($this->tb);

    if(is_array($this->tbs))
      foreach($this->tbs as &$_tb_dns) {
        if($_tb_dns instanceof \PDO) continue;
        $_tb_dns = self::initTable($_tb_dns);
      }
  }

  public function attach($child): self
  {
    if($child instanceof \base\Component) array_push($this->_children, $child);
    return $this;
  }

  public function attachTo($parent): self
  {
    if($parent instanceof \base\Component) {
      $this->_parent = $parent;
      $parent->attach($this);
    }

    if($this instanceof \base\Model && $parent instanceof \base\Collection) {
      $this->collection = $parent;
      if($this->collection->tb instanceof \PDO && $this->tb == $this->collection->tb->dns())
        $this->tb = $this->collection->tb;
    }

    return $this;
  }


  /* Events bus
   ========================================================================== */

  public function trigger(string $name, array $event = []): self
  {
    if(!empty($this->_events[$name])) {
      if(empty($event['target'])) $event['target'] = $this;

      foreach ($this->_events[$name] as $handler) {
        $event['data'] = $handler[1];
        call_user_func($handler[0], $event);
      }
    }
    return $this;
  }

  public function on(string $name, callable $handler, $data = null, bool $append = true): self
  {
    if ($append || empty($this->_events[$name])) {
      $this->_events[$name][] = array($handler, $data);
    } else {
      array_unshift($this->_events[$name], array($handler, $data));
    }
    return $this;
  }

  public function off($name, $handler = null): bool
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

  public function fetch(array $options = [])
  {
    $options = $this->_prepareFetchOptions($options);

    // fetch Model
    if($this instanceof \base\Model) {
      $this->tb->reset()
          ->select($options['fields'])
          ->where($options['where'])
          ->limit(1);

      $data = $this->tb->fetch(\PDO::FETCH_ASSOC);
      if(!$data) throw new \AppException("FailedModelFetch", $options);

      $res = $this->parse($data);
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

  public function buildPaging(int $offset = 0, int $count = 20, int $total = 0): array
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

  protected function _prepareFetchOptions(array $options = []): array
  {
    $options = $options + $this->_fetch_options + $this->_default_fetch_options;

    // fields

    if(!empty($options['fields']))
      $options['fields'] = str2array($options['fields']);
    else
      $options['fields'] = $this->tb->fields();

    // exclude fields

    if(isset($options['excluded_fields'])) {
      $options['excluded_fields'] = str2array($options['excluded_fields']);
      foreach($options['excluded_fields'] as $_field) {
        $i = array_search($_field, $options['fields']);
        if($i) unset($options['fields'][$i]);
      }
    }

    // order

    if(is_string($options['sort'])) {
      $_orders = explode(',', $options['sort']);
      foreach($_orders as $_i=>$_order) {
        if($_order == 'random')
          $_orders[$_i] = 'RAND()';
        elseif(strpos($_order, '-') === 0)
          $_orders[$_i] = '`' . substr($_order, 1) . '` DESC';
        else
          $_orders[$_i] = '`' . $_order . '` ASC';
      }
      $options['order'] = join(', ', $_orders);

    } elseif(is_string($this->tb->primaryKey()))
      $options['order'] = '`' . $this->tb->primaryKey() . '` DESC';

    if(isset($_GET['debug'])) {
      var_dump("// Fetch options");
      var_dump($options);
    }

    // build where expression

    $_conditions = is_array($options['where']) ? $_conditions = $options['where'] : [];

    if($this instanceof \base\Collection && is_string($options['where'])) {
      
      $_conditions = explode(';', $options['where']);

      foreach($_conditions as $_i => $_condition) {
        preg_match('/([^\s!:]+)([!:]?:)([^:\s]+)/', $_condition, $_temp);

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
          $expr = str_replace(array('!:', '::', ':'), array(' NOT IN ', 'IN', 'IN'), $expr);
          $value = array_map(function($val) {
            return is_numeric($val) ? $val : '"' . strtolower($val) . '"';
          }, $value);
          $value = '(' . join(',', $value) . ')';
        } else if(is_numeric($value)) {
          $expr = str_replace(array('!:', '::', ':'), array('!=', '=', '='), $expr);
        } else {
          if ($expr == '::') $value = '"' . addslashes(strtolower($value)) . '"';
          else $value = '"%' . addslashes(strtolower($value)) . '%"';
          $expr = str_replace(array('!:', '::', ':'), array('NOT LIKE', 'LIKE', 'LIKE'), $expr);
        }

        $_conditions[$_i] = $column . ' ' . $expr . ' ' . $value;
      }

    } else if($this instanceof \base\Model) {
      if($this->isNew()) {
        throw new \AppException("Model can not be fetched");
      }
      if ($this->id) {
        if($this->tb->hasColumn(static::$idAttribute))
          array_push($_conditions, "`" . static::$idAttribute . "` = '" . $this->id . "'");
        else
          array_push($_conditions, "`" . $this->tb->primaryKey() . "` = '" . $this->id . "'");
      }
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
    if(is_string($table) === false) return $table;

    if(empty($table)) {
      throw new \base\SystemException('EmptyTableName');
    }
    elseif (strpos($table, 'sqlite:') === 0) {
      $table = new \DB\SQLite\Table($table);
    }
    elseif (strpos($table, 'mysql:') === 0) {
      $table = new \DB\MySql\Table($table);
    }
    else {
      throw new \base\SystemException('FailInitTable');
    }

    return $table;
  }


  /* Prepare api response
   ========================================================================== */

  public function prepareResponse(\http\Response $Response) {
    $Response->set('data', $this->toArray());
    return $this;
  }


  /* Helpers
   ========================================================================== */

  public function isAccessible(\http\Request $request): bool { return true; }
  public function isValid(): bool { return true; }


  /* Magic methods
   ========================================================================== */

  public function __get(string $attr)
  {
    if ($attr === 'parent') {
      return $this->_parent;
    } elseif ($this instanceof \base\Model) {
      return $this->get($attr);
    } elseif ($this instanceof \base\Collection) {
      return $this->pluck($attr);
    }
  }

  public function __toString() { return '{' . get_called_class() . ':' . json_encode($this->toArray()) .'}'; }
  public function __call($name, $arguments) { echo "Call undefined method '$name' " . implode(', ', $arguments). "\n"; }
  public static function __callStatic($name, $arguments) { echo "Call undefined static method '$name' " . implode(', ', $arguments). "\n"; }
}
