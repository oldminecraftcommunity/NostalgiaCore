<?php

define("BIG_ENDIAN", 0x00);
define("LITTLE_ENDIAN", 0x01);
define("ENDIANNESS", (pack("d", 1) === "\77\360\0\0\0\0\0\0" ? BIG_ENDIAN : LITTLE_ENDIAN));

class Utils{

	public static $online = true;
	public static $ip = false;

	/*public static function isOnline(){
		return ((@fsockopen("8.8.8.8", 80, $e = null, $n = null, 2) !== false or @fsockopen("www.linux.org", 80, $e = null, $n = null, 2) !== false or @fsockopen("www.php.net", 80, $e = null, $n = null, 2) !== false) ? true:false);
	}*/
	const emojiRegex = "([*#0-9](?>\\xEF\\xB8\\x8F)?\\xE2\\x83\\xA3|\\xC2[\\xA9\\xAE]|\\xE2..(\\xF0\\x9F\\x8F[\\xBB-\\xBF])?(?>\\xEF\\xB8\\x8F)?|\\xE3(?>\\x80[\\xB0\\xBD]|\\x8A[\\x97\\x99])(?>\\xEF\\xB8\\x8F)?|\\xF0\\x9F(?>[\\x80-\\x86].(?>\\xEF\\xB8\\x8F)?|\\x87.\\xF0\\x9F\\x87.|..(\\xF0\\x9F\\x8F[\\xBB-\\xBF])?|(((?<zwj>\\xE2\\x80\\x8D)\\xE2\\x9D\\xA4\\xEF\\xB8\\x8F\k<zwj>\\xF0\\x9F..(\k<zwj>\\xF0\\x9F\\x91.)?|(\\xE2\\x80\\x8D\\xF0\\x9F\\x91.){2,3}))?))";
	public static function getEntityTypeByID($id){
		return match ($id) {
			10, 11, 12, 13, 32, 33, 34, 35, 36 => ENTITY_MOB,
			62, 65, 80, 81, 82, 83, 84 => ENTITY_OBJECT,
			66 => FALLING_SAND,
			default => $id,
		};
	}
	
	public static function wrapAngleTo360($angle)
	{
		$angle = fmod($angle, 360);
		return $angle < 0 ? $angle + 360 : $angle;
	}
	
	public static function getSeedNumeric($seed){
		if($seed === "") return false;
		elseif(is_int($seed)) return (int)$seed;
		else{
			$i = 0;
			for($j = 0; $j < strlen($seed); ++$j){
				$i = $i * 31 + ord($seed[$j]);
			}
			return (int)$i;
		}
	}
	
	public static function sint32($r){
		$r &= 0xFFFFFFFF;
		if ($r & 0x80000000)
		{
			$r &= ~0x80000000;
			return -2147483648 + $r;
		}
		return $r;
	}
	
	public static function wrapAngleTo180($angle)
	{
		$angle = fmod($angle, 360);
		
		if($angle >= 180) $angle -= 360;
		if($angle < -180) $angle += 360;
		return $angle;
	}
	public static function getSign($v){
		return $v <=> 0;
	}
	
	public static function clampDegrees($v){
		return floor(($v % 360 + 360) % 360);
	}
	
	/**
	 * PHP8 has internal function for doing it: {@link str_ends_with}
	 */
	public static function endsWith($str, $check) {
		return str_ends_with($str, $check);
	}
	
	public static function hasEmoji($s){
		return preg_match(Utils::emojiRegex, $s);
	}
	
	public static function replaceEmoji($s){
		return preg_replace(Utils::emojiRegex, " ", $s);
	}
	
	public static function getCallableIdentifier(callable $variable){
		if(is_array($variable)){
			return sha1(strtolower(get_class($variable[0])) . "::" . strtolower($variable[1]));
		}else{
			return sha1(strtolower($variable));
		}
	}
	
	public static function in_range($num, $min, $max){
		return $num >= $min && $num <= $max;
	}
	
