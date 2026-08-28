<?php

class TileAPI{

	private $server;
	/**
	 * @var Tile[]
	 */
	private $tiles;
	private $tCnt = 1;

	function __construct(){
		$this->tiles = [];
		$this->server = ServerAPI::request();
	}
	public function getXYZ(Level $level, int $x, int $y, int $z){
		$cX = ($x >> 4);
		$cZ = ($z >> 4);
		foreach($level->tileEntityListPositioned["$cX $cZ"] ?? [] as $tile){
			if($tile->x == $x && $tile->y == $y && $tile->z == $z) return $tile;
		}
		return false;
	}
	
	public function invalidateAll(Level $level, int $x, int $y, int $z){
		$cX = ($x >> 4);
		$cZ = ($z >> 4);
		$invcnt = 0;
		foreach($level->tileEntityListPositioned["$cX $cZ"] ?? [] as $tile){
			if($tile->x == $x && $tile->y == $y && $tile->z == $z){
				++$invcnt;
				$tile->close();
			}
			if($invcnt > 1){
				ConsoleAPI::warn("{$level->getName()}: ($x $y $z) has more than 1 tile entity! Invalidated ID {$tile->id} (Total invaliated: $invcnt)");
			}
		}
	}
	
	public function get(Position $pos){
		return $this->getXYZ($pos->level, $pos->x, $pos->y, $pos->z);
	}

	public function getByID($id){
		if($id instanceof Tile){
			return $id;
		}elseif(isset($this->tiles[$id])){
			return $this->tiles[$id];
		}
		return false;
	}

	public function init(){

	}

	public function addSign(Level $level, $x, $y, $z, $lines = ["", "", "", ""]){
		return $this->add($level, TILE_SIGN, $x, $y, $z, $data = [
			"id" => "Sign",
			"x" => $x,
			"y" => $y,
			"z" => $z,
			"Text1" => $lines[0],
			"Text2" => $lines[1],
			"Text3" => $lines[2],
			"Text4" => $lines[3],
		]);
	}

	public function add(Level $level, $class, $x, $y, $z, $data = []){
		$id = $this->tCnt++;
		$clz = Tile::$tileId2tileClass[$class] ?? "Tile";
		$this->tiles[$id] = $t = new $clz($level, $id, $class, $x, $y, $z, $data);
		$cX = floor($t->x) >> 4;
		$cZ = floor($t->z) >> 4;
		$t->level->tileEntityList[$id] = $t;
		$t->level->tileEntityListPositioned["$cX $cZ"][$id] = $t;
		
		$this->spawnToAll($this->tiles[$id]);
		return $this->tiles[$id];
	}

	public function spawnToAll(Tile $t){
		foreach($this->server->api->player->getAll($t->level) as $player){
			if($player->eid !== false){
				$t->spawn($player);
			}
		}
	}

	public function spawnAll(Player $player){
		foreach($this->getAll($player->level) as $t){
			$t->spawn($player);
		}
	}

	public function getAll($level = null){
		if($level instanceof Level){
			return $level->tileEntityList;
		}
		return $this->tiles;
	}

	public function remove($id){
		if(isset($this->tiles[$id])){
			$t = $this->tiles[$id];
			$this->tiles[$id] = null;
			unset($this->tiles[$id]);
			if($t->level instanceof Level){
				$cX = floor($t->x >> 4);
				$cZ = floor($t->z >> 4);
				unset($t->level->tileEntityList[$id]);
				unset($t->level->tileEntityListPositioned["$cX $cZ"][$id]);
			}
			$t->closed = true;
			$t->close();
			$this->server->api->dhandle("tile.remove", $t);
			$t = null;
			unset($t);
		}
	}
}