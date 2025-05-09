<?php

define("CONFIG_DETECT", -1); //Detect by file extension
define("CONFIG_PROPERTIES", 0); // .properties
define("CONFIG_CNF", CONFIG_PROPERTIES); // .cnf
define("CONFIG_JSON", 1); // .js, .json
define("CONFIG_YAML", 2); // .yml, .yaml
//define("CONFIG_EXPORT", 3); // .export, .xport
define("CONFIG_SERIALIZED", 4); // .sl
define("CONFIG_LIST", 5); // .txt, .list

/**
 * Class Config
 * Config Class for simple config manipulation of multiple formats.
 */
class Config{

	public static $formats = [
		"properties" => CONFIG_PROPERTIES,
		"cnf" => CONFIG_CNF,
		"conf" => CONFIG_CNF,
		"config" => CONFIG_CNF,
		"json" => CONFIG_JSON,
		"js" => CONFIG_JSON,
		"yml" => CONFIG_YAML,
		"yaml" => CONFIG_YAML,
		//"export" => CONFIG_EXPORT,
		//"xport" => CONFIG_EXPORT,
		"sl" => CONFIG_SERIALIZED,
		"serialize" => CONFIG_SERIALIZED,
		"txt" => CONFIG_LIST,
		"list" => CONFIG_LIST,
	];
	/**
	 * @var array
	 */
	private $config;
	/**
	 * @var string
	 */
	private $file;
	/**
	 * @var boolean
	 */
	private $correct;
	/**
	 * @var integer
	 */
	private $type = CONFIG_DETECT;

	/**
	 * @param string $file
	 * @param int $type
	 * @param array $default
	 * @param null|boolean $correct
	 */
	public function __construct($file, $type = CONFIG_DETECT, $default = [], &$correct = null){
		$this->load($file, $type, $default);
		$correct = $this->check();
	}

