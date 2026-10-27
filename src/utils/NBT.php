<?php

class NBT{

	const TAG_END = 0;
	const TAG_BYTE = 1;
	const TAG_SHORT = 2;
	const TAG_INT = 3;
	const TAG_LONG = 4;
	const TAG_FLOAT = 5;
	const TAG_DOUBLE = 6;
	const TAG_BYTE_ARRAY = 7;
	const TAG_STRING = 8;
	const TAG_LIST = 9;
	const TAG_COMPOUND = 10;

	public $tree = [];
	public $binary = b"";
	private $offset = 0;

	public function write($bin){
		$this->binary .= $bin;
	}

	/**
	 * A function to safely load sign nbt
	 * @param string $str raw nbt data
	 * @return false|array array with sign data, false if failed
	 */
	public function loadSignNBT($str){
		$this->offset = 0;
		$this->binary = (string) $str;
		//sign nbt in vanilla is
		/*
		 * [
		 * 	"id": "Sign",
		 * 	"x": <int>,
		 * 	"y": <int>,
		 * 	"z": <int>,
		 * 	"Text1": <string>, //max 15 chars
		 * 	"Text2": <string>, //^
		 * 	"Text3": <string>, //^
		 * 	"Text4": <string>  //^
		 * ]
		 */
		$tag = ord($this->read(1));
		if($tag == self::TAG_COMPOUND){
			$name = $this->readTAG_STRING_maxlen(2);
			if($name != "") return false;
			$value = [];
			for($n = 0; $n < 8; ++$n){
				$tag = ord($this->read(1));
				if($tag == self::TAG_END){
					break;
				}else if($tag == self::TAG_INT){
					$name = $this->readTAG_STRING_maxlen(1);
					if($name != "x" && $name != "y" && $name != "z") return false;
					$value[$name] = $this->readTAG_INT();
				}else if($tag == self::TAG_STRING){
					$name = $this->readTAG_STRING_maxlen(5);
					if($name == "id"){
						$s = $this->readTAG_STRING_maxlen(4);
						if($s != "Sign") return false;
						$value[$name] = $s;
					}else if($name == "Text1" || $name == "Text2" || $name == "Text3" || $name == "Text4"){
						$value[$name] = $this->readTAG_STRING_maxlen(15*4); //mcpe 0.8.1 supports unicode, so the max char size 4 bytes
					}else{
						return false;
					}
				}else{
					return false;
				}
			}
			if(count($value) != 8) return false;
			return $value;
		}else{
			return false;
		}
	}
	
	public function load($str){
		$this->offset = 0;
		$this->binary = (string) $str;
		$this->parseTree($this->tree);
	}

	private function parseTree(&$node){
		while(($tag = ord($this->read(1))) !== self::TAG_END and !$this->feof()){
			$name = $this->readTAG_STRING();
			switch($tag){
				case self::TAG_BYTE:
					$value = $this->readTAG_BYTE();
					break;
				case self::TAG_SHORT:
					$value = $this->readTAG_SHORT();
					break;
				case self::TAG_INT:
					$value = $this->readTAG_INT();
					break;
				case self::TAG_LONG:
					$value = $this->readTAG_LONG();
					break;
				case self::TAG_FLOAT:
					$value = $this->readTAG_FLOAT();
					break;
				case self::TAG_DOUBLE:
					$value = $this->readTAG_DOUBLE();
					break;
				case self::TAG_BYTE_ARRAY:
					$value = $this->readTAG_BYTE_ARRAY();
					break;
				case self::TAG_STRING:
					$value = $this->readTAG_STRING();
					break;
				case self::TAG_LIST:
					$value = [];
					$this->parseList($value, ord($this->read(1)), Utils::readLInt($this->read(4)));
					break;
				case self::TAG_COMPOUND:
					$value = [];
					$this->parseTree($value);
					break;
				default:
					echo bin2hex(substr($this->binary, $this->offset - 1)) . PHP_EOL . PHP_EOL;
					throw new NBTException("Invalid NBT Tag $tag");
			}
			$node[$name] = $value;
		}
	}

	public function read($n){
		if($n <= 0){
			return "";
		}
		$this->offset += $n;
		return substr($this->binary, $this->offset - $n, $n);
	}

	private function feof(){
		return !isset($this->binary[$this->offset]);
	}
	
	//i guess i could have modified old method?
	//*but it will make it slower </3*
	//*the nbt needs to be completely rewritten anyway this is barely usable </3*
	public function readTAG_STRING_maxlen($ml){
		$len = Utils::readLShort($this->read(2), false);
		return $this->read($len <= $ml ? $len : $ml);
	}
	public function readTAG_STRING(){
		return $this->read(Utils::readLShort($this->read(2), false));
	}

	public function readTAG_BYTE(){
		return Utils::readByte($this->read(1));
	}

	public function readTAG_SHORT(){
		return Utils::readLShort($this->read(2));
	}

	public function readTAG_INT(){
		return Utils::readLInt($this->read(4));
	}

	public function readTAG_LONG(){
		return Utils::readLLong($this->read(8));
	}

	public function readTAG_FLOAT(){
		return Utils::readLFloat($this->read(4));
	}

	public function readTAG_DOUBLE(){
		return Utils::readLDouble($this->read(8));
	}

	public function readTAG_BYTE_ARRAY(){
		return $this->read($this->readTAG_INT());
	}

	private function parseList(&$node, $tag, $cnt){
		for($i = 0; $i < $cnt and !$this->feof(); ++$i){
			switch($tag){
				case self::TAG_BYTE:
					$value = $this->readTAG_BYTE();
					break;
				case self::TAG_SHORT:
					$value = $this->readTAG_SHORT();
					break;
				case self::TAG_INT:
					$value = $this->readTAG_INT();
					break;
				case self::TAG_LONG:
					$value = $this->readTAG_LONG();
					break;
				case self::TAG_FLOAT:
					$value = $this->readTAG_FLOAT();
					break;
				case self::TAG_DOUBLE:
					$value = $this->readTAG_DOUBLE();
					break;
				case self::TAG_BYTE_ARRAY:
					$value = $this->readTAG_BYTE_ARRAY();
					break;
				case self::TAG_STRING:
					$value = $this->readTAG_STRING();
					break;
				case self::TAG_LIST:
					$value = [];
					$this->parseList($value, ord($this->read(1)), Utils::readLInt($this->read(4)));
					break;
				case self::TAG_COMPOUND:
					$value = [];
					$this->parseTree($value);
					break;
				default:
					echo bin2hex(substr($this->binary, $this->offset - 1)) . PHP_EOL . PHP_EOL;
					die("Invalid NBT Tag $tag");
			}
			$node[] = $value;
		}
	}

	public function writeTAG_BYTE($v){
		$this->binary .= chr($v);
	}

	public function writeTAG_LONG($v){
		$this->binary .= Utils::writeLLong($v);
	}

	public function writeTAG_FLOAT($v){
		$this->binary .= Utils::writeLFloar($v);
	}

	public function writeTAG_DOUBLE($v){
		$this->binary .= Utils::writeLDouble($v);
	}

	public function writeTAG_BYTE_ARRAY($v){
		$this->binary .= $this->writeTAG_INT(strlen($v)) . $v;
	}

	public function writeTAG_INT($v){
		$this->binary .= Utils::writeLInt($v);
	}

	public function writeTAG_STRING($v){
		$this->binary .= $this->writeTAG_SHORT(strlen($v)) . $v;
	}

	public function writeTAG_SHORT($v){
		$this->binary .= Utils::writeLShort($v);
	}
}