	public static function getUniqueID($raw = false, $extra = ""){
		$machine = php_uname("a");
		$machine .= file_exists("/proc/cpuinfo") ? `cat /proc/cpuinfo | grep "model name"` : "";
		$machine .= sys_get_temp_dir();
		$machine .= $extra;
		$os = Utils::getOS();
		if($os === "win"){
			@exec("ipconfig /ALL", $mac);
			$mac = implode("\n", $mac);
			if(preg_match_all("#Physical Address[. ]{1,}: ([0-9A-F\\-]{17})#", $mac, $matches)){
				foreach($matches[1] as $i => $v){
					if($v == "00-00-00-00-00-00"){
						unset($matches[1][$i]);
					}
				}
				$machine .= implode(" ", $matches[1]); //Mac Addresses
			}
		}elseif($os === "linux"){
			@exec("ifconfig", $mac);
			$mac = implode("\n", $mac);
			if(preg_match_all("#HWaddr[ \t]{1,}([0-9a-f:]{17})#", $mac, $matches)){
				foreach($matches[1] as $i => $v){
					if($v == "00:00:00:00:00:00"){
						unset($matches[1][$i]);
					}
				}
				$machine .= implode(" ", $matches[1]); //Mac Addresses
			}
		}
		$data = $machine . PHP_MAXPATHLEN;
		$data .= PHP_INT_MAX;
		$data .= PHP_INT_SIZE;
		$data .= get_current_user();
		foreach(get_loaded_extensions() as $ext){
			$data .= $ext . ":" . phpversion($ext);
		}
		return hash("md5", $machine, $raw) . hash("sha512", $data, $raw);
	}

	public static function getOS(){
		$uname = php_uname("s");
		if(stripos($uname, "Darwin") !== false){
			if(str_starts_with(php_uname("m"), "iP")){
				return "ios";
			}else{
				return "mac";
			}
		}elseif(stripos($uname, "Win") !== false or $uname === "Msys"){
			return "win";
		}elseif(stripos($uname, "Linux") !== false){
			if(@file_exists("/system/build.prop")){
				return "android";
			}else{
				return "linux";
			}
		}elseif(stripos($uname, "BSD") !== false or $uname === "DragonFly"){
			return "bsd";
		}else{
			return "other";
		}
	}

	public static function getIP($force = false){
		if(Utils::$online === false){
			return false;
		}elseif(Utils::$ip !== false and $force !== true){
			return Utils::$ip;
		}
		$ip = trim(strip_tags(Utils::curl_get("http://checkip.dyndns.org/")));
		if(preg_match('#Current IP Address\: ([0-9a-fA-F\:\.]*)#', $ip, $matches) > 0){
			Utils::$ip = $matches[1];
		}else{
			$ip = Utils::curl_get("http://www.checkip.org/");
			if(preg_match('#">([0-9a-fA-F\:\.]*)</span>#', $ip, $matches) > 0){
				Utils::$ip = $matches[1];
			}else{
				$ip = Utils::curl_get("http://checkmyip.org/");
				if(preg_match('#Your IP address is ([0-9a-fA-F\:\.]*)#', $ip, $matches) > 0){
					Utils::$ip = $matches[1];
				}else{
					$ip = trim(Utils::curl_get("http://ifconfig.me/ip"));
					if($ip != ""){
						Utils::$ip = $ip;
					}else{
						return false;
					}
				}
			}
		}
		return Utils::$ip;

	}
	
	public static function makeHeaders($json){
		$arr = json_decode($json);
		$rarr = [];
		foreach ($arr as $key => $value){
			$rarr[] = $key.": ".$value;
		}
		return $rarr;
	}
	
