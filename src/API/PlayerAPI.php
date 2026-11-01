<?php

class PlayerAPI{

	private $server;

	function __construct(){
		$this->server = ServerAPI::request();
	}

	public function init(){
		$this->server->addHandler("player.death", [$this, "handle"], 1);
		$this->registerCmd("list");
		$this->registerCmd("kill", "<player>");
		$this->registerCmd("gamemode", "<mode> [player]");
		$this->registerCmd("tp", "[target player] <destination player|w:world> OR /tp [target player] <x> <y> <z>");
		$this->registerCmd("spawnpoint", "[player] [x] [y] [z]");
		$this->registerCmd("spawn");
		$this->registerCmd("ping");
		$this->registerCmd("loc");
		$this->registerCmd("hotbar", "<slotcount>");
		
		$this->server->api->console->alias("lag", "ping");
		$this->server->api->console->alias("gm", "gamemode");
		$this->server->api->console->alias("who", "list");
		$this->server->api->console->alias("suicide", "kill");
		$this->server->api->console->alias("tppos", "tp");
		$this->server->api->console->alias("xyz", "loc");
		$this->server->api->console->alias("coords", "loc");

		$this->server->api->console->cmdWhitelist("list");
		$this->server->api->console->cmdWhitelist("ping");
		$this->server->api->console->cmdWhitelist("spawn");
		$this->server->api->console->cmdWhitelist("loc");
		$this->server->api->console->cmdWhitelist("hotbar");
	}

	public function registerCmd($cmd, $help = ""){
		$this->server->api->console->register($cmd, $help, [$this, "commandHandler"]);
	}

	public function handle($data, $event){
		switch($event){
			case "player.death":
				if(is_numeric($data["cause"])){
					$e = $this->server->api->entity->get($data["cause"]);
					if($e instanceof Entity){
						if($e instanceof Arrow){
							if($e->shotByEntity && isset($this->server->api->entity->entities[$e->shooterEID]) && $this->server->api->entity->entities[$e->shooterEID] instanceof Entity){
								$message = " was shot by {$this->server->api->entity->entities[$e->shooterEID]->name}";
							}else{
								$message = " was shot";	
							}
							
						}else{
							$message = " was killed by {$e->name}";
						}
					}
				}else{
					$message = match ($data["cause"]) {
						"cactus" => " was pricked to death",
						"lava" => " tried to swim in lava",
						"fire" => " went up in flames",
						"burning" => " burned to death",
						"suffocation" => " suffocated in a wall",
						"water" => " drowned",
						"void" => " fell out of the world",
						"fall" => " hit the ground too hard",
						"explosion" => " blew up",
						default => " died",
					};
				}
				$this->server->api->chat->broadcast($data["player"]->username . $message);
				return true;
		}
	}

