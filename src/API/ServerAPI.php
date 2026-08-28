<?php

class ServerAPI{

	private static $serverRequest = false;
	public $restart = false;
	
	/**
	 * @var QueryAPI
	 */
	public $queryAPI;

	/**
	 * @var ConsoleAPI
	 */
	public $console;
	/**
	 * @var LevelAPI
	 */
	public $level;
	/**
	 * @var BlockAPI
	 */
	public $block;
	/**
	 * @var ChatAPI
	 */
	public $chat;
	/**
	 * @var BanAPI
	 */
	public $ban;
	/**
	 * @var EntityAPI
	 */
	public $entity;

	/**
	 * @var TimeAPI
	 */
	public $time;
	/**
	 * @var PlayerAPI
	 */
	public $player;
	/**
	 * @var TileAPI
	 */
	public $tile;
	/**
	 * @var AchievementAPI
	 */
	public $achievement;
	/**
	 * @var PluginAPI
	 */
	public $plugin;
	/**
	 * @var QueryHandler
	 */
	public $query;
	
	private $asyncCalls = [];
	private $server;
	private $config;
	private $apiList = [];
	private $asyncCnt = 0;
	private $rcon;

	/**
	 * @return PocketMinecraftServer
	 */
	public static function request(){
		return self::$serverRequest;
	}

	public function start(){
		return $this->run();
	}

	public function run(){
		$this->load();
		return $this->init();
	}

