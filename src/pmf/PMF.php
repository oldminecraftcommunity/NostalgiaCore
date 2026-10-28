<?php

define("PMF_CURRENT_VERSION", 0x01);

class PMF{

	protected $fp;
	protected $file;
	private $version;
	protected $type;

	public function __construct($file, $new = false, $type = 0, $version = PMF_CURRENT_VERSION){
		if($new === true){
			$this->create($file, $type, $version);
		}else{
			if($this->load($file) !== true){
				$this->parseInfo();
			}
		}
	}

	public function create($file, $type, $version = PMF_CURRENT_VERSION){
		$this->file = $file;
		$dirname = dirname($this->file);
		if(!is_dir($dirname)){
			@mkdir($dirname, 0755, true);
		}
		
		
		if(!is_resource($this->fp)){
			if(($this->fp = @fopen($file, "c+b")) === false){
				return false;
			}
		}
		$this->seek(0);
		$this->write("PMF" . chr((int) $version) . chr((int) $type));
	}

	public function seek($offset, $whence = SEEK_SET){
		if(is_resource($this->fp)){
			return fseek($this->fp, (int) $offset, (int) $whence);
		}
		return false;
	}

	public function write($string, $length = false){
		if(is_resource($this->fp)){
			return ($length === false ? fwrite($this->fp, $string) : fwrite($this->fp, $string, $length));
		}
		return false;
	}

	public function load($file){
		$this->close();
		$this->file = $file;
		if(($this->fp = @fopen($file, "c+b")) !== false){
			fseek($this->fp, 0, SEEK_END);
			if(ftell($this->fp) >= 5){ //Header + 2 Bytes
				@flock($this->fp, LOCK_EX);
				return true;
			}
			$this->close();
		}
		return false;
	}

	public function close(){
		unset($this->version, $this->type, $this->file);
		if(is_object($this->fp)){
			@flock($this->fp, LOCK_UN);
			fclose($this->fp);
		}
	}

	public function parseInfo(){
		$this->seek(0);
		$type = fread($this->fp, 3);
		if($type !== "PMF"){
			return false;
		}
		$this->version = ord($this->read(1));
		switch($this->version){
			case 0x01:
				$this->type = ord($this->read(1));
				break;
			default:
				console("[ERROR] Tried loading non-supported PMF version " . $this->version . " on file " . $this->file);
				return false;
		}
		return true;
	}

	public function read($length){
		if($length <= 0){
			return "";
		}
		if(is_resource($this->fp)){
			return fread($this->fp, (int) $length);
		}
		return false;
	}

	public function getVersion(){
		return $this->version;
	}

	public function getType(){
		return $this->type;
	}

	public function getFile(){
		return $this->file;
	}

}