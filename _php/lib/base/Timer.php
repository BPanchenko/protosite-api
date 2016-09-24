<?php
namespace \base;

	class Timer {
		private $start_time;
	
		private function get_time() {
			return microtime(1);
		}
		
		function start() {
			$this->start_time = $this->get_time();
			return $this->start_time;
		}
		
		function difference() {
			return ($this->get_time() - $this->start_time);
		}
	}
?>