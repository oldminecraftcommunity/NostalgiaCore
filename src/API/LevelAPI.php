<?php

class LevelAPI{
	/**
	 * @var Level[]
	 */
	public $levels;
	private $server, $default;

	public function __construct(){
		$this->server = ServerAPI::request();
		$this->levels = [];
	}

	public function init(){
		//TODO per-level difficulty, interact, random-ticking
		$this->registerProperty("difficulty", 2, desc: "WIP for now, Difficulty level. 0 - Peaceful, 1 - Easy, 2 - Normal, 3 - Hard");
		$this->registerProperty("mobs-amount", 50, desc: "Max amount of mobs that can be spawned.");
		$this->registerProperty("spawn-animals", true, desc: "Spawn animals.");
		$this->registerProperty("spawn-monsters", true, desc: "Spawn monsters.");
		$this->registerProperty("pvp", true, desc: "Enable or disable pvp in the level");
		$this->registerProperty("random-ticking", "subchunk", desc: [
			"WIP", "Affects how random ticking works. Available modes:", 
			"subchunk - tick 16x16x16 areas", 
			"fullchunk - tick 16x16x128 areas", 
			"vanilla - tick 256x256x128 area"
		]);
		$this->registerProperty("interact", true, desc: "WIP, Enable or disable ability to interact with the level");
		$this->registerProperty("spawn-protection", 16, desc: "Prevent player interactions <n> blocks around the spawn.");
		
		
		$this->server->api->console->register("setworldprop", "[world] <property> <value>", [$this, "commandHandler"]);
		$this->server->api->console->register("getworldprop", "[world] <property>", [$this, "commandHandler"]);
		$this->server->api->console->register("reloadworldprops", "[world]", [$this, "commandHandler"]);
		$this->server->api->console->register("createworld", "<worldname> [type] [seed]", [$this, "commandHandler"]);
		$this->server->api->console->register("loadworld", "<worldname>", [$this, "commandHandler"]);
		
		$this->server->api->console->register("seed", "[world]", [$this, "commandHandler"]);
		$this->server->api->console->register("save-all", "", [$this, "commandHandler"]);
		$this->server->api->console->register("save-on", "", [$this, "commandHandler"]);
		$this->server->api->console->register("save-off", "", [$this, "commandHandler"]);
		$this->server->api->console->register("setwspawn", "Set the spawn position for your current world. ", [$this, "commandHandler"]);
		
		$this->default = $this->server->api->getProperty("level-name");
		if($this->loadLevel($this->default) === false){
			$this->generateLevel($this->default, $this->server->seed);
			$this->loadLevel($this->default);
		}
		$this->server->spawn = $this->getDefault()->getSafeSpawn();
	}
	
	
	private $defaultLevelProperties = [];
	private $levelPropsInfo = [];
	
	/**
	 * Registers a level property
	 * 
	 * @param string $name - property name
	 * @param mixed $defaultValue - default property value
	 * @param array[string]|string|boolean $desc=false - property description(false=none, string=single line, array of strings=multiline)
	 * @return false if the property exists, true if success
	 */
	public function registerProperty(string $name, $defaultValue, $desc = false){
		if(isset($this->defaultLevelProperties[$name])) return false;
		
		$this->defaultLevelProperties[$name] = $defaultValue;
		if(is_array($desc) || is_string($desc)) $this->levelPropsInfo[$name] = $desc;
		return true;
	}
	
	/**
	 * Gets a default value for property
	 * @param string $name - property name
	 * @param mixed $fail=false - return value if the property doesn't exist
	 * @return string|mixed
	 */
	public function getDefaultProperty(string $name, $fail=false){
		return $this->defaultLevelProperties[$name] ?? $fail;
	}
	
	/**
	 * Loads a properties for a level
	 * @param Level $level
	 */
	public function loadLevelProperties(Level $level){
		$propsPath = DATA_PATH . "worlds/{$level->getName()}/properties.yml";
		
		$level->levelProperties = new Config($propsPath, CONFIG_YAML, $this->defaultLevelProperties, comments: $this->levelPropsInfo);
		foreach($level->levelProperties->getAll() as $k => $v){
			$level->setProperty($k, $v);
		}
	}

