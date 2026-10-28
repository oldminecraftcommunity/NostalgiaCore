<?php

class LadderBlock extends TransparentBlock{
	public static $blockID;
	public static function getAABB(Level $level, $x, $y, $z){
		[$id, $meta] = $level->level->getBlock($x, $y, $z);
		
		switch($meta){
			case 2:
				StaticBlock::setBlockBounds($id, 0.0, 0.0, 0.875, 1.0, 1.0, 1.0);
				break;
			case 3:
				StaticBlock::setBlockBounds($id, 0, 0.0, 0.0, 1.0, 1.0, 0.125);
				break;
			case 4:
				StaticBlock::setBlockBounds($id, 0.875, 0.0, 0.0, 1.0, 1.0, 1.0);
				break;
			case 5:
				StaticBlock::setBlockBounds($id, 0, 0.0, 0.0, 0.125, 1.0, 1.0);
				break;
				
		}
		
		return parent::getAABB($level, $x, $y, $z);
	}
	
	public static function getCollisionBoundingBoxes(Level $level, $x, $y, $z, Entity $entity){
		return [static::getAABB($level, $x, $y, $z)];
	}
	
	public function __construct($meta = 0){
		parent::__construct(LADDER, $meta, "Ladder");
		$this->isSolid = false;
		$this->isFullBlock = false;
		$this->hardness = 2;
		$this->breakTime = 0.4;
		$this->material = Material::$decoration;
	}
	
	public function getPlacementDataValue(Level $level, $x, $y, $z, $side){
		$v = 0; //getLevelDataForAuxValue($item_aux_value); - should be 0
		if(($v == 0 || $side == 2) && $level->isSolidBlockingTile($x, $y, $z + 1)) $v = 2;
		if(($v == 0 || $side == 3) && $level->isSolidBlockingTile($x, $y, $z - 1)) $v = 3;
		if(($v == 0 || $side == 4) && $level->isSolidBlockingTile($x + 1, $y, $z)) $v = 4;
		if(($v == 0 || $side == 5) && $level->isSolidBlockingTile($x - 1, $y, $z)) $v = 5;
		return $v; //should never happen due to previous check in ::place
	}
	
	public function place(Item $item, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		$x = $block->x;
		$y = $block->y;
		$z = $block->z;
		$level = $block->level;
		if($level->isSolidBlockingTile($x - 1, $y, $z) || $level->isSolidBlockingTile($x + 1, $y, $z) || $level->isSolidBlockingTile($x, $y, $z - 1) || $level->isSolidBlockingTile($x, $y, $z+1)){
			$this->meta = static::getPlacementDataValue($level, $x, $y, $z, $face);
			$this->level->setBlock($block, $this, true, false, true);
			return true;
		}
		
		return false;
	}

	public static function neighborChanged(Level $level, $x, $y, $z, $nX, $nY, $nZ, $oldID){
		$side = $level->level->getBlockDamage($x, $y, $z);
		
		$attached = match($side){
			3 => $level->level->getBlockID($x, $y, $z - 1),
			2 => $level->level->getBlockID($x, $y, $z + 1),
			5 => $level->level->getBlockID($x - 1, $y, $z),
			4 => $level->level->getBlockID($x + 1, $y, $z),
			default => 0 //TODO
		};
		
		if($attached == AIR){ //Replace with common break method
			ServerAPI::request()->api->entity->drop(new Position($x, $y, $z, $level), BlockAPI::getItem(LADDER, 0, 1));
			$level->fastSetBlockUpdate($x, $y, $z, 0, 0, true);
		}
	}

	public function getDrops(Item $item, Player $player){
		return array(
			array($this->id, 0, 1),
		);
	}		
}