	public function load(){
		if(!file_exists(DATA_PATH . "worlds/")){
			mkdir(DATA_PATH . "worlds/", 0777);
		}

		if(!file_exists(DATA_PATH . "players/")){
			mkdir(DATA_PATH . "players/", 0777);
		}

		if(!file_exists(DATA_PATH . "plugins/")){
			mkdir(DATA_PATH . "plugins/", 0777);
		}

		//Init all the events
		foreach(get_declared_classes() as $class){
			if(is_subclass_of($class, "BaseEvent") and property_exists($class, "handlers") and property_exists($class, "handlerPriority")){
				$class::unregisterAll();
			}
		}

		console("[INFO] Starting Minecraft PE server version " . FORMAT_AQUA . CURRENT_MINECRAFT_VERSION);

		console("[INFO] Loading properties...");
		$this->config = new Config(DATA_PATH . "server.properties", CONFIG_PROPERTIES, [
			"server-name" => "Minecraft: PE Server",
			"description" => "Server made using NostalgiaCore",
			"motd" => "Welcome @player to this server!",
			"server-ip" => "",
			"server-port" => 19132,
			"server-type" => "normal",
			"memory-limit" => "128M",
			"white-list" => false,
			"announce-player-achievements" => true,
			//"spawn-protection" => 16,
			"view-distance" => 10,
			"max-players" => 20,
			"allow-flight" => true,
			//"spawn-animals" => true,
			//"spawn-mobs" => true,
			//"mobs-amount" => 50,
			"gamemode" => SURVIVAL,
			"hardcore" => false,
			//"pvp" => true,
			"generator-settings" => "",
			"difficulty" => 2,
			"level-name" => "world",
			"level-seed" => "",
			"level-type" => "DEFAULT",
			"enable-query" => true,
			"enable-rcon" => false,
			"rcon.password" => substr(base64_encode(Utils::getRandomBytes(20, false)), 3, 10),
			"auto-save" => true,
			"enable-mob-ai" => true,
			"abort-reading-after-N-packets" => PocketMinecraftServer::$PACKET_READING_LIMIT,
			"ticking-mode" => "legacy",
			"mushroom-spread" => Block::$mushroomSpread
		], comments: [
			"level-name" => [
				"Default level name"
			],
			"level-seed" => [
				"Default level seed"
			],
			"level-type" => [
				"Default level type",
				"Alowed types:",
				"FLAT - flat world",
				"DEFAULT - use one of the 4 pregenerated worlds from pocketmine",
				"VANILLA - unfinished, attempts to generate vanilla 0.8.1 world. This geneeator is very slow."
			],
			"abort-reading-after-N-packets" => [
				"Sets max amount of packets the server can read before forcing tick check."
			],
			"ticking-mode" => [
				"Sets ticking mode. Allowed modes:",
				"legacy - sleep while waiting for the next tick. same ticking as in old PocketMine. The server won't use a lot of cpu, but tps may be not stable(causes 16 TPS on Windows)",
				"nodelay - unused time is not used for anything.. May cause to use 100% of CPU.",
				"netwait - uses unused time to wait for packets. Should always give max tps. Untested on windows."
			],
			"server-type" => [
				"Changes server type. Does not affect anything in 0.8.1. Allowed types:",
				"normal - changes unconnected_pong reply to use MCCPP;Demo;",
				"demo - same as normal",
				"minecon - changes unconnected_pong reply to use MCCPP;MINECON;",
			],
			"generator-settings" => [
				"Used to set level settings when generating a world. Currently affects only FLAT world.",
				"FLAT world preset format: VERSION;LAYERS;STARTY;PARAMS",
				"The string contains multiple parameters split by ;",
				"VERSION - preset version (2)",
				"LAYERS - defines layers and their sizes",
				"	each layer is formatted as <ID>x<CNT>",
				"	<ID> - block id to fill the layer",
				"	<CNT> - layer height",
				"Multiple layers are separated by ,",
				"STARTY - start y level. Must be more than 0",
				"PARAMS - additional patamaters",
				"	spawn(radius=<N> block=<ID>) - sets spawn parameters",
				"		radius=<N> - sets spawn radius to <N>",
				"		block=<ID> - sets block to fill spawn area with",
				"	decoration(treecount=<N> grasscount=<M>) - sets amount of decorations",
				"Preset example:",
				"2;7,59x1,3x3,2;1;spawn(radius=10 block=89),decoration(treecount=80 grasscount=45)"
			],
			"mushroom-spread" => [
				"Enable or disable mushroom spreading"
			]
		]);
		Block::$mushroomSpread = $this->getProperty("mushroom-spread", Block::$mushroomSpread);
		Entity::$allowFly = $this->getProperty("allow-flight", true);
		if(!Entity::$allowFly){
			ConsoleAPI::warn("Fly checking is enabled! Players may experience issues with kicking while not flying!");
		}
		$this->parseProperties();
		Entity::$allowedAI = $this->getProperty("enable-mob-ai", true);
		PocketMinecraftServer::$PACKET_READING_LIMIT = $this->getProperty("abort-reading-after-N-packets", PocketMinecraftServer::$PACKET_READING_LIMIT);
		PocketMinecraftServer::$TICKING_MODE = match(strtolower($this->getProperty("ticking-mode"))){
			default => PocketMinecraftServer::TICK_LEGACY,
			"nodelay" => PocketMinecraftServer::TICK_F20TPS,
			"netwait" => PocketMinecraftServer::TICK_NETWAIT
		};
		//Load advanced properties
		define("DEBUG", $this->getProperty("debug", 1));
		define("ADVANCED_CACHE", false);
		//define("MAX_CHUNK_RATE", 20 / $this->getProperty("max-chunks-per-second", 8)); //Default rate ~512 kB/s

		if($this->getProperty("upnp-forwarding")){
			console("[INFO] [UPnP] Trying to port forward...");
			UPnP_PortForward($this->getProperty("server-port"));
		}

		$this->server = new PocketMinecraftServer($this->getProperty("server-name"), $this->getProperty("gamemode"), Utils::getSeedNumeric($this->getProperty("level-seed")), $this->getProperty("server-port"), ($ip = $this->getProperty("server-ip")) != "" ? $ip : "0.0.0.0");
		$this->server->api = $this;
		self::$serverRequest = $this->server;
		$this->server->send2Discord("[INFO] Starting Minecraft PE server version " . CURRENT_MINECRAFT_VERSION);
		console("[INFO] This server is running NostalgiaCore version " . MAJOR_VERSION . FORMAT_RESET . " \"" . CODENAME . "\" (MCPE: " . CURRENT_MINECRAFT_VERSION . ") (API " . CURRENT_API_VERSION . ") (PHP " . PHP_VERSION . ")", true, true, 0);
		console("[INFO] NostalgiaCore is distributed under the LGPL License", true, true, 0);

		$this->loadProperties();
		$this->loadAPI("console", "ConsoleAPI");
		$this->loadAPI("level", "LevelAPI");
		$this->loadAPI("block", "BlockAPI");
		$this->loadAPI("chat", "ChatAPI");
		$this->loadAPI("ban", "BanAPI");
		$this->loadAPI("entity", "EntityAPI");
		$this->loadAPI("tile", "TileAPI");
		$this->loadAPI("player", "PlayerAPI");
		$this->loadAPI("time", "TimeAPI");
		$this->loadAPI("queryAPI", "QueryAPI");
		$this->loadAPI("achievement", "AchievementAPI");
		foreach($this->apiList as $ob){
			if(is_callable([$ob, "init"])){
				$ob->init(); //Fails sometimes!!!
			}
		}
		$this->loadAPI("plugin", "PluginAPI"); //fix :(
		$this->plugin->init();

	}

