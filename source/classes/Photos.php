<?php
	class Photo extends Model {
		protected $_table = "`bp_sov-art`.`photos`";
		protected $_folders = array('temp','products','news','authors','factories');
		protected $_im;
		public static $document_root = "/home/bp/beta.sov-art.net.ru/docs";
		public static $root = "/home/bp/beta.sov-art.net.ru/docs/media/photos";
		
		function __construct($data) {
			parent::__construct($data);
			
			// 
			if($this->isNew() && !$this->isEmpty('filepath')) {
				// фотография инициализирована абсолютным путем к файлу
				$_fileinfo = File::info($this->get('filepath'));
				if(empty($_fileinfo)) {
					throw new Exception('File not found');
					return NULL;
				}
				$this->set($this->_parseFileInfo($_fileinfo));
				$_fileroot = str_replace(array('/'.$this->get('folder').'/',$this->get('file')), '', $this->get('filepath'));
				if($_fileroot !== self::$root && !$this->isEmpty('file')) {
					$temp_filepath = self::$root."/temp/".$this->get('file');
					if(copy($this->get('filepath'), $temp_filepath)) {
						$_fileinfo = File::info($temp_filepath);
						$this->set($this->_parseFileInfo($_fileinfo));
					} else return NULL;
				}
			}
			
		}
		
		public function fetch($fields=NULL) {
			parent::fetch($fields);
			list($w, $h) = getimagesize($this->get('filepath'));
			$this->set('file', $this->get('filename').'.'.$this->get('extension'))
					->set('src', str_replace(self::$document_root, '', self::$root).'/'.$this->get('folder').'/'.$this->get('file'))
					->set('filesize', filesize($this->get('filepath')))
					->set('filesize_str', File::filesizeDisplay($this->get('filesize')))
					->set('width', $w)
					->set('height', $h);
			
			return $this;
		}
		
		public function getIM(){
			if(empty($this->_im)) {
					  if ($this->get('extension') == 'gif') $this->_im = imagecreatefromgif($this->get('filepath'));
				  elseif ($this->get('extension') == 'png') $this->_im = imagecreatefrompng($this->get('filepath'));
				  elseif ($this->get('extension') == 'bmp') $this->_im = imagecreatefromwbmp($this->get('filepath'));
													  else  $this->_im = imagecreatefromjpeg($this->get('filepath'));
			}
			if (empty($this->_im)) {
				$this->_im = imagecreate(600, 320);
				$bgc = imagecolorallocate ($this->_im, 255, 255, 255);
				$tc = imagecolorallocate ($this->_im, 0, 0, 0);
				imagefilledrectangle($this->_im, 0, 0, 150, 30, $bgc);
				imagestring($this->_im, 1, 5, 5, "Error loading ".$this->get('filepath'), $tc);
			}
			return $this->_im;
		}
		
		public function moveTo($new_folder) {
			if(in_array($new_folder, $this->_folders)) {
				$new_filepath = self::$root.'/'.$new_folder.'/'.$this->get('file');
				if(copy($this->get('filepath'), $new_filepath)) {
					unlink($this->get('filepath'));
					$this->set('filepath', $new_filepath)
							->set('path', str_replace('/'.$this->get('file'),'',$new_filepath))
							->set('folder', $new_folder)
							->set('src', str_replace(self::$document_root, '', $this->get('path')).'/'.$this->get('file'))
							->save();
				}
			}
			return $this;
		}
		
		public function pasteWatermark($position='center', $margin=array(10,10), $watermark="/media/photos/watermark.png"){
//			if(!$this->isValid()) return $this;
			$watermark_path = $_SERVER['DOCUMENT_ROOT'].$watermark;
			if (!is_file($watermark_path)) return $this;
			
			list($wmW, $wmH) = getimagesize($watermark_path);
			$wmIM = imagecreatefrompng($watermark_path);
			
//			$_wmW = round($this->get('width')/6.472);
//			$_wmH = round($_wmW*$wmW/$wmH);
//			$wmW = $_wmW; $wmH = $_wmH; unset($_wmW, $_wmH);
			switch ($position) {
				case 'center':
					$wmX = $this->get('width')/2 - $wmW/2;
					$wmY = $this->get('height') - $wmH - $margin[0];
				break;
				case 'lt':
					$wmX = $margin[1];
					$wmY = $margin[0];
				break;
				case 'rt':
					$wmX = $this->get('width') - $wmW - $margin[1];
					$wmY = $margin[0];
				break;
				case 'lb':
					$wmX = $margin[1];
					$wmY = $this->get('height') - $wmH - $margin[0];
				break;
				case 'rb':
					$wmX = $this->get('width') - $wmW - $margin[1];
					$wmY = $this->get('height') - $wmH - $margin[0];
				break;
				default: return false;
			}
			imagecopyresampled($this->getIM(), $wmIM, $wmX, $wmY, 0, 0, $wmW, $wmH, $wmW, $wmH);
			
			$this->saveIM();
			return $this;
		}
		
		public function rename($str){
			$new_filename = $this->Table->uniqTranslit('filename', $str);
			$new_filepath = $this->get('path').'/'.$new_filename.'.'.$this->get('extension');
			if(rename($this->get('filepath'),$new_filepath)) {
				$this->set('filepath', $new_filepath)
						->set('filename', $new_filename)
						->set('file', $new_filename.'.'.$this->get('extension'));
			} else throw new Exception('FailedRenamePhoto');
			return $this;
		}
		
		public function resize($w,$h){
			if (!is_file($this->get('filepath'))) return $this;
			$w = (int)$w; if (!$h) $h = $w;
			if (!$w || !$h || !is_numeric($w) || !is_numeric($h)) return $this;
			
			if($w/$h < $this->get('width')/$this->get('height')) {
				$per = $w/$this->get('width');
			} else {
				$per = $h/$this->get('height');
			}
			
			$width	= round($per * $this->get('width'));
			$height	= round($per * $this->get('height'));
			
			$image	= imagecreatetruecolor($width,$height);
			$bg	= imagecolorallocate($image, 255, 255, 255);
			imagefilledrectangle($image, 0, 0, $width, $height, $bg);
			
			imagecopyresampled($image, $this->getIM(), 0, 0, 0, 0, $width, $height, $this->get('width'), $this->get('height'));
			$this->set('width', $width);
			$this->set('height', $height);
			
			$this->saveIM($image);
			return $this;
		}
		
		public function saveIM($im=NULL){
			if(!empty($im)) $this->_im = $im;
				  if ($this->get('extension') == 'gif')	@imagegif($this->getIM(), $this->get('filepath'));
			  elseif ($this->get('extension') == 'png') @imagepng($this->getIM(), $this->get('filepath'));
			  elseif ($this->get('extension') == 'bmp') @imagewbmp($this->getIM(), $this->get('filepath'));
			                                      else  @imagejpeg($this->getIM(), $this->get('filepath'),90);
			return $this;
		}
		
		protected function _parseFileInfo($data){
			$data['file'] = $data['basename'];
			$data['path'] = $data['dirname'];
			$data['folder'] = basename($data['dirname']);
			$data['filepath'] = $data['dirname']."/".$data['file'];
			unset($data['dirname'], $data['basename']);
			
			return $data;
		}
	}
	
	class Photos extends Collection {
		public $ModelClass = Photo;
		
		function __construct() {
			parent::__construct();
		}
		
	}

?>