	public function commandHandler($cmd, $args, $issuer, $alias){
		$output = "";
		switch($cmd){
			case "spawnpoint":
				if(!($issuer instanceof Player)){
					return "Please run this command in-game.";
				}

				if(count($args) === 1 or count($args) === 4){
					$target = $this->server->api->player->get(array_shift($args));
				}else{
					$target = $issuer;
				}

				if(!($target instanceof Player)){
					return "That player cannot be found.";
				}

				if(count($args) === 3){
					$spawn = new Position(floatval(array_shift($args)), floatval(array_shift($args)), floatval(array_shift($args)), $issuer->level);
				}else{
					$spawn = new Position($issuer->entity->x, $issuer->entity->y, $issuer->entity->z, $issuer->entity->level);
				}

				$target->setSpawn($spawn);
				return "Spawnpoint set correctly!\n";
			case "hotbar":
				if(!($issuer instanceof Player)) return "Please run this command in-game.";
				if(count($args) < 1) return "Slots in hotbar on server: {$issuer->slotCount}";
				
				$scrw = $args[0];
				if(is_numeric($scrw)){
					$sc = (int)$scrw;
					if($sc < 5 || $sc > 9) return "Slot count must be between 5 and 9.";
					
					$issuer->setSlotCount($sc);
					$issuer->sendInventory();
					return "Changed slot count to $sc";
				}else{
					return "Usage: /$cmd <slotcount>";
				}
			case "spawn":
				if(!($issuer instanceof Player)){
					return "Please run this command in-game.";
				}
				$issuer->teleport($this->server->spawn);
				return "You were teleported to the spawn.";
			case "ping":
				$additional = false;
				if(!($issuer instanceof Player)){
					if(isset($args[0])){
						goto get_ping_of_others;
					}
					return "Usage: /$cmd <username>.";
				}
				
				if(isset($args[0]) && $this->server->api->ban->isOp($issuer->iusername)){
					get_ping_of_others:
					$additional = true;
					$p = $this->get($args[0]);
					if(!($p instanceof Player)){
						return "Player with username '{$args[0]}' is not on server.";
					}
				}else{
					$p = $issuer;
				}
				$ping = round($p->getLag(), 2);
				$pkLoss = round($p->getPacketLoss() * 100, 2);
				$transfer = round($p->getBandwidth() / 1024, 2);
				$additionalInfo = "";
				if($additional){
					$arqCnt = count($p->packetAlwaysRecoverQueue);
					$rqCnt = count($p->recoveryQueue);
					$maxAck = $this->server->interface->maxACKsAllowed["{$p->ip}:{$p->port}"] ?? "???";
					$additionalInfo = "ARQ/RQ/MAXACK: $arqCnt/$rqCnt/$maxAck";
				}
				$chunksCnt = count($p->chunkDataSent);
				return "{$p->username}'s ping: {$ping}ms, packet loss {$pkLoss}%, $transfer KB/s. Chunks: $chunksCnt/256\n$additionalInfo";
			case "gamemode":
				$player = false;
				$setgm = false;
				$gms = [
					"0" => SURVIVAL,
					"survival" => SURVIVAL,
					"s" => SURVIVAL,
					"1" => CREATIVE,
					"creative" => CREATIVE,
					"c" => CREATIVE,
					"2" => ADVENTURE,
					"adventure" => ADVENTURE,
					"a" => ADVENTURE,
					"3" => VIEW,
					"view" => VIEW,
					"viewer" => VIEW,
					"spectator" => VIEW,
					"v" => VIEW,
				];
				if($issuer instanceof Player){
					$player = $issuer;
				}
				if(count($args) == 1){
					$setgm = $args[0];
				}elseif(isset($args[1])){
					if($this->server->api->player->get($args[1]) instanceof Player){
						$player = $this->server->api->player->get($args[1]);
						$setgm = $args[0];
					}elseif($this->server->api->player->get($args[0]) instanceof Player){
						$player = $this->server->api->player->get($args[0]);
						$setgm = $args[1];
					}else{
						return "Usage: /$cmd <mode> [player] or /$cmd [player] <mode>\n";
					}
				}
				if(!($player instanceof Player) or !isset($gms[strtolower($setgm)])){
					return "Usage: /$cmd <mode> [player] or /$cmd [player] <mode>\n";
				}
				if($player->setGamemode($gms[strtolower($setgm)])){
					return "Gamemode of " . $player->username . " changed to " . $player->getGamemode() . "\n";
				}
				break;
			case "tp":
				if(count($args) <= 2 or str_starts_with($args[0], "w:") or str_starts_with($args[1], "w:")){
					if((!isset($args[1]) or str_starts_with($args[0], "w:")) and isset($args[0]) and ($issuer instanceof Player)){
						$name = $issuer->username;
						$target = implode(" ", $args);
					}elseif(isset($args[1]) and isset($args[0])){
						$name = array_shift($args);
						$target = implode(" ", $args);
					}else{
						return "Usage: /$cmd [target player] <destination player>\n";
					}
					if($this->teleport($name, $target) !== false){
						return "\"$name\" teleported to \"$target\"\n";
					}else{
						return "Couldn't teleport.\n";
					}
				}else{
					if(!isset($args[3]) and isset($args[2]) and isset($args[1]) and isset($args[0]) and ($issuer instanceof Player)){
						$name = $issuer->username;
						$x = $args[0];
						$y = $args[1];
						$z = $args[2];
					}elseif(isset($args[3]) and isset($args[2]) and isset($args[1]) and isset($args[0])){
						$name = $args[0];
						$x = $args[1];
						$y = $args[2];
						$z = $args[3];
					}else{
						return "Usage: /$cmd [player] <x> <y> <z>\n";
					}
					if($this->tppos($name, $x, $y, $z)){
						return "\"$name\" teleported to ($x, $y, $z)\n";
					}else{
						return "Couldn't teleport.\n";
					}
				}
			case "kill":
			case "suicide":
				if(!isset($args[0])){
					if(!($issuer instanceof Player)) return "Usage: /kill <player>";
					$player = $issuer;
				}else{
					$player = $this->get($args[0]);
				}
				if($player instanceof Player){
					$player->entity->harm(PHP_INT_MAX, "console", true);
					return "Ouch. That looks like it hurt.\n";
				}else{
					return "Usage: /$cmd [player]\n";
				}
			case "list":
				$output .= "There are " . count($this->server->clients) . "/" . $this->server->maxClients . " players online:\n";
				if(count($this->server->clients) == 0){
					break;
				}
				foreach($this->server->clients as $c){
					$output .= $c->username . ", ";
				}
				return substr($output, 0, -2) . "\n";
			case "loc":
				if(!($issuer instanceof Player)){
					if(isset($args[0])){
						goto get_coords_of_others;
					}
					return "Usage: /$cmd <username>.";
				}
				
				if(isset($args[0]) && $this->server->api->ban->isOp($issuer->iusername)){
					get_coords_of_others:
					$p = $this->get($args[0]);
					if(!($p instanceof Player)){
						return "Player with username '{$args[0]}' is not on server.";
					}
					$ent = $p->entity;
					$username = $p->username;
				}else{
					$ent = $issuer->entity;
					$username = $issuer->username;
				}
				
				$x = round($ent->x, 1, PHP_ROUND_HALF_UP);
				$y = round($ent->y, 1, PHP_ROUND_HALF_UP);
				$z = round($ent->z, 1, PHP_ROUND_HALF_UP);
				$level = $ent->level->getName();
				$compass = [0 => "X+", 1 => "Z+", 2 => "X-", 3 => "Z-", null => "null"];
				$direction = $compass[$ent->getDirection()];
				
				$xChunk = $x >> 4;
				$zChunk = $z >> 4;
				$brightness = $ent->level->getRawBrightness((int)$x, (int)$y, (int)$z);
				return "$username's coordinates: X: $x ($xChunk), Y: $y, Z: $z ($zChunk), world: $level.\nDirection: $direction Brightness: $brightness";
		}
		return $output;
	}

