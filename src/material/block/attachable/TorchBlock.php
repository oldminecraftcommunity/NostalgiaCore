<?php

class TorchBlock extends FlowableBlock implements LightingBlock{
	public static $blockID;
	public function __construct($meta = 0){
		parent::__construct(TORCH, $meta, "Torch");
		$this->hardness = 0;
		$this->breakTime = 0;
		$this->material = Material::$decoration;
		$this->lightEmission = 14;
	}
	
	public function getMaxLightValue(){
		return 15;
	}
	
	public static function getAABB(Level $level, $x, $y, $z){
		return null;
	}
	
	public static function neighborChanged(Level $level, $x, $y, $z, $nX, $nY, $nZ, $oldID){
		if(static::checkCanSurvive($level, $x, $y, $z)){
			$data = $level->level->getBlockDamage($x, $y, $z);
			$destroy = !$level->isSolidBlockingTile($x-1, $y, $z) && $data == 1;
			if(!$level->isSolidBlockingTile($x+1, $y, $z) && $data == 2) $destroy = true;
			if(!$level->isSolidBlockingTile($x, $y, $z-1) && $data == 3) $destroy = true;
			if(!$level->isSolidBlockingTile($x, $y, $z+1) && $data == 4) $destroy = true;
			if((!static::isConnection($level, $x, $y-1, $z) && $data == 5) || $destroy){
				ServerAPI::request()->api->entity->drop(new Position($x, $y, $z, $level), BlockAPI::getItem(TORCH, 0, 1));
				$level->fastSetBlockUpdate($x, $y, $z, 0, 0);
			}
		}
	}

	public static function isConnection(Level $level, $x, $y, $z){
		if($level->isSolidBlockingTile($x, $y, $z)){
			return true;
		}
		$id = $level->level->getBlockID($x, $y, $z);
		return $id == FENCE || $id == GLASS || $id == COBBLE_WALL;
	}
	
	public static function getPlacementDataValue(Level $level, $x, $y, $z, $side){
		$def = 0;
		if($side == 1){
			if(static::isConnection($level, $x, $y-1, $z)) return 5;
		}else if($side == 2){
			if($level->isSolidBlockingTile($x, $y, $z+1)) return 4;
		}
		
		if($side == 5){
			if($level->isSolidBlockingTile($x-1, $y, $z)) return 1;
		}else if($side == 3){
			if($level->isSolidBlockingTile($x, $y, $z-1)) return 3;
		}else if($side == 4){
			if($level->isSolidBlockingTile($x+1, $y, $z)) return 2;
		}
		return $def;
		
	}
	
	public static function checkCanSurvive(Level $level, $x, $y, $z){
		if(static::mayPlace($level, $x, $y, $z)) return true;
		if($level->level->getBlockID($x, $y, $z) == TORCH){
			ServerAPI::request()->api->entity->drop(new Position($x, $y, $z, $level), BlockAPI::getItem(TORCH, 0, 1));
			$level->fastSetBlockUpdate($x, $y, $z, 0, 0);
		}
		return false;
	}
	
	public static function onPlace(Level $level, $x, $y, $z){
		if($level->level->getBlockDamage($x, $y, $z) == 0){
			if($level->isSolidBlockingTile($x-1, $y, $z)) $level->fastSetBlockUpdateMeta($x, $y, $z, 1);
			if($level->isSolidBlockingTile($x+1, $y, $z)) $level->fastSetBlockUpdateMeta($x, $y, $z, 2);
			if($level->isSolidBlockingTile($x, $y, $z-1)) $level->fastSetBlockUpdateMeta($x, $y, $z, 3);
			if($level->isSolidBlockingTile($x, $y, $z+1)) $level->fastSetBlockUpdateMeta($x, $y, $z, 4);
			if(static::isConnection($level, $x, $y-1, $z)) $level->fastSetBlockUpdateMeta($x, $y, $z, 5);
		}
		static::checkCanSurvive($level, $x, $y, $z);
	}

	public static function mayPlace(Level $level, $x, $y, $z){
		return $level->isSolidBlockingTile($x - 1, $y, $z) || $level->isSolidBlockingTile($x + 1, $y, $z) || $level->isSolidBlockingTile($x, $y, $z - 1) || $level->isSolidBlockingTile($x, $y, $z+1) || static::isConnection($level, $x, $y-1, $z);
	}
	
	public function place(Item $item, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		$x = $block->x;
		$y = $block->y;
		$z = $block->z;
		$level = $block->level;
		if(static::mayPlace($level, $x, $y, $z)){
			$this->meta = static::getPlacementDataValue($level, $x, $y, $z, $face);
			$this->level->setBlock($block, $this, true, false, true);
			return true;
		}
		return false;
	}
	
	
	
	public function getDrops(Item $item, Player $player){
		return array(
			array($this->id, 0, 1),
		);
	}
}