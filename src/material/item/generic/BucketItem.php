<?php

class BucketItem extends Item{
	private static $possiblenames = array(
		0 => "Bucket",
		1 => "Milk Bucket",
		8 => "Water Bucket",
		10 => "Lava Bucket"
	);
	public function __construct($meta = 0, $count = 1){
		parent::__construct(BUCKET, $meta, $count, "Bucket");
		$this->isActivable = true;
		$this->maxStackSize = 16;
		$this->name = BucketItem::$possiblenames[$this->meta];
	}
	
	public function getMaxStackSize(){
		if($this->getMetadata() == 0) return 16;
		return 1;
	}
	
	public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		if($this->meta === AIR){
			if($target instanceof LiquidBlock && $target->getMetadata() == 0){
				$level->setBlock($target, new AirBlock(), true, false, true);
				if(($player->gamemode & 0x01) === 0){
					$meta = match($target->getID()){
						WATER, STILL_WATER => WATER,
						LAVA, STILL_LAVA => LAVA,
						default => 0
					};
					if($this->count <= 1){
						$this->meta = $meta;
					}else{
						--$this->count;
						$player->addItem(BUCKET, $meta, 1);
					}
				}
				return true;
			}
		}elseif($this->meta === WATER){
			if($block->getID() === AIR || $block->getID() === TORCH || $block instanceof LiquidBlock){
				$water = new WaterBlock();
				$level->setBlock($block, $water, true, false, true);
				//$water->place($this, $player, $block, $target, $face, $fx, $fy, $fz);
				if(($player->gamemode & 0x01) === 0){
					if($this->count <= 1){
						$this->meta = 0;
					}else{
						--$this->count;
						$player->addItem(BUCKET, $meta, 0);
					}
				}
				return true;
			}
		}elseif($this->meta === LAVA){
			if($block->getID() === AIR || $block->getID() === TORCH || $block instanceof LiquidBlock){
				$lava = new LavaBlock();
				$level->setBlock($block, $lava, true, false, true);
				//$lava->place(clone $this, $player, $block, $target, $face, $fx, $fy, $fz);
				if(($player->gamemode & 0x01) === 0){
					if($this->count <= 1){
						$this->meta = 0;
					}else{
						--$this->count;
						$player->addItem(BUCKET, $meta, 0);
					}
				}
				return true;
			}
		}
		return false;
	}
}