	public function get($name, $alike = true, $multiple = false){
		$name = trim(strtolower($name));
		if($name === ""){
			return false;
		}
		$this->server->preparedSQL->player->getEq->reset();
		$this->server->preparedSQL->player->getEq->bindValue(":name", $name);
		$query = $this->server->preparedSQL->player->getEq->execute();
		
		$players = [];
		if($query !== false and $query !== true){
			while(($d = $query->fetchArray(SQLITE3_ASSOC)) !== false){
				$CID = PocketMinecraftServer::clientID($d["ip"], $d["port"]);
				if(isset($this->server->clients[$CID])){
					$players[$CID] = $this->server->clients[$CID];
					if($multiple === false and $d["name"] === $name){
						return $players[$CID];
					}
				}
			}
		}
		if($alike === true){
			$this->server->preparedSQL->player->getLike->reset();
			$query = $this->server->preparedSQL->player->getLike->bindValue(":name", "$name%");
			$query = $this->server->preparedSQL->player->getLike->execute(); //try getting player with non-full name match if none was found
			if($query !== false and $query !== true){
				while(($d = $query->fetchArray(SQLITE3_ASSOC)) !== false){
					$CID = PocketMinecraftServer::clientID($d["ip"], $d["port"]);
					if(isset($this->server->clients[$CID])){
						$players[$CID] = $this->server->clients[$CID];
						if($multiple === false and $d["name"] === $name){
							return $players[$CID];
						}
					}
				}
			}
		}
		
		if($multiple === false){
			if(count($players) > 0){
				return array_shift($players);
			}else{
				return false;
			}
		}else{
			return $players;
		}
	}

