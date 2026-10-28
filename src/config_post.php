<?php
set_time_limit(0);

function parseOffset(string $offset) : string|false{
	//Make signed offsets unsigned for date_parse
	if(str_starts_with($offset, '-')){
		$negative_offset = true;
		$offset = str_replace('-', '', $offset);
	}else{
		if(str_starts_with($offset, '+')){
			$negative_offset = false;
			$offset = str_replace('+', '', $offset);
		}else{
			return false;
		}
	}

	$parsed = date_parse($offset);
	$offset = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];

	//After date_parse is done, put the sign back
	if($negative_offset){
		$offset = -abs($offset);
	}

	//And then, look the offset up.
	//timezone_name_from_abbr is not used because it returns false on some(most) offsets because it\'s mapping function is weird.
	//That\'s been a bug in PHP since 2008!
	foreach(timezone_abbreviations_list() as $zones){
		foreach($zones as $timezone){
			if($timezone["timezone_id"] !== null && $timezone["offset"] == $offset){
				return $timezone["timezone_id"];
			}
		}
	}

	return false;
}

if(ini_get("date.timezone") == ""){ //No Timezone set
	date_default_timezone_set("GMT");
	if(str_contains(" " . strtoupper(php_uname("s")), " WIN")){ //windows
		$keyPath = 'HKLM\\SYSTEM\\CurrentControlSet\\Control\\TimeZoneInformation';
		$timezone = 'UTC'; //default

		/*
		 * Get the timezone offset through the registry
		 *
		 * Sample Output var_dump
		 * array(13) {
		 *   [0]=>
		 *   string(0) ""
		 *   [1]=>
		 *   string(71) "HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Control\TimeZoneInformation"
		 *   [2]=>
		 *   string(35) "    Bias    REG_DWORD    0xfffffe20"
		 *   [3]=>
		 *   string(43) "    DaylightBias    REG_DWORD    0xffffffc4"
		 *   [4]=>
		 *   string(45) "    DaylightName    REG_SZ    @tzres.dll,-571"
		 *   [5]=>
		 *   string(67) "    DaylightStart    REG_BINARY    00000000000000000000000000000000"
		 *   [6]=>
		 *   string(36) "    StandardBias    REG_DWORD    0x0"
		 *   [7]=>
		 *   string(45) "    StandardName    REG_SZ    @tzres.dll,-572"
		 *   [8]=>
		 *   string(67) "    StandardStart    REG_BINARY    00000000000000000000000000000000"
		 *   [9]=>
		 *   string(52) "    TimeZoneKeyName    REG_SZ    China Standard Time"
		 *   [10]=>
		 *   string(51) "    DynamicDaylightTimeDisabled    REG_DWORD    0x0"
		 *   [11]=>
		 *   string(45) "    ActiveTimeBias    REG_DWORD    0xfffffe20"
		 *   [12]=>
		 *   string(0) ""
		 * }
		 */
		exec("reg query " . escapeshellarg($keyPath), $output);

		foreach($output as $line){
			if(preg_match('/ActiveTimeBias\s+REG_DWORD\s+0x([0-9a-fA-F]+)/', $line, $matches) > 0){
				$offsetMinutes = ((int) hexdec(trim($matches[1]))) << 32 >> 32; //sign int

				if($offsetMinutes === 0){
					break; //UTC
				}

				$sign = $offsetMinutes <= 0 ? '+' : '-'; //windows timezone + and - are opposite
				$absMinutes = abs($offsetMinutes);
				$hours = floor($absMinutes / 60);
				$minutes = $absMinutes % 60;

				$offset = sprintf(
					"%s%02d:%02d",
					$sign,
					$hours,
					$minutes
				);

				$timezone = parseOffset($offset);
				break;
			}
		}

		if(date_default_timezone_set($timezone)){
			ini_set("date.timezone", $timezone);
		}
	}else{
		exec("date +%s", $t);
		$offset = round((intval(trim($t[0])) - time()) / 60) * 60;
		$daylight = (int) date("I");
		$d = timezone_name_from_abbr("", $offset, $daylight);
		@ini_set("date.timezone", $d);
		date_default_timezone_set($d);
	}
}else{
	$d = @date_default_timezone_get();
	if(!str_contains($d, "/")){
		$d = timezone_name_from_abbr($d);
		@ini_set("date.timezone", $d);
		date_default_timezone_set($d);
	}
}

gc_enable();
error_reporting(E_ALL | E_STRICT);
ini_set("allow_url_fopen", 1);
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
ini_set("default_charset", "utf-8");
if(defined("POCKETMINE_COMPILE") and POCKETMINE_COMPILE === true){
	define("FILE_PATH", realpath(dirname(__FILE__)) . "/");
}else{
	define("FILE_PATH", realpath(dirname(__FILE__) . "/../") . "/");
}
set_include_path(get_include_path() . PATH_SEPARATOR . FILE_PATH);

ini_set("memory_limit", "256M"); //Default
define("LOG", true);
define("START_TIME", microtime(true));
define("MAJOR_VERSION", "1.1.2dev");
define("CODENAME", "Nostalgia"); //i\'m not very creative - kotyaralih, yeah - tema1d.
define("CURRENT_MINECRAFT_VERSION", "v0.8.1 alpha");
define("CURRENT_API_VERSION", "12.3");
define("CURRENT_PHP_VERSION", "8.0");
$gitsha1 = false;
if(file_exists(FILE_PATH . ".git/refs/heads/master")){ //Found Git information!
	define("GIT_COMMIT", strtolower(trim(file_get_contents(FILE_PATH . ".git/refs/heads/master"))));
}else{ //Unknown :(
	define("GIT_COMMIT", str_repeat("00", 20));
}