	public function loadLevel($name){
		if($this->get($name) !== false){
			return true;
		}elseif($this->levelExists($name) === false){
			console("[NOTICE] Level \"" . $name . "\" not found");
			return false;
		}
		$path = DATA_PATH . "worlds/" . $name . "/";
		console("[INFO] Preparing level \"" . $name . "\"");
		$level = new PMFLevel($path . "level.pmf");
		if(!$level->isLoaded){
			console("[ERROR] Could not load level \"" . $name . "\"");
			return false;
		}
		
		if(PocketMinecraftServer::$KEEP_CHUNKS_LOADED){
			for($X = 0; $X < 16; ++$X){
				for($Z = 0; $Z < 16; ++$Z){
					$level->loadChunk($X, $Z);
				}
			}
		}
		
		$entities = new Config($path . "entities.yml", CONFIG_YAML);
		if(file_exists($path . "tileEntities.yml")){
			@rename($path . "tileEntities.yml", $path . "tiles.yml");
		}
		$tiles = new Config($path . "tiles.yml", CONFIG_YAML);
		$blockUpdates = new Config($path . "bupdates.yml", CONFIG_YAML);
		
		$this->levels[$name] = $lvl = new Level($level, $entities, $tiles, $blockUpdates, $name);
		$this->loadLevelProperties($lvl);
		
		foreach($entities->getAll() as $entity){
			if(!isset($entity["id"])){
				break;
			}
			
			$entity["x"] = $entity["Pos"][0];
			$entity["y"] = $entity["Pos"][1];
			$entity["z"] = $entity["Pos"][2];
			$entity["yaw"] = $entity["Rotation"][0];
			$entity["pitch"] = $entity["Rotation"][1];
			
			if($entity["id"] === 64){ //Item Drop
				$e = $this->server->api->entity->add($this->levels[$name], ENTITY_ITEM, ENTITY_ITEM_TYPE, [
					"meta" => $entity["Item"]["Damage"],
					"stack" => $entity["Item"]["Count"],
					"itemID" => $entity["Item"]["id"],
					"x" => $entity["Pos"][0],
					"y" => $entity["Pos"][1],
					"z" => $entity["Pos"][2],
					"yaw" => $entity["Rotation"][0],
					"pitch" => $entity["Rotation"][1],
				]);
			}elseif($entity["id"] === FALLING_SAND){
				$e = $this->server->api->entity->add($this->levels[$name], ENTITY_FALLING, $entity["id"], $entity);
				$e->setHealth($entity["Health"]);
			}elseif(Utils::getEntityTypeByID($entity["id"]) === ENTITY_OBJECT){ //Object
				$e = $this->server->api->entity->add($this->levels[$name], ENTITY_OBJECT, $entity["id"], $entity);
				$e->setHealth(1);
			}else{
				$e = $this->server->api->entity->add($this->levels[$name], ENTITY_MOB, $entity["id"], $entity);
				$e->setHealth($entity["Health"]);
			}
		}

		foreach($tiles->getAll() as $tile){
			if(!isset($tile["id"])){
				break;
			}
			$t = $this->server->api->tile->add($this->levels[$name], $tile["id"], $tile["x"], $tile["y"], $tile["z"], $tile);
		}

		$timeu = microtime(true);
		foreach($blockUpdates->getAll() as $bupdate){
			if($bupdate["type"] !== BLOCK_UPDATE_RANDOM) $this->server->api->block->scheduleBlockUpdate(new Position((int) $bupdate["x"], (int) $bupdate["y"], (int) $bupdate["z"], $this->levels[$name]), (float) $bupdate["delay"], (int) $bupdate["type"]);
		}
		
		return true;
	}
	/**
	 * Gets a level by name. Returns false if no level with such name exists.
	 * @param string $name
	 * @return Level|false
	 */
	public function get($name){
		if(isset($this->levels[$name])){
			return $this->levels[$name];
		}
		return false;
	}