	public static function curl_get($page, $timeout = 10, $headers = " "){
		if(Utils::$online === false){
			return false;
		}
		
		if($headers != " "){
			$headers = Utils::makeHeaders($headers);
		}else{
			$headers = ["User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 PocketMine-MP"];
		}
		$ch = curl_init($page);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) $timeout);
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}

	public static function hexdump($bin){
		$output = "";
		$bin = str_split($bin, 16);
		foreach($bin as $counter => $line){
			$hex = chunk_split(chunk_split(str_pad(bin2hex($line), 32, " ", STR_PAD_RIGHT), 2, " "), 24, " ");
			$ascii = preg_replace('#([^\x20-\x7E])#', ".", $line);
			$output .= str_pad(dechex($counter << 4), 4, "0", STR_PAD_LEFT) . "  " . $hex . " " . $ascii . PHP_EOL;
		}
		return $output;
	}

	public static function printable($str){
		if(!is_string($str)){
			return gettype($str);
		}
		return preg_replace('#([^\x20-\x7E])#', '.', $str);
	}

	public static function getRandomUpdateTicks(){
		return -log(lcg_value()) * 1365.4; //Poisson distribution (1/(68.27 * 20))
	}

	public static function writeMetadata($data){
		$m = "";
		foreach($data as $bottom => $d){
			$m .= chr(($d["type"] << 5) | ($bottom & 0b00011111));
			switch($d["type"]){
				case 0:
					$m .= Utils::writeByte($d["value"]);
					break;
				case 1:
					$m .= Utils::writeLShort($d["value"]);
					break;
				case 2:
					$m .= Utils::writeLInt($d["value"]);
					break;
				case 3:
					$m .= Utils::writeLFloat($d["value"]);
					break;
				case 4:
					$m .= Utils::writeLShort(strlen($d["value"]));
					$m .= $data["value"];
					break;
				case 5:
					$m .= Utils::writeLShort($d["value"][0]);
					$m .= Utils::writeByte($d["value"][1]);
					$m .= Utils::writeLShort($d["value"][2]);
					break;
				case 6:
					for($i = 0; $i < 3; ++$i){
						$m .= Utils::writeLInt($d["value"][$i]);
					}
					break;
			}
		}
		$m .= "\x7f";
		return $m;
	}

	public static function writeByte($c){
		if($c > 0xff){
			return false;
		}
		if($c < 0 and $c >= -0x80){
			$c = 0xff + $c + 1;
		}
		return chr($c);
	}

	public static function writeLShort($value){
		return pack("v", $value);
	}

	public static function writeLInt($value){
		if($value < 0){
			$value += 0x100000000;
		}
		return pack("V", $value);
	}

	public static function writeLFloat($value){
		return ENDIANNESS === BIG_ENDIAN ? strrev(pack("f", $value)) : pack("f", $value);
	}

	public static function writeSlot(Item $item){
		return Utils::writeShort($item->getID()) . chr($item->count) . Utils::writeShort($item->getMetadata());
	}

	public static function writeShort($value){
		return pack("n", $value);
	}

	public static function readSlot($ob){
		$id = Utils::readShort($ob->get(2));
		$cnt = ord($ob->get(1));
		return BlockAPI::getItem(
			$id,
			Utils::readShort($ob->get(2)),
			$cnt
		);
	}

	public static function readShort($str, $signed = true){
		if(strlen($str) < 2){
			return;
		}

		list(, $unpacked) = unpack("n", $str);
		if($unpacked > 0x7fff and $signed === true){
			$unpacked -= 0x10000; // Convert unsigned short to signed short
		}

		return $unpacked;
	}

	public static function readMetadata($value, $types = false){
		$offset = 0;
		$m = [];
		$b = ord($value[$offset]);
		++$offset;
		while($b !== 127 and isset($value[$offset])){
			$bottom = $b & 0x1F;
			$type = $b >> 5;
			switch($type){
				case 0:
					$r = Utils::readByte($value[$offset]);
					++$offset;
					break;
				case 1:
					$r = Utils::readLShort(substr($value, $offset, 2));
					$offset += 2;
					break;
				case 2:
					$r = Utils::readLInt(substr($value, $offset, 4));
					$offset += 4;
					break;
				case 3:
					$r = Utils::readLFloat(substr($value, $offset, 4));
					$offset += 4;
					break;
				case 4:
					$len = Utils::readLShort(substr($value, $offset, 2));
					$offset += 2;
					$r = substr($value, $offset, $len);
					$offset += $len;
					break;
				case 5:
					$r = [];
					$r[] = Utils::readLShort(substr($value, $offset, 2));
					$offset += 2;
					$r[] = ord($value[$offset]);
					++$offset;
					$r[] = Utils::readLShort(substr($value, $offset, 2));
					$offset += 2;
					break;
				case 6:
					$r = [];
					for($i = 0; $i < 3; ++$i){
						$r[] = Utils::readLInt(substr($value, $offset, 4));
						$offset += 4;
					}
					break;

			}
			if($types === true){
				$m[$bottom] = [$r, $type];
			}else{
				$m[$bottom] = $r;
			}
			$b = ord($value[$offset]);
			++$offset;
		}
		return $m;
	}

	public static function readByte($c, $signed = true){
		$b = ord($c[0]);
		if($signed === true and ($b & 0x80) === 0x80){ //calculate Two's complement
			$b = -0x80 + ($b & 0x7f);
		}
		return $b;
	}

	public static function readLShort($str, $signed = true){
		list(, $unpacked) = @unpack("v", $str);
		if($unpacked > 0x7fff and $signed === true){
			$unpacked -= 0x10000; // Convert unsigned short to signed short
		}
		return $unpacked;
	}

	public static function readLInt($str){
		if(PHP_INT_SIZE === 8){
			return @unpack("V", $str)[1] << 32 >> 32;
		}else{
			return @unpack("V", $str)[1];
		}
	}

	public static function readLFloat($str){
		list(, $value) = ENDIANNESS === BIG_ENDIAN ? @unpack("f", strrev($str)) : @unpack("f", $str);
		return $value;
	}

	public static function readDataArray($str, $len = 10, &$offset = null){
		$data = [];
		$offset = 0;
		for($i = 1; $i <= $len and isset($str[$offset]); ++$i){
			$l = Utils::readTriad(substr($str, $offset, 3));
			$offset += 3;
			$data[] = substr($str, $offset, $l);
			$offset += $l;
		}
		return $data;
	}
	
	public static function readTriad($str){
		return strlen($str) < 3 ? 0 : @unpack("N", "\x00$str")[1]; //make sure this returns 0 on failure instead of false or else raknet parser will fall into infinite loop if start is false
	}

	public static function writeDataArray($data){
		$raw = "";
		foreach($data as $v){
			$raw .= Utils::writeTriad(strlen($v));
			$raw .= $v;
		}
		return $raw;
	}
	
	public static function writeLTriad($value){
		return substr(pack("V", $value), 0, -1);
	}
	
	public static function writeTriad($value){
		return substr(pack("N", $value), 1);
	}

	public static function getRandomBytes($length = 16, $secure = true, $raw = true, $startEntropy = "", &$rounds = 0, &$drop = 0){
		return $raw ? random_bytes($length) : bin2hex(random_bytes($length)); //nobody would ever notice other parameters
	}
	
	public static function chance($i){//GameHerobrine's code
		return lcg_value() <= $i / 100;
	}
	
	/**
	 * @deprecated use lcg_value instead
	 */
	public static function randomFloat(){
		return lcg_value();
	}

	public static function round($number){
		return round($number, 0, PHP_ROUND_HALF_DOWN);
	}
	/**
	 * manhattan distance
	 */
	public static function manh_distance($pos1, $pos2){
		if($pos1 instanceof Vector3){
			$pos1 = $pos1->toArray();
		}
		if($pos2 instanceof Vector3){
			$pos2 = $pos2->toArray();
		}
		
		return abs($pos2["x"] - $pos1["x"]) + abs($pos2["y"] - $pos1["y"]) + abs($pos2["z"] - $pos1["z"]);
	}
	
	/**
	 * Euclidian distance, but without square roots
	 */
	public static function distance_noroot($pos1, $pos2){
		if($pos1 instanceof Vector3){
			$pos1 = $pos1->toArray();
		}
		if($pos2 instanceof Vector3){
			$pos2 = $pos2->toArray();
		}
		$pX = ($pos1["x"] - $pos2["x"]);
		$pY = ($pos1["y"] - $pos2["y"]);
		$pZ = ($pos1["z"] - $pos2["z"]);
		return ($pX*$pX) + ($pY*$pY) + ($pZ*$pZ);
	}
	
	/**
	 * 
	 * @param $pos1
	 * @param $pos2
	 * @return number
	 */
	public static function distance($pos1, $pos2){
		if($pos1 instanceof Vector3){
			$pos1 = $pos1->toArray();
		}
		if($pos2 instanceof Vector3){
			$pos2 = $pos2->toArray();
		}
		return sqrt(($pos1["x"] - $pos2["x"])*($pos1["x"] - $pos2["x"]) + ($pos1["y"] - $pos2["y"])*($pos1["y"] - $pos2["y"]) + ($pos1["z"] - $pos2["z"])*($pos1["z"] - $pos2["z"]));
	}
	
	public static function angle3D($pos1, $pos2){
		if($pos1 instanceof Vector3){
			$pos1 = $pos1->toArray();
		}
		if($pos2 instanceof Vector3){
			$pos2 = $pos2->toArray();
		}
		$X = $pos1["x"] - $pos2["x"];
		$Z = $pos1["z"] - $pos2["z"];
		$dXZ = sqrt(pow($X, 2) + pow($Z, 2));
		$Y = $pos1["y"] - $pos2["y"];
		$hAngle = rad2deg(atan2($Z, $X) - M_PI_2);
		$vAngle = rad2deg(-atan2($Y, $dXZ));
		return ["yaw" => $hAngle, "pitch" => $vAngle];
	}

	public static function curl_post($page, $args, $timeout = 10){
		if(Utils::$online === false){
			return false;
		}

		$ch = curl_init($page);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 PocketMine-MP"]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) $timeout);
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}

	public static function strToHex($str){
		return bin2hex($str);
	}

	public static function hexToStr($hex){
		return hex2bin($hex);
	}

	public static function readBool($b){
		return Utils::readByte($b, false) != 0;
	}

	public static function writeBool($b){
		return Utils::writeByte($b ? 1 : 0);
	}

	public static function readInt($str){
		if(strlen($str) < 4) return 0; 
		
		return @unpack("N", $str)[1] << 32 >> 32; //php has no signed long unpack
	}

	public static function writeInt($value){
		if($value < 0){
			$value += 0x100000000;
		}
		return pack("N", $value);
	}

	public static function readFloat($str){
		if(strlen($str) < 4) return 0;
		list(, $value) = @unpack("G", $str);
		return $value;
	}

	public static function writeFloat($value){
		return pack("G", $value);
	}

	public static function printFloat($value){
		return preg_replace("/(\.\d+?)0+$/", "$1", sprintf("%F", $value));
	}

	public static function readDouble($str){
		list(, $value) = @unpack("E", $str);
		return $value;
	}

	public static function writeDouble($value){
		return pack("E", $value);
	}

	public static function readLDouble($str){
		if(strlen($str) < 8) return 0; 
		list(, $value) = @unpack("e", $str);
		return $value;
	}

	public static function writeLDouble($value){
		return pack("e", $value);
	}

	public static function readLLong($str){
		if(strlen($str) < 8) return 0; 
		return unpack("P", $str)[1];
	}

	public static function readLong($x, $signed = true){
		return strlen($x) < 8 ? 0 : @unpack("J", $x)[1]; //signed is useless since number cant be more than 2^63-1 in php
	}

	public static function writeLLong($value){
		return pack("P", $value);
	}

	public static function writeLong($value){
		return pack("J", $value);
	}
}

//if(Utils::isOnline() === false){
//Utils::$online = false;
//}
