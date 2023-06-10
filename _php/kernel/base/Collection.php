<?php
namespace base;

class Collection extends Component implements \ArrayAccess {
  
  const EVENT_ADD = 'add';

  public $models = [];
  public $length = 0;
  public $pagination;
  public $total = 0;
  protected $_columns = [];
		
		
  function __construct($data = [], $parent = NULL) {
    if (is_array($data) && count($data)) {
      foreach($data as $item) {
        $this->add($item);
      }
    }

    // init pagination
    $this->pagination = new \stdClass();
    $this->pagination->total = 0;
    $this->pagination->page = 1;
    $this->pagination->limit = 20;

    parent::__construct($data, $parent);
  }
		
		
  /****/
  public function offsetExists($offset): bool {
    return isset($this->models[$offset]);
  }
  public function offsetGet($offset) {
    return $this->offsetExists($offset) ? $this->models[$offset] : null;
  }

  public function offsetSet(mixed $offset, mixed $model): void {
    if (is_null($offset)) {
      $this->models[] = $model;
    } else {
      $this->models[$offset] = $model;
    }
  }

  public function offsetUnset(mixed $offset): void {
    $index = NULL;
    unset($this->models[$index]);
  }
		
		
  /**
   * Добавление элемента в коллекцию
   */
  public function add($data): self {
    if ($this->isModel($data)) {
      $model = $data;
      $model->collection = $this;
    } elseif (is_numeric($data) || is_array($data) || is_object($data)) {
      $model = $this->initModel($data);
    }

    if (!$model->isValid()) return $this;

    if ($this->get($model->id))
      $this->get($model->id)->set($model->toArray());
    else {
      array_push($this->models, $model);
      $this->length++;
      $this->trigger(self::EVENT_ADD, [ 'model' => $model ]);
    }

    return $this;
  }
		
		
  public function at($index) {
    return $this->models[$index];
  }
		
		
  /**
   * Создание модели внутри коллекции.
   * Новая модель автоматически добавляется в коллекцию.
   */
  public function create($data){
    try {
      $model = $this->initModel($data);
      $this->add($model);
    } catch(\ErrorException $e) {
      if ($e->getMessage() == 'WrongModelID') return NULL;
    }
    return $model;
  }
		
		
  /**
   * Обход элементов коллекции.
   * Для каждого элемента вызывается функция, переданая в параметре метода.
   * При каждом вызове функции ей будут переданы 3 аргумента: $model, $index, $collection.
   */
  public function each($fn): self {
    if (!is_callable($fn)) return $this;
    foreach($this->models as $index=>$model) call_user_func($fn, $model, $index, $this);
    return $this;
  }
		
		
  /**
   * Выполняет поиск элемента коллекции,
   * подходящего под переданый массив атрибутов.
   */
  public function findWhere($attributes, $value){
    if (!is_array($attributes) && $value) {
      $attributes = [
          $attributes => $value
      ];
    }
    return $this->where($attributes, true)[0];
  }
		
		
  /**
   * Возвращает модель из коллекции по ее идентификатору.
   */
  public function get(int $id) {
    if ($id && is_numeric($id)) {
      foreach($this->models as $model) {
        if ($model->id == $id) return $model;
      }
    }
    return null;
  }
		
		
  /**
   * Поиск модели с минимальным значением атрибута $attr
   */
  public function max($attr) {
    $result = NULL;

    for ($i = 0; $i <= $this->length; $i++) {
      if (!$this[$i]->has($attr)) continue;

      if (is_null($result))
        $result = $this[$i];
      elseif ($result->get($attr) < $this[$i]->get($attr))
        $result = $this[$i];
    }

    return $result;
  }
		
		
  /**
   * Поиск модели с минимальным значением атрибута $attr
   */
  public function min($attr) {
    $result = NULL;

    for ($i = 0; $i <= $this->length; $i++) {
      if (!method_exists($this[$i], 'has') || !$this[$i]->has($attr))
        continue;

      if (is_null($result))
        $result = $this[$i];
      elseif ($result->get($attr) > $this[$i]->get($attr))
        $result = $this[$i];
    }

    return $result;
  }
		
		
  /**
   * Возвращает индекс модели в коллекции.
   * Если модели в коллекции нет результатом будет false.
   */
  public function indexOf($searchValue): int {
    $index = false;
    $id = $searchValue instanceof Model ? $searchValue->id : (int)$searchValue;

    foreach($this->models as $key=>$model) {
      if ($model->id == $id) {
        $index = $key;
        continue;
      }
    }

    return $index;
  }


  /* Saving collection's models
   ========================================================================== */

  public function save(): self {
    foreach($this as $model) $model->save();
    return $this;
  }
		
		
  /****/
  public function set(array $data, int $end=0): self {
    foreach($data as $item) $this->add($item);
    return $this;
  }
		
		
  /**
   * Выбирает срез коллекции начиная с идекса $start и заканчивая индексом $end.
   */
  public function slice(int $start, int $end=0) {
    $offset = $start;
    $length = $end ? ($end - $start) : NULL;
    return  array_slice($this->models, $offset, $length);
  }
		
		
  /**
   * Преобразует коллекцию в простой массив,
   * каждый элемент которого является ассоциативным массивом атрибутов модели.
   */
  public function toArray(array $options=[]): array {
    $result = [];

    if (!$this->length) return $result;

    if (isset($options['bulk']) && $options['bulk'] == 'ids') {
      foreach($this->models as $model) {
        array_push($result, $model->id);
      }
    } else {
      foreach($this->models as $model) {
          array_push($result, $model->toArray($options));
      }
    }

    return $result;
  }
		
  public function toJSON($options=[]) {
    return json_encode($this->toArray($options));
  }
		
		
  /**
   * Выполняет поиск элементов коллекции,
   * подходящих под переданый массив атрибутов.
   */
  public function where(array $attributes, bool $first=false): array{
    $_result = [];

    foreach($this->models as $model) {
      $apt = true;
      foreach($attributes as $key => $val) {
        if ($model->get($key) != $val) {
          $apt = false;
          break;
        }
      }
      if ($apt) {
        if ($first) return [$model];
        else array_push($_result, $model);
      }
    }

    return $_result;
  }


  /**
   * Вернет массив значений свойства каждого элемента коллекции.
   */
  public function pluck(string $attr): array {
    return array_map(fn($model) => $model->get($attr), $this->models);
  }


  /* Методы для работы с моделями коллекции требуют переопределения в
   * конкретных коллекциях для переопределения класса элементов коллекции.
   ========================================================================== */

  /**
   * Проверит является ли параметр моделью
   */
  protected function isModel($data): bool {
    return ($data instanceof Model);
  }

  /**
   * Вернёт инициализированный элемент коллекции
   */
  protected function initModel($data): Model {
    return $this->isModel($data) ? $data : new Model($data, $this);
  }


  /* API
   ========================================================================== */

  public function get_fields() {
    return $this->tb->columns();
  }

  /**
   * Обработка ответа на запрос к API
   */

  public function prepareResponse(\http\Response $Response) {
    $meta = $Response->get('meta');
    $meta->length = $this->length;
    $meta->paging = $this->paging;
    $meta->total = $this->total;

    $Response->set('meta', $meta);
    $Response->set('data', $this->toArray());

    return $this;
  }
}