	public function teleport(&$name, &$target){
		if(str_starts_with($target, "w:")){
			$lv = $this->server->api->level->get(substr($target, 2));
			if($lv instanceof Level){
				$origin = $this->get($name);
				if($origin instanceof Player){
					$name = $origin->username;
					return $origin->teleport($lv->getSafeSpawn());
				}
			}else{
				return false;
			}
		}
		$player = $this->get($target);
		if(($player instanceof Player) and ($player->entity instanceof Entity)){
			$origin = $this->get($name);
			if($origin instanceof Player){
				$name = $origin->username;
				$target = $player->username;
				return $origin->teleport($player->entity);
			}
		}
		return false;
	}

	public function tppos(&$name, &$x, &$y, &$z){
		$player = $this->get($name);
		if(($player instanceof Player) and ($player->entity instanceof Entity)){
			$name = $player->username;
			$x = $x[0] === "~" ? $player->entity->x + floatval(substr($x, 1)) : floatval($x);
			$y = $y[0] === "~" ? $player->entity->y + floatval(substr($y, 1)) : floatval($y);
			$z = $z[0] === "~" ? $player->entity->z + floatval(substr($z, 1)) : floatval($z);
			$player->teleport(new Vector3($x, $y, $z));
			return true;
		}
		return false;
	}

	public function broadcastPacket(array $players, RakNetDataPacket $packet){
		foreach($players as $p){
			$p->dataPacket(clone $packet);
		}
	}

	public function online(){
		$o = [];
		foreach($this->server->clients as $p){
			if($p->auth === true){
				$o[] = $p->username;
			}
		}
		return $o;
	}

	public function add($CID){
		if(isset($this->server->clients[$CID])){
			$player = $this->server->clients[$CID];
			$player->data = $this->getOffline($player->username);
			$player->gamemode = $player->data->get("gamemode");
			$player->saveInventory = ($player->gamemode & 1) == SURVIVAL;
			if(($player->level = $this->server->api->level->get($player->data->get("position")["level"])) === false){
				$player->level = $this->server->api->level->getDefault();
				$player->data->set("position", [
					"level" => $player->level->getName(),
					"x" => $player->level->getSpawn()->x,
					"y" => $player->level->getSpawn()->y,
					"z" => $player->level->getSpawn()->z,
				]);
			}
			$player->level->players[$CID] = $player;
			$this->server->query("INSERT OR REPLACE INTO players (CID, ip, port, name) VALUES (" . $player->CID . ", '" . $player->ip . "', " . $player->port . ", '" . strtolower($player->username) . "');");
		}
	}