	private function parseProperties(){
		foreach($this->config->getAll() as $n => $v){
			switch($n){
				case "gamemode":
				case "max-players":
				case "server-port":
				case "debug":
				case "difficulty":
					$v = (int) $v;
					break;
				case "server-id":
					if($v !== false){
						$v = preg_match("/[^0-9\-]/", $v) > 0 ? Utils::readInt(substr(md5($v, true), 0, 4)) : $v;
					}
					break;
			}
			$this->config->set($n, $v);
		}
		if($this->getProperty("hardcore") == 1 and $this->getProperty("difficulty") < 3){
			$this->setProperty("difficulty", 3);
		}
	}

	public function getProperty($name, $default = false){
		if(($v = arg($name)) !== false){ //Allow for command-line arguments
			switch(strtolower(trim($v))){
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
			switch($name){
				case "gamemode":
				case "max-players":
				case "server-port":
				case "debug":
				case "difficulty":
				case "time-per-second":
					$v = (int) $v;
					break;
				case "server-id":
					if($v !== false){
						$v = preg_match("/[^0-9\-]/", $v) > 0 ? Utils::readInt(substr(md5($v, true), 0, 4)) : $v;
					}
					break;
			}
			return $v;
		}
		return ($this->config->exists($name) ? $this->config->get($name) : $default);
	}

	public function setProperty($name, $value, $save = true){
		$this->config->set($name, $value);
		if($save){
			$this->writeProperties();
		}
		$this->loadProperties();
	}

	private function writeProperties(){
		$this->config->save();
	}

	private function loadProperties(){
		if(($memory = str_replace("B", "", strtoupper($this->getProperty("memory-limit")))) !== false){
			$value = ["M" => 1, "G" => 1024];
			$real = ((int) substr($memory, 0, -1)) * $value[substr($memory, -1)];
			if($real < 128){
				console("[WARNING] NostalgiaCore may not work right with less than 128MB of RAM", true, true, 0);
			}
			@ini_set("memory_limit", $memory);
		}else{
			$this->setProperty("memory-limit", "128M");
		}

		if($this->server instanceof PocketMinecraftServer){
			$this->server->setType($this->getProperty("server-type"));
			$this->server->maxClients = $this->getProperty("max-players");
			$this->server->description = $this->getProperty("description");
			$this->server->motd = $this->getProperty("motd");
			$this->server->gamemode = $this->getProperty("gamemode");
			$this->server->difficulty = $this->getProperty("difficulty");
			$this->server->whitelist = $this->getProperty("white-list");
		}
	}

	public function loadAPI($name, $class, $dir = false){
		if(isset($this->$name)){
			return false;
		}elseif(!class_exists($class)){
			$internal = false;
			if($dir === false){
				$internal = true;
				$dir = FILE_PATH . "src/API/";
			}
			$file = $dir . $class . ".php";
			if(!file_exists($file)){
				console("[ERROR] API " . $name . " [" . $class . "] in " . $dir . " doesn't exist", true, true, 0);
				return false;
			}
			require_once($file);
		}else{
			$internal = true;
		}
		$this->$name = new $class();
		$this->apiList[] = $this->$name;
		console("[" . ($internal === true ? "INTERNAL" : "DEBUG") . "] API \x1b[36m" . $name . "\x1b[0m [\x1b[30;1m" . $class . "\x1b[0m] loaded", true, true, ($internal === true ? 3 : 2));
	}

	public function init(){
		if(!(self::$serverRequest instanceof PocketMinecraftServer)){
			self::$serverRequest = $this->server;
		}

		if($this->getProperty("auto-save") === true){
			$this->server->schedule(18000, [$this, "autoSave"], [], true);
		}
		if(!defined("NO_THREADS") and $this->getProperty("enable-rcon") === true){
			$this->rcon = new RCON($this->getProperty("rcon.password", ""), $this->getProperty("rcon.port", $this->getProperty("server-port")), ($ip = $this->getProperty("server-ip")) != "" ? $ip : "0.0.0.0", $this->getProperty("rcon.threads", 1), $this->getProperty("rcon.clients-per-thread", 50));
		}

		if($this->getProperty("enable-query") === true){
			$this->query = new QueryHandler();
		}
		CraftingRecipes::init();
		$this->server->init();
		unregister_tick_function([$this->server, "tick"]);
		$this->console->__destruct();
		if($this->rcon instanceof RCON){
			$this->rcon->stop();
		}
		$this->__destruct();
		if($this->getProperty("upnp-forwarding") === true){
			console("[INFO] [UPnP] Removing port forward...");
			UPnP_RemovePortForward($this->getProperty("server-port"));
		}
		return $this->restart;
	}

	public function __destruct(){
		foreach($this->apiList as $i => $ob){
			if(method_exists($ob, "__destruct")){
				$ob->__destruct();
				unset($this->apiList[$i]);
			}
		}
	}

	/*-------------------------------------------------------------*/

	public function async(callable $callable, $params = [], $remove = false){
		$cnt = $this->asyncCnt++;
		$this->asyncCalls[$cnt] = new Async($callable, $params);
		return $remove === true ? $this->getAsync($cnt) : $cnt;
	}

	public function getAsync($id){
		if(!isset($this->asyncCalls[$id])){
			return false;
		}
		$ob = $this->asyncCalls[$id];
		unset($this->asyncCalls[$id]);
		return $ob;
	}

	public function autoSave(){
		console("[DEBUG] Saving....", true, true, 2);
		$this->server->api->level->saveAll();
	}

	public function asyncOperation($t, $d, $c = null){
		return $this->server->asyncOperation($t, $d, $c);
	}

	public function addHandler($e, $c, $p = 5){
		return $this->server->addHandler($e, $c, $p);
	}

	public function dhandle($e, $d){
		return $this->server->handle($e, $d);
	}

	public function handle($e, &$d){
		return $this->server->handle($e, $d);
	}

	public function schedule($t, $c, $d, $r = false, $e = "server.schedule"){
		return $this->server->schedule($t, $c, $d, $r, $e);
	}

	public function event($e, $d){
		return $this->server->event($e, $d);
	}

	public function trigger($e, $d){
		return $this->server->trigger($e, $d);
	}

	public function deleteEvent($id){
		return $this->server->deleteEvent($id);
	}

	public function getProperties(){
		return $this->config->getAll();
	}

	public function getList(){
		return $this->apiList;
	}

}
