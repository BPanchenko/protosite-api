<?php
	class File {
		public static $snd_exts = array("mp3");
		public static $vid_exts = array("flv","f4v","mp4");
		public static $img_exts = array("gif","jpg","png");
	
		public static function info($filepath) {
			$_info = array();
			if(strpos('.',$path) !== false && !is_file($filepath)) return NULL;
			if(is_file($filepath)) $_info = pathinfo($filepath);
			foreach (self::$img_exts as $value) {
				if (strpos('.',$path) === false && is_file($filepath.".".$value)) {
					$filepath .= ".".$value;
					$_info = array_merge($_info, pathinfo($filepath));
				}
				if($value == $_info['extension']) $_info['type'] = 'img';
			}
			foreach (self::$vid_exts as $value) {
				if (strpos('.',$path) === false && is_file($filepath.".".$value)) {
					$filepath .= ".".$value;
					$_info = array_merge($_info, pathinfo($filepath));
				}
				if($value == $_info['extension']) $_info['type'] = 'vid';
			}
			foreach (self::$snd_exts as $value) {
				if (strpos('.',$path) === false && is_file($filepath.".".$value)) {
					$filepath .= ".".$value;
					$_info = array_merge($_info, pathinfo($filepath));
				}
				if($value == $_info['extension']) $_info['type'] = 'snd';
			}
			return $_info;
		}
		
		public static function filesizeDisplay($filesize){
			if(is_numeric($filesize)){
				$decr = 1024; $step = 0;
				$prefix = array('Byte','KB','MB','GB','TB','PB');
				
				while(($filesize / $decr) > 0.9){
					$filesize = $filesize / $decr;
					$step++;
				}
				return round($filesize,2).' '.$prefix[$step];
			} else {
				return 'NaN';
			}
		}
	}
?>