<?php

class FurnaceTileEntity extends Tile
{
	public function __construct(Level $level, $id, $class, $x, $y, $z, $data = []){
		parent::__construct($level, $id, $class, $x, $y, $z, $data);
		
		if(!isset($this->data["BurnTime"]) or $this->data["BurnTime"] < 0){
			$this->data["BurnTime"] = 0;
		}
		if(!isset($this->data["CookTime"]) or $this->data["CookTime"] < 0 or ($this->data["BurnTime"] === 0 and $this->data["CookTime"] > 0)){
			$this->data["CookTime"] = 0;
		}
		if(!isset($this->data["MaxTime"])){
			$this->data["MaxTime"] = $this->data["BurnTime"];
			$this->data["BurnTicks"] = 0;
		}
		if($this->data["BurnTime"] > 0){
			$this->update();
		}
	}
	
	public function openInventory(Player $player){
		$player->windowCnt++;
		$player->windowCnt = $id = max(2, $player->windowCnt % 99);
		$player->windows[$id] = $this;
		
		$pk = new ContainerOpenPacket;
		$pk->windowid = $id;
		$pk->type = WINDOW_FURNACE;
		$pk->slots = FURNACE_SLOTS;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$player->blockQueueDataPacket($pk);
		
		$slots = [];
		for($s = 0; $s < FURNACE_SLOTS; ++$s){
			$slot = $this->getSlot($s);
			if($slot->getID() > AIR and $slot->count > 0){
				$slots[] = $slot;
			}else{
				$slots[] = BlockAPI::getItem(AIR, 0, 0);
			}
		}
		$pk = new ContainerSetContentPacket;
		$pk->windowid = $id;
		$pk->slots = $slots;
		$player->blockQueueDataPacket($pk);
		return true;
	}
	
	public function update(){
		if($this->closed === true) return false;
		
		$fuel = $this->getSlot(1);
		$raw = $this->getSlot(0);
		$product = $this->getSlot(2);
		$smelt = $raw->getSmeltItem();
		$canSmelt = ($smelt !== false and $raw->count > 0 and (($product->getID() === $smelt->getID() and $product->getMetadata() === $smelt->getMetadata() and $product->count < $product->getMaxStackSize()) or $product->getID() === AIR));
		if($this->data["BurnTime"] <= 0 and $canSmelt and $fuel->getFuelTime() !== false and $fuel->count > 0){
			$this->lastUpdate = microtime(true);
			$this->data["MaxTime"] = $this->data["BurnTime"] = floor($fuel->getFuelTime() * 20);
			$this->data["BurnTicks"] = 0;
			--$fuel->count;
			if($fuel->count === 0){
				$fuel = BlockAPI::getItem(AIR, 0, 0);
			}
			$this->setSlot(1, $fuel, false);
			$current = $this->level->getBlock($this);
			if($current->getID() === FURNACE){
				$this->level->setBlock($this, BlockAPI::get(BURNING_FURNACE, $current->getMetadata()), true, false, true);
			}
		}
		if($this->data["BurnTime"] > 0){
			$ticks = (microtime(true) - $this->lastUpdate) * 20;
			$this->data["BurnTime"] -= $ticks;
			$this->data["BurnTicks"] = ceil(($this->data["BurnTime"] / $this->data["MaxTime"]) * 200);
			if($smelt !== false and $canSmelt){
				$this->data["CookTime"] += $ticks;
				if($this->data["CookTime"] >= 200){ //10 seconds
					$product = BlockAPI::getItem($smelt->getID(), $smelt->getMetadata(), $product->count + 1);
					$this->setSlot(2, $product, false);
					--$raw->count;
					if($raw->count === 0){
						$raw = BlockAPI::getItem(AIR, 0, 0);
					}
					$this->setSlot(0, $raw, false);
					$this->data["CookTime"] -= 200;
				}
			}elseif($this->data["BurnTime"] <= 0){
				$this->data["BurnTime"] = 0;
				$this->data["CookTime"] = 0;
				$this->data["BurnTicks"] = 0;
			}else{
				$this->data["CookTime"] = 0;
			}
			
			$this->server->schedule(2, [$this, "update"]);
			$this->scheduledUpdate = true;
		}else{
			$current = $this->level->getBlock($this);
			if($current->getID() === BURNING_FURNACE){
				$this->level->setBlock($this, BlockAPI::get(FURNACE, $current->getMetadata()), true, false, true);
			}
			$this->data["CookTime"] = 0;
			$this->data["BurnTime"] = 0;
			$this->data["BurnTicks"] = 0;
			$this->scheduledUpdate = false;
		}
		parent::update();
	}
}

