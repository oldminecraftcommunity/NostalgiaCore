<?php

class BaseMushroomBlock extends FlowableBlock{
	public function __construct($id, $meta, $name){
		parent::__construct($id, $meta, $name);
		$this->hardness = 0;
		$this->breakTime = 0;
		$this->material = Material::$plant;
	}
	
	public static function neighborChanged(Level $level, $x, $y, $z, $nX, $nY, $nZ, $oldID){
		if(StaticBlock::getIsTransparent($level->level->getBlockID($x, $y - 1, $z))){ //Replace with common break method
			ServerAPI::request()->api->entity->drop(new Position($x+0.5, $y, $z+0.5, $level), BlockAPI::getItem(static::$blockID, 0, 1));
			$level->fastSetBlockUpdate($x, $y, $z, 0, 0);
		}
	}
	
	public static function canSurvive(Level $level, $x, $y, $z){
		return $level->getRawBrightness($x, $y, $z) <= 12 && StaticBlock::getIsSolid($level->level->getBlockID($x, $y-1, $z));
	}

	public static function onRandomTick(Level $level, $x, $y, $z){
		if(Block::$mushroomSpread && mt_rand(0, 24) == 0){
			$maxcnt = 5;
			for($xx = $x-4; $xx <= $x+4; ++$xx){
				for($zz = $z-4; $zz <= $z+4; ++$zz){
					for($yy = $y-1; $yy <= $y+1; ++$yy){
						if($level->level->getBlockID($xx, $yy, $zz) == static::$blockID){
							if(--$maxcnt <= 0) return;
						}
					}
				}
			}
			
			for($att = 0; $att < 4; ++$att){
				$nx = $x + mt_rand(-1, 1);
				$ny = $y + mt_rand(-1, 1);
				$nz = $z + mt_rand(-1, 1);
				if($ny < 127 && $ny > 0 && $level->level->getBlockID($nx, $ny, $nz) == 0){
					$idb = $level->level->getBlockID($nx, $ny-1, $nz);
					if(!StaticBlock::getIsTransparent($idb)){ //TODO better canSurvive check
						if($level->getRawBrightness($nx, $ny, $nz) <= 12){
							$level->fastSetBlockUpdate($nx, $ny, $nz, static::$blockID, 0);
							return;
						}
					}
				}
			}
		}
	}
	
	public function place(Item $item, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		if($block->getID() == 0 || $block->material->replaceable){ //Tile::mayPlace
			if(static::canSurvive($block->level, $block->x, $block->y, $block->z)){
				$this->level->setBlock($block, $this, true, false, true);
				return true;
			}
		}
	}
}