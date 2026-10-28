<?php

class MobSpawner{
	public static $maxMobsNearPlayerAtOnce = 10;
	private $server;
	public $level;
	public $totalMobsPerPlayer;
	public $playerAffectedEIDS = [];
	public $entityAffectedPlayers = [];
	
	public function __construct(Level $level){
		$this->server = ServerAPI::request();
		$this->level = $level;
	}

	public function countEntities(){
		return $this->level->totalMobsAmount;
	}
	
	public function checkDespawn(Living $living){
		if(!isset($this->entityAffectedPlayers[$living->eid])){
			return false;
		}
		
		$playerID = $this->entityAffectedPlayers[$living->eid];
		$player = $this->level->entityList[$playerID] ?? false;
		if($player === false){
			//TODO try retargetting other player?
			return true;
		}else{
			$diffX = $living->x - $player->x;
			$diffZ = $living->z - $player->z;
			$dist = $diffX*$diffX + $diffZ*$diffZ;
			if($dist <= 512){
				return false;
			}
			
			return $dist > 4096 || mt_rand($dist, 4096) == $dist; //force despawn 64 blocks away or despawn randomly
		}
		
	}
	
	public function handle(){
		$limit = $this->level->getProperty("mobs-amount");
		
		if($this->countEntities() > $limit || count($this->level->players) <= 0){
			return false; //not spawning
		}
		$svd = $this->totalMobsPerPlayer;
		$this->totalMobsPerPlayer = min(ceil($limit / count($this->level->players)), self::$maxMobsNearPlayerAtOnce);
		if($svd != $this->totalMobsPerPlayer) ConsoleAPI::debug("Changed total mobs per player from $svd to {$this->totalMobsPerPlayer}.");
		
		return $this->spawnMobs();
	}

	public function spawnMobs(){
		$phase = $this->server->api->time->getPhase($this->level);
		if($this->level->getProperty("spawn-animals") && ($phase == "day" || $phase == "sunrise")){ //Animal
			$type = mt_rand(10, 13);
			$baby = false;
			$grassOnly = true;
		}elseif($this->level->getProperty("spawn-monsters") && ($phase == "night" || $phase == "sunset") && $this->server->difficulty > 0){ //Monster, true night
			$type = mt_rand(32, 35);
			$grassOnly = false;
			$baby = 2;
		}else{
			return false;
		}
		
		foreach($this->level->players as $player){
			if(isset($this->playerAffectedEIDS[$player->entity->eid]) && count($this->playerAffectedEIDS[$player->entity->eid]) > $this->totalMobsPerPlayer){
				continue;
			}
			
			$x = mt_rand(floor($player->entity->x - 32), ceil($player->entity->x + 32));
			$z = mt_rand(floor($player->entity->z - 32), ceil($player->entity->z + 32));
			$diffX = $x-$player->entity->x;
			$diffZ = $z-$player->entity->z;
			$dist = $diffX*$diffX + $diffZ*$diffZ;
			if($dist < 768){
				continue;
			}
			
			$cnt = mt_rand(1, 3);
			
			for($i = 0; $i < $cnt; ++$i){
				
				$xMob = $x + mt_rand(-3, 3);
				$zMob = $z + mt_rand(-3, 3);
				
				
				$y = $this->getSafeY($xMob, $zMob, $grassOnly, $type >= 32 && $type <= 36 && $type != 35, $type >= 32 && $type <= 36);
				if(!$y || $y < 0){
					continue;
				}
				
				$data = $this->genPosData($xMob, $y + 0.5, $zMob);
				if($baby != 2) $data["IsBaby"] = $baby;
				
				$e = $this->server->api->entity->add($this->level, 2, $type, $data);
				
				if($e instanceof Entity){
					$this->server->api->entity->spawnToAll($e);
					ConsoleAPI::debug("$type spawned at $xMob, $y, $zMob");
				}
				if(!isset($this->playerAffectedEIDS[$player->entity->eid])){
					$this->playerAffectedEIDS[$player->entity->eid] = [$e->eid => true];
				}else{
					$this->playerAffectedEIDS[$player->entity->eid][$e->eid] = true;
				}
				
				$this->entityAffectedPlayers[$e->eid] = $player->entity->eid;
				
			}
		}
		
		
		return true;
	}
	
	private function genPosData($x, $y, $z){
		return [
			"x" => $x + 0.5,
			"y" => $y,
			"z" => $z + 0.5
		];
	}
	
	protected function getSafeY($x, $z, $grassOnly = false, $highMob = false, $isMonster = false){ //first safe block //TODO check boundingbox
		$allowed = [];
		for($y = 0; $y < 128; ++$y){
			$b = $this->level->level->getBlockID($x, $y, $z);
			$b2 = $this->level->level->getBlockID($x, $y + 1, $z);
			$b1 = $this->level->level->getBlockID($x, $y - 1, $z);
			if(
				!StaticBlock::getIsSolid($b) && !StaticBlock::getIsLiquid($b) && 
				(StaticBlock::getIsSolid($b1) && (!$grassOnly || $b1 === GRASS) &&
				(!$highMob || !StaticBlock::getIsSolid($b2) && !StaticBlock::getIsLiquid($b2)))
			){
				if($isMonster && ($rb = $this->level->getRawBrightness($x, $y, $z)) > 8){ //dont spawn if too bright
					continue;
				}
				$allowed[] = $y;
			}
		}
		
		return empty($allowed) ? -1 : $allowed[mt_rand(0, count($allowed) - 1)];
	}
	/**
	 * @deprecated
	 * use $level->getProperty("mobs-amount")
	 */
	public static $MOB_LIMIT = 50;
	/**
	 * @deprecated
	 * use $level->getProperty("spawn-animals") and $level->getProperty("spawn-monsters")
	 */
	public static $spawnAnimals = true, $spawnMobs = true;
}

