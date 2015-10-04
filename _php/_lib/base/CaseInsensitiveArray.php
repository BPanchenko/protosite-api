<?
namespace base;

	class CaseInsensitiveArray implements \ArrayAccess, \Countable, \Iterator {
		private $_container = array();
	
		public function offsetSet($offset, $value) {
			if (is_null($offset)) {
				$this->_container[] = $value;
			} else {
				$index = array_search(strtolower($offset), array_keys(array_change_key_case($this->_container, CASE_LOWER)));
				if ($index !== false) {
					$keys = array_keys($this->_container);
					unset($this->_container[$keys[$index]]);
				}
				$this->_container[$offset] = $value;
			}
		}
	
		public function offsetExists($offset) {
			return array_key_exists(strtolower($offset), array_change_key_case($this->_container, CASE_LOWER));
		}
	
		public function offsetUnset($offset) {
			unset($this->_container[$offset]);
		}
	
		public function offsetGet($offset) {
			$index = array_search(strtolower($offset), array_keys(array_change_key_case($this->_container, CASE_LOWER)));
			if ($index === false) {
				return null;
			}
	
			$values = array_values($this->_container);
			return $values[$index];
		}
	
		public function count() {
			return count($this->_container);
		}
	
		public function current() {
			return current($this->_container);
		}
	
		public function next() {
			return next($this->_container);
		}
	
		public function key() {
			return key($this->_container);
		}
	
		public function valid() {
			return !($this->current() === false);
		}
	
		public function rewind() {
			reset($this->_container);
		}
		
		public function push($value) {
			array_push($this->_container, $value);
		}
	}
?>