	public function getOffline($name, $create = true){
		$iname = strtolower($name);
		$default = [
			"caseusername" => $name,
			"position" => [
				"level" => $this->server->spawn->level->getName(),
				"x" => $this->server->spawn->x,
				"y" => $this->server->spawn->y,
				"z" => $this->server->spawn->z,
			],
			"spawn" => [
				"level" => $this->server->spawn->level->getName(),
				"x" => $this->server->spawn->x,
				"y" => $this->server->spawn->y,
				"z" => $this->server->spawn->z,
			],
			"inventory" => array_fill(0, PLAYER_SURVIVAL_SLOTS, [AIR, 0, 0]),
			"armor" => array_fill(0, 4, [AIR, 0]),
			"gamemode" => $this->server->gamemode,
			"health" => 20,
			"lastIP" => "",
			"lastID" => 0,
			"achievements" => [],
			"slot-count" => 7,
			"bed-position" => null,
		];
		$ev = true;
		$sav = false;
		if(!file_exists(DATA_PATH . "players/$iname.yml")){
			if(PocketMinecraftServer::$SAVE_PLAYER_DATA && $create){
				console("[NOTICE] Player data not found for \"$iname\", creating new profile");
				$data = new Config(DATA_PATH . "players/$iname.yml", CONFIG_YAML, $default);
				$sav = true;
			}else{
				$data = new Config(DATA_PATH . "players/$iname.yml", CONFIG_YAML, $default);
				$ev = false;
			}
		}else{
			$data = new Config(DATA_PATH . "players/$iname.yml", CONFIG_YAML, $default);
		}
		
		if(($data->get("gamemode") & 0x01) === 1){
			$data->set("health", 20);
		}
		if(!$data->exists("hotbar")){
			if(($data->get("gamemode") & 1) == CREATIVE) $hb = BlockAPI::$creativeHotbarSlots;
			else $hb = [0, 1, 2, 3, 4, 5, 6, 7, 8];
			$data->set("hotbar", $hb);
		}
		if($ev) $this->server->handle("player.offline.get", $data);
		if($sav) $data->save();
		
		return $data;
	}

	public function spawnAllPlayers(Player $player){
		foreach($this->getAll() as $p){
			if($p !== $player and ($p->entity instanceof Entity)){
				$p->entity->spawn($player);
				if($p->level !== $player->level){
					$pk = new MoveEntityPacket_PosRot;
					$pk->eid = $p->entity->eid;
					$pk->x = -256;
					$pk->y = 128;
					$pk->z = -256;
					$pk->yaw = 0;
					$pk->pitch = 0;
					$player->entityQueueDataPacket($pk);
				}
			}
		}
	}

	public function getAll($level = null){
		if($level instanceof Level){
			//safe_var_dump($level->players);
			return $level->players;

		}
		return $this->server->clients;
	}

	public function getByEID($eid){
		$eid = (int) $eid;
		$CID = $this->server->query("SELECT ip,port FROM players WHERE EID = '" . $eid . "';", true);
		$CID = is_array($CID) ? PocketMinecraftServer::clientID($CID["ip"], $CID["port"]) : false;
		if(isset($this->server->clients[$CID])){
			return $this->server->clients[$CID];
		}
		return false;
	}

	public function spawnToAllPlayers(Player $player){
		foreach($this->getAll() as $p){
			if($p->spawned && $p !== $player && ($p->entity instanceof Entity) && ($player->entity instanceof Entity)){
				$player->entity->spawn($p);
				if($p->level !== $player->level){
					$pk = new MoveEntityPacket_PosRot;
					$pk->eid = $player->entity->eid;
					$pk->x = -256;
					$pk->y = 128;
					$pk->z = -256;
					$pk->yaw = 0;
					$pk->pitch = 0;
					$p->entityQueueDataPacket($pk);
				}
			}
		}
	}

	public function remove($CID){
		if(isset($this->server->clients[$CID])){
			$player = $this->server->clients[$CID];
			unset($this->server->clients[$CID]);
			$player->close();
			if($player->username != "" and ($player->data instanceof Config)){
				$this->saveOffline($player->data);
			}
			
			$this->server->preparedSQL->player->deleteCID->reset();
			$this->server->preparedSQL->player->deleteCID->bindValue(":CID", $CID);
			$this->server->preparedSQL->player->getEq->execute();
			
			$this->server->api->entity->remove($player->eid);
			unset($player->level->players[$player->CID]);
			if($player->entity instanceof Entity){
				unset($player->entity->player);
				//unset($player->entity);
			}
			
			$player = null;
			unset($player);
		}
	}

	public function saveOffline(Config $data){
		if(PocketMinecraftServer::$SAVE_PLAYER_DATA){
			$this->server->handle("player.offline.save", $data);
			$data->save();
		}
	}
}
