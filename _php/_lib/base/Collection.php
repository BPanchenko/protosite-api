<?php
namespace base;

	class Collection extends Component implements \ArrayAccess {
		public static $classModel = Model;
		public $models = array();
		public $length = 0;
		public $pagination;
		protected $_columns = array();
		
		
		function __construct($data = array(), $parent = NULL) {
			if(is_array($data) && count($data))
				foreach($data as $item) {
					$this->add($item);
				}
			
			// init pagination
			$this->pagination = new \stdClass();
			$this->pagination->total = 0;
			$this->pagination->page = 1;
			$this->pagination->limit = 20;
			
			parent::__construct();
		}
		
		
		/****/
		public function offsetExists($offset) {
			return isset($this->models[$offset]);
		}
		public function offsetGet($offset) {
			return $this->offsetExists($offset) ? $this->models[$offset] : NULL;
		}

		public function offsetSet($offset, $model) {
			if (is_null($offset)) {
				$this->models[] = $model;
			} else {
				$this->models[$offset] = $model;
			}
			return $this;
		}

		public function offsetUnset($offset) {
			$index = NULL;
			unset($this->models[$index]);
			
			return is_null($index) ? false : true;
		}
		
		
		/**
		 * Добавление элемента в коллекцию
		 */
		public function add($data){
			if($data instanceof static::$classModel) {
				$model = $data;
				$model->collection = $this;
			} elseif(is_numeric($data) || is_array($data) || is_object($data)) {
				$model = new static::$classModel($data, $this);
			}
			
			if(!isset($model)) return $this;
			
			if($this->get($model->id))
				$this->get($model->id)->set($model->toArray());
			else {
				array_push($this->models, $model);
				$this->length++;
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
				$model = new static::$classModel($data, $this);
				$this->add($model);
			} catch(ErrorException $e) {
				if($e->getMessage() == 'WrongModelID') return NULL;
			}
			return $model;
		}
		
		
		/**
		 * Обход элементов коллекции.
		 * Для каждого элемента вызывается функция, переданая в параметре метода.
		 * При каждом вызове функции ей будут переданы 3 аргумента: $model, $index, $collection.
		 */
		public function each($fn) {
			if(!is_callable($fn)) return $this;
			foreach($this->models as $index=>$model)
				call_user_func($fn, $model, $index, $this);
			return $this;
		}
		
		
		/**
		 * Выполняет поиск элемента коллекции,
		 * подходящего под переданый массив атрибутов.
		 */
		public function findWhere($attributes, $value=NULL){
			if(!is_array($attributes) && $value) {
				$attributes = array(
					$attributes => $value
				);
			}
			return $this->where($attributes, true);
		}
		
		
		/**
		 * Возвращает модель из коллекции по ее идентификатору.
		 */
		public function get($id) {
			if($id && is_numeric($id))
				foreach($this->models as $model) {
					if($model->id == $id) return $model;
				}
			
			return NULL;
		}
		
		
		/**
		 * Поиск модели с минимальным значением атрибута $attr
		 */
		public function max($attr) {
			$result = NULL;
			
			for ($i = 0; $i <= $this->length; $i++) {
				if(!$this[$i]->has($attr))
					continue;
				
				if(is_null($result))
					$result = $this[$i];
				elseif($result->get($attr) < $this[$i]->get($attr))
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
				if(!method_exists($this[$i], 'has') || !$this[$i]->has($attr))
					continue;
				
				if(is_null($result))
					$result = $this[$i];
				elseif($result->get($attr) > $this[$i]->get($attr))
					$result = $this[$i];
			}
			
			return $result;
		}
		
		
		/**
		 * Возвращает индекс модели в коллекции.
		 * Если модели в коллекции нет результатом будет false.
		 */
		public function indexOf($searchValue) {
			$index = false;
			$id = $searchValue instanceof Model ? $searchValue->id : (int)$searchValue;
			
			foreach($this->models as $key=>$model)
				if($model->id == $id) {
					$index = $key;
					continue;
				}
			
			return $index;
		}
		
		
		/****/
		public function set($data, $end=0) {
			if(is_array($data) && count($data))
				foreach($data as $item) {
					$this->add($item);
				}
			return $this;
		}
		
		
		/**
		 * Выбирает срез коллекции начиная с идекса $start и заканчивая индексом $end.
		 */
		public function slice($start, $end=0) {
			$offset = $start;
			 $length = $end ? ($end - $start) : NULL;
			return  array_slice ($this->models, $offset, $length);
		}
		
		
		/**
		 * Преобразует коллекцию в простой массив,
		 * каждый элемент которого является ассоциативным массивом атрибутов модели.
		 */
		public function toArray($options=array()) {
			$array = array();
			if(!$this->length)
				return $array;
			
			if($options['bulk'] == 'ids') {
				foreach($this->models as $model) {
					array_push($array,$model->id);
				}
			} else {
				foreach($this->models as $model) {
					array_push($array,$model->toArray($options));
				}
			}
			return $array;
		}
		
		public function toJSON($options=array()) {
			return json_encode($this->toArray($options));
		}
		
		
		/**
		 * Выполняет поиск элементов коллекции,
		 * подходящих под переданый массив атрибутов.
		 */
		public function where($attributes, $first=false){
			$_result = array();
			
			foreach($this->models as $model) {
				$apt = true;
				foreach($attributes as $key => $val) {
					if($model->get($key) != $val) {
						$apt = false;
						break;
					}
				}
				if($apt)
					if($first)
						return $model;
					else
						array_push($_result, $model);
			}
			
			return $_result;
		}
	}
?>