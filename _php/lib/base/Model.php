<?php
namespace base;

class Model extends Component {
		
  const EVENT_CREATE = 'create';
  const EVENT_UPDATE = 'update';

  protected $_attributes = [];
  protected $_changed = [];
  protected $_defaults = [];
  protected $_previous = [];

  public static $idAttribute = 'id';
  public $id;
  public $collection;


  function __construct($data = [], $parent = NULL) {
    $data = $data ? $data + $this->_defaults : $this->_defaults;
    if ($parent instanceof Model) {
      $data[$parent::$idAttribute] = $parent->id;
    }
    $this->set($this->parse($data));
    parent::__construct($data, $parent);
  }


  /**
   * @method has()
   */
  public function has(string $attr): bool {
    return ($attr == 'id') ? !!$this->id : array_key_exists($attr, $this->_attributes);
  }


  /**
   * @method get()
   */
  public function get(string $attr) {
    if ($attr == 'id') return $this->id;
    if ($this->has($attr)) return $this->_attributes[$attr];
    return null;
  }


  /* Insert or update a model in the database
   ========================================================================== */

  public function save(array $data = []): self {
    if (count($data)) $this->set($this->parse($data));

    $is_new = $this->isNew();

    $this->tb->save($this->toArray());

    if ($is_new) {
      if ($this->tb->primaryKeyIsNumber())
        $this->set(static::$idAttribute, $this->tb->lastInsertId());
      $this->trigger(self::EVENT_CREATE);
    } else {
      $this->trigger(self::EVENT_UPDATE);
    }

    return $this;
  }


  /**
   * @method parse()
   */
  public function parse(array $data): array {
    return $data;
  }


  /**
   * @method set()
   */
  public function set($attr, $value = null): self {

    $attributes = [];
    is_array($attr) ? $attributes = $attr : $attributes[$attr] = $value;

    if (count($attributes)) {
      $_changed = [];
      $_previous = [];
    } else {
      return $this;
    }

    // предварительное приведение типов
    foreach($attributes as $key => $val) {
      if (is_double($val) && $val < PHP_INT_MAX) {
        $val = doubleval($val);
      } elseif (is_numeric($val) && $val < PHP_INT_MAX) {
        $val = strpos($val, '.') != false ? floatval($val) : intval($val);
      } elseif (is_string($val)) {
        $val = trim($val);
      }
      $attributes[$key] = $val;
    }

    foreach($attributes as $key => $val) {
      // Если атрибут был задан ранее и не равен новому значению,
      // то он сохраняется в хеше измененных атрибутов, а также
      // прежнее значение сохраняется в $this->_previous.
      if (isset($this->_attributes[$key]) && $this->_attributes[$key] !== $val) {
        $_previous[$key] = $this->_attributes[$key];
        $_changed[$key] = $val;
      } elseif (isset($this->_attributes[$key]) && $this->_attributes[$key] === $val) {
        continue;
      }

      if (isset($this->_attributes[$key]) && is_array($this->_attributes[$key]) && is_array($val)) {
        // для сохранения массива данных используется слияние старых и новых данных
        $this->_attributes[$key] = $val + $this->_attributes[$key];
      } else {
        $this->_attributes[$key] = $val;
      }

      if ($key == static::$idAttribute || $key == 'id') {
        $this->id = $this->_attributes[static::$idAttribute] = $val;
      }

      $this->trigger(self::EVENT_CHANGE . ":$key", [
          "value" => $val,
          "previous" => isset($_previous[$key]) ? $_previous[$key] : null
      ]);
    }

    $this->_changed = $_changed;
    $this->_previous = $_previous;

    if (count($_changed)) {
      $this->trigger(self::EVENT_CHANGE, [
        "changed" => $_changed,
        "previous" => $_previous
      ]);
    }

    return $this;
  }


  /**
   * @method delete()
   */
  public function delete(): bool {
    $this->tb->update(["is_del" => 1], "`".static::$idAttribute."` = " . $this->id);
    return true;
  }


  /**
   * @method destroy()
   */
  public function destroy(): bool {
    // TODO: remove all records about the model in the database
    return true;
  }


  /**
   * @method remove()
   */
  public function remove($attr): self {
    $attributes = str2array($attr);
    foreach($attributes as $key) unset($this->_attributes[$key]);
    return $this;
  }

  /**
   * @method isEmpty()
   */
  public function isEmpty(string $attr = ''): bool {
    return !count($this->_attributes)
        || !$this->has($attr)
        || !(bool)trim($this->get($attr))
        || !(bool)count($this->get($attr));
  }
  /**
   * @method isNew()
   */
  public function isNew(): bool {
    return !$this->id;
  }

  /**
   * @method toArray()
   */
  public function toArray(array $options = []): array {
    $result = [];

    if (!empty($options['fields'])) $fields = str2array($options['fields']);

    if (!empty($fields)) {
      if (!in_array(static::$idAttribute, $fields)) {
        array_unshift($fields, static::$idAttribute);
      }
      foreach($fields as $attr) {
        $attr = explode('.', $attr);
        if (array_key_exists($attr[0], $this->_attributes)) {
          if (!empty($attr[1]) && is_array($this->_attributes[$attr[0]]))
            $result[$attr[0]][$attr[1]] = $this->_attributes[$attr[0]][$attr[1]];
          else
            $result[$attr[0]] = $this->_attributes[$attr[0]];
        }
      }
    } else {
      foreach($this->_attributes as $attr=>$value)
        if (is_object($value) && method_exists($value, 'toArray'))
          $result[$attr] = call_user_func(array($value, 'toArray'));
        else
          $result[$attr] = $value;
    }

    if (!$this->isNew()) $result['id'] = $this->id;
    
    return $result;
  }

  public function toJSON(array $options = []): string {
    return json_encode($this->toArray($options));
  }

  /* Magic methods
   ========================================================================== */
  
  public function __set($attr, $value) { return $this->set($attr, $value); }
  public function __isset($attr) { return $this->has($attr); }
}
?>