	public function levelExists($name){
		if($name === ""){
			return false;
		}
		$path = DATA_PATH . "worlds/" . $name . "/";
		if($this->get($name) === false and !file_exists($path . "level.pmf")){
			$level = new LevelImport($path);
			if($level->import() === false){
				return false;
			}
		}
		return true;
	}

	public function generateLevel($name, $seed = false, $generator = false){
		if($this->levelExists($name)){
			return false;
		}
		$options = [];
		if($this->server->api->getProperty("generator-settings") !== false and trim($this->server->api->getProperty("generator-settings")) != ""){
			$options["preset"] = $this->server->api->getProperty("generator-settings");
		}

		if($generator !== false and class_exists($generator)){
			$generator = new $generator($options);
		}else{
			$generator = match (strtoupper($this->server->api->getProperty("level-type"))) {
				"FLAT" => new SuperflatGenerator($options),
				"VANILLA" => new VanillaGenerator($options),
				default => new TemporalGenerator($options),
			};
		}
		$gen = new WorldGenerator($generator, $name, $seed === false ? Utils::readInt(Utils::getRandomBytes(4, false)) : (int) $seed);
		$gen->generate();
		$gen->close();
		return true;
	}

	public function getDefault(){
		return $this->levels[$this->default];
	}

	public function commandHandler($cmd, $params, $issuer, $alias){
		$output = "";
		switch($cmd){
			case "setworldprop":
				
				return "WIP";
			case "getworldprop":
				
				return "WIP";
			case "reloadworldprops":
				
				return "WIP";
			case "createworld":
				return "WIP";
			case "loadworld":
				
				return "WIP";
				
			case "setwspawn":
				if(!($issuer instanceof Player)){
					return ("Please run this command in-game. ");
				}
				$issuer->entity->level->setSpawn(new Vector3($issuer->entity->x, $issuer->entity->y, $issuer->entity->z));
				return ("Spawn set!");
			case "save-all":
				$output .= "Saving...\n";
				foreach($this->server->clients as $p){
					$p->sendChat("Saving world, server might lag a bit...");
				}
				$save = $this->server->saveEnabled;
				$this->server->saveEnabled = true;
				$this->saveAll();
				$this->server->saveEnabled = $save;
				$output .= "Saved the world";
				break;
			case "save-on":
				$this->server->saveEnabled = true;
				break;
			case "save-off":
				$this->server->saveEnabled = false;
				break;
			case "seed":
				if(!isset($params[0]) and ($issuer instanceof Player)){
					$output .= "Seed: " . $issuer->level->getSeed() . "\n";
				}elseif(isset($params[0])){
					if(($lv = $this->server->api->level->get(trim(implode(" ", $params)))) !== false){
						$output .= "Seed: " . $lv->getSeed() . "\n";
					}
				}else{
					$output .= "Seed: " . $this->server->api->level->getDefault()->getSeed() . "\n";
				}
		}
		return $output;
	}

	public function saveAll(){
		foreach($this->levels as $level){
			$level->save();
		}
	}

	public function handle($data, $event){
		switch($event){
		}
	}

	public function __destruct(){
		$this->saveAll();
		foreach($this->levels as $level){
			$this->unloadLevel($level, true);
		}
	}

	public function unloadLevel(Level $level, $force = false){
		$name = $level->getName();
		if($name === $this->default and $force !== true){
			return false;
		}
		console("[INFO] Unloading level \"" . $name . "\"");
		$level->close();
		unset($this->levels[$name]);
		return true;
	}

	public function getSpawn(){
		return $this->server->spawn;
	}

	public function loadMap(){
		if($this->mapName !== false and trim($this->mapName) !== ""){
			if(!file_exists($this->mapDir . "level.pmf")){
				$level = new LevelImport($this->mapDir);
				$level->import();
			}
			$this->level = new PMFLevel($this->mapDir . "level.pmf");
			console("[INFO] Preparing level \"" . $this->level->getData("name") . "\"");
			$this->time = (int) $this->level->getData("time");
			$this->seed = (int) $this->level->getData("seed");
			$this->spawn = $this->level->getSpawn();
		}
	}

	public function getAll(){
		return $this->levels;
	}

}
