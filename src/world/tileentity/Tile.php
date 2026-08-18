<?php

class Tile extends Position{
	/**
	 * TileEntity id to TileEntity class name converter.
	 * If id is not defined here, the server will create base Tile class
	 */
	public static $tileId2tileClass = [
		TILE_SIGN => "SignTileEntity",
		TILE_CHEST => "ChestTileEntity",
		TILE_FURNACE => "FurnaceTileEntity"
		//TODO nether reactor tileentity
	];
	
	/**
	 * Numeric tileentity id. Should not be modified
	 * @var int
	 */
	public $id;
	public $class;

	public $name;
	public $normal;
	public $data;
	public $attach;
	public $metadata;
	public $closed;
	
	public $scheduledUpdate, $lastUpdate;
	
	protected $server;
	public function __construct(Level $level, $id, $class, $x, $y, $z, $data = []){
		$this->server = ServerAPI::request();
		$this->level = $level;
		$this->normal = true;
		$this->class = $class;
		$this->data = $data;
		$this->closed = false;
		if($class === false){
			$this->closed = true;
		}
		$this->name = "";
		$this->lastUpdate = microtime(true);
		$this->scheduledUpdate = false;
		$this->id = (int) $id;
		$this->x = (int) $x;
		$this->y = (int) $y;
		$this->z = (int) $z;
	}
	
	/**
	 * @return array
	 */
	public function getSaveData(){
		return [
			"id" => $this->id,
			"x" => $this->x,
			"y" => $this->y,
			"z" => $this->z
		];
	}

	public function update(){
		if($this->closed === true) return false;
		
		$this->server->handle("tile.update", $this);
		$this->lastUpdate = microtime(true);
	}

	public function getSlot($s){
		$i = $this->getSlotIndex($s);
		if($i === false or $i < 0){
			return BlockAPI::getItem(AIR, 0, 0);
		}else{
			return BlockAPI::getItem($this->data["Items"][$i]["id"], $this->data["Items"][$i]["Damage"], $this->data["Items"][$i]["Count"]);
		}
	}

	public function getSlotIndex($s){
		if($this->class !== TILE_CHEST and $this->class !== TILE_FURNACE){
			return false;
		}
		foreach($this->data["Items"] as $i => $slot){
			if($slot["Slot"] === $s){
				return $i;
			}
		}
		return -1;
	}

	public function setSlot($s, Item $item, $update = true, $offset = 0){
		$i = $this->getSlotIndex($s);
		$d = [
			"Count" => $item->count,
			"Slot" => $s,
			"id" => $item->getID(),
			"Damage" => $item->getMetadata(),
		];
		if($i === false){
			return false;
		}elseif($item->getID() === AIR or $item->count <= 0){
			if($i >= 0){
				unset($this->data["Items"][$i]);
			}
		}elseif($i < 0){
			$this->data["Items"][] = $d;
		}else{
			$this->data["Items"][$i] = $d;
		}
		$this->server->api->dhandle("tile.container.slot", [
			"tile" => $this,
			"slot" => $s,
			"offset" => $offset,
			"slotdata" => $item,
		]);

		if($update === true and $this->scheduledUpdate === false){
			$this->update();
		}
		return true;
	}

	public function pairWith(Tile $tile){
		if($this->isPaired() or $tile->isPaired()){
			return false;
		}

		$this->data["pairx"] = $tile->x;
		$this->data["pairz"] = $tile->z;

		$tile->data["pairx"] = $this->x;
		$tile->data["pairz"] = $this->z;

		$this->server->api->tile->spawnToAll($this);
		$this->server->api->tile->spawnToAll($tile);
		$this->server->handle("tile.update", $this);
		$this->server->handle("tile.update", $tile);
	}

	public function isPaired(){
		if($this->class !== TILE_CHEST){
			return false;
		}
		if(!isset($this->data["pairx"]) or !isset($this->data["pairz"])){
			return false;
		}
		return true;
	}

	public function unpair(){
		if(!$this->isPaired()){
			return false;
		}

		$tile = $this->getPair();
		unset($this->data["pairx"], $this->data["pairz"], $tile->data["pairx"], $tile->data["pairz"]);

		$this->server->api->tile->spawnToAll($this);
		$this->server->handle("tile.update", $this);
		if($tile instanceof Tile){
			$this->server->api->tile->spawnToAll($tile);
			$this->server->handle("tile.update", $tile);
		}
	}

	public function getPair(){
		if($this->isPaired()){
			return $this->server->api->tile->get(new Position((int) $this->data["pairx"], $this->y, (int) $this->data["pairz"], $this->level));
		}
		return false;
	}

	public function openInventory(Player $player){}
	public function getSpawnPacket(){
		return null;
	}
	
	public function spawn($player){
		if($this->closed){
			return false;
		}
		if(!($player instanceof Player)) $player = $this->server->api->player->get($player);
		$packet = $this->getSpawnPacket();
		if($packet instanceof RakNetDataPacket){
			$player->blockQueueDataPacket($packet);
		}
	}

	public function setText($line1 = "", $line2 = "", $line3 = "", $line4 = ""){
		if($this->class !== TILE_SIGN){
			return false;
		}
		if(Level::$disableEmojisOnSigns){
			$line1 = Utils::replaceEmoji($line1);
			$line2 = Utils::replaceEmoji($line2);
			$line3 = Utils::replaceEmoji($line3);
			$line4 = Utils::replaceEmoji($line4);
			
		}
		$this->data["Text1"] = $line1;
		$this->data["Text2"] = $line2;
		$this->data["Text3"] = $line3;
		$this->data["Text4"] = $line4;
		$this->server->api->tile->spawnToAll($this);
		$this->server->handle("tile.update", $this);
		return true;
	}

	public function getText(){
		return [
			$this->data["Text1"],
			$this->data["Text2"],
			$this->data["Text3"],
			$this->data["Text4"]
		];
	}

	public function __destruct(){
		$this->close();
	}

	public function close(){
		if($this->closed === false){
			$this->closed = true;
			$this->server->api->tile->remove($this->id);
		}
	}

	public function getName(){
		return $this->name;
	}


	/**
	 * @deprecated Should not be used
	 * @param Vector3|Position $pos
	 */
	public function setPosition(Vector3 $pos){
		if($pos instanceof Position && $pos->level != $this->level){
			$cX = floor($this->x) >> 4;
			$cZ = floor($this->z) >> 4;
			unset($this->level->tileEntityList[$this->id]);
			unset($this->level->tileEntityListPositioned["$cX $cZ"][$this->id]);
			$this->level = $pos->level;
			$this->level->tileEntityList[$this->id] = $this;
		}else{
			$cX = floor($this->x) >> 4;
			$cZ = floor($this->z) >> 4;
			unset($this->level->tileEntityListPositioned["$cX $cZ"][$this->id]);
		}
			
		$nCX = floor($pos->x) >> 4;
		$nCZ = floor($pos->z) >> 4;
		$this->level->tileEntityListPositioned["$nCX $nCZ"][$this->id] = $this;
		
		$this->x = (int) $pos->x;
		$this->y = (int) $pos->y;
		$this->z = (int) $pos->z;
	}
}