	/**
	 * @param string $file
	 * @param int $type
	 * @param array $default
	 *
	 * @return boolean
	 */
	public function load($file, $type = CONFIG_DETECT, $default = []){
		$this->correct = true;
		$this->type = (int) $type;
		$this->file = $file;
		if(!is_array($default)){
			$default = [];
		}
		if(!file_exists($file)){
			$this->config = $default;
			$this->save();
		}else{
			if($this->type === CONFIG_DETECT){
				$extension = explode(".", basename($this->file));
				$extension = strtolower(trim(array_pop($extension)));
				if(isset(Config::$formats[$extension])){
					$this->type = Config::$formats[$extension];
				}else{
					$this->correct = false;
				}
			}
			if($this->correct === true){
				$content = @file_get_contents($this->file);
				switch($this->type){
					case CONFIG_PROPERTIES:
					case CONFIG_CNF:
						$this->parseProperties($content);
						break;
					case CONFIG_JSON:
						$this->config = @json_decode($content, true);
						break;
					case CONFIG_YAML:
						$content = $this->fixYAMLIndexes($content);
						$this->config = yaml_parse($content);
						break;
					case CONFIG_SERIALIZED:
						$this->config = @unserialize($content);
						break;
					case CONFIG_LIST:
						$this->parseList($content);
						break;
					default:
						$this->correct = false;
						return false;
				}
				if(!is_array($this->config)){
					$this->config = $default;
				}
				if($this->fillDefaults($default, $this->config) > 0){
					$this->save();
				}
			}else{
				return false;
			}
		}
		return true;
	}
	/**
	 * @return boolean
	 */
	public function save(){
		if($this->correct === true){
			switch($this->type){
				case CONFIG_PROPERTIES:
				case CONFIG_CNF:
					$content = $this->writeProperties();
					break;
				case CONFIG_JSON:
					$content = json_encode($this->config, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING);
					break;
				case CONFIG_YAML:
					$content = yaml_emit($this->config, YAML_UTF8_ENCODING);
					break;
				case CONFIG_SERIALIZED:
					$content = @serialize($this->config);
					break;
				case CONFIG_LIST:
					$content = implode("\r\n", array_keys($this->config));
					break;
			}
			@file_put_contents($this->file, $content, LOCK_EX);
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @return string
	 */
	private function writeProperties(){
		$content = "#Properties Config file\r\n#" . date("D M j H:i:s T Y") . "\r\n";
		foreach($this->config as $k => $v){
			if(is_bool($v) === true){
				$v = $v === true ? "on" : "off";
			}elseif(is_array($v)){
				$v = implode(";", $v);
			}
			$content .= $k . "=" . $v . "\r\n";
		}
		return $content;
	}

	private function parseProperties($content){
		if(preg_match_all('/([a-zA-Z0-9\-_\.]*)=([^\r\n]*)/u', $content, $matches) > 0){ //false or 0 matches
			foreach($matches[1] as $i => $k){
				$v = trim($matches[2][$i]);
				switch(strtolower($v)){
					case "on":
					case "true":
					case "yes":
						$v = true;
						break;
					case "off":
					case "false":
					case "no":
						$v = false;
						break;
				}
				if(isset($this->config[$k])){
					console("[NOTICE] [Config] Repeated property " . $k . " on file " . $this->file, true, true, 2);
				}
				$this->config[$k] = $v;
			}
		}
	}

	public function fixYAMLIndexes($str){
		return preg_replace("#^([ ]*)([a-zA-Z_]{1}[^\:]*)\:#m", "$1\"$2\":", $str);
	}

	private function parseList($content){
		foreach(explode("\n", trim(str_replace("\r\n", "\n", $content))) as $v){
			$v = trim($v);
			if($v == ""){
				continue;
			}
			$this->config[$v] = true;
		}
	}

	/**
	 *
	 * @return integer
	 */
	private function fillDefaults($default, &$data){
		$changed = 0;
		foreach($default as $k => $v){
			if(is_array($v)){
				if(!isset($data[$k]) or !is_array($data[$k])){
					$data[$k] = [];
				}
				$changed += $this->fillDefaults($v, $data[$k]);
			}elseif(!isset($data[$k])){
				$data[$k] = $v;
				++$changed;
			}
		}
		return $changed;
	}

	/**
	 * @return boolean
	 */
	public function check(){
		return $this->correct === true;
	}

	public function reload(){
		unset($this->config);
		unset($this->correct);
		unset($this->type);
		$this->load($this->file);
		$this->check();
	}

	/**
	 *
	 * @return boolean|mixed
	 */
	public function &__get($k){
		return $this->get($k);
	}

	public function __set($k, $v){
		$this->set($k, $v);
	}

	/**
	 *
	 * @return boolean|mixed
	 */
	public function &get($k){
		if(isset($this->correct) and ($this->correct === false or !isset($this->config[$k]))){
			$false = false;
			return $false;
		}
		return $this->config[$k];
	}

	/**
	 * @param bool $v
	 */
	public function set($k, $v = true){
		$this->config[$k] = $v;
	}

	/**
	 *
	 * @return boolean
	 */
	public function __isset($k){
		return $this->exists($k);
	}

	/**
	 * @param bool $lowercase If set, searches Config in single-case / lowercase.
	 *
	 * @return boolean
	 */
	public function exists($k, $lowercase = false){
		if($lowercase === true){
			$k = strtolower($k);//Convert requested  key to lower
			$array = array_change_key_case($this->config, CASE_LOWER);//Change all keys in array to lower
			return isset($array[$k]);//Find $k in modified array
		}else{
			return isset($this->config[$k]);
		}
	}

	public function __unset($k){
		$this->remove($k);
	}

	public function remove($k){
		unset($this->config[$k]);
	}

	/**
	 * @param array $v
	 */
	public function setAll($v){
		$this->config = $v;
	}

	/**
	 * @param bool $keys
	 *
	 * @return array
	 */
	public function getAll($keys = false){
		return ($keys === true ? array_keys($this->config) : $this->config);
	}

}
