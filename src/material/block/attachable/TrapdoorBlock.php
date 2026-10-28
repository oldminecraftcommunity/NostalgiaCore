<?php

class TrapdoorBlock extends TransparentBlock{
	public static $blockID;
	public function __construct($meta = 0){
		parent::__construct(TRAPDOOR, $meta, "Trapdoor");
		$this->isActivable = true;
		if(($this->meta & 0x04) === 0x04){
			$this->isFullBlock = false;
		}else{
			$this->isFullBlock = true;
		}
		$this->hardness = 15;
		$this->breakTime = 3;
		$this->material = Material::$wood;
	}
	
	public static function attachesTo($id){
		if($id <= 0) return false;
		
		//check for non existent tiles is here - not needed?
		//image using tile->getRenderShape() == 10 to check is stairs </3
		return StaticBlock::getMaterial($id)->isSolidBlocking() && StaticBlock::getIsCubeShaped($id) || $id == GLOWSTONE || $id == SLAB || $id == WOODEN_SLAB || (StaticBlock::getBlock($id) instanceof StairBlock);
	}
	
	public static function isOpen($meta){
		return ($meta >> 2) & 1;
	}
	
	public static function getAABB(Level $level, $x, $y, $z){
		static::updateShape($level, $x, $y, $z);
		return StaticBlock::getAABB(static::$blockID, $x, $y, $z);
	}
	
	public static function getCollisionBoundingBoxes(Level $level, $x, $y, $z, Entity $entity){
		return [static::getAABB($level, $x, $y, $z)];
	}
	
	public static function updateShape(Level $level, $x, $y, $z){
		[$id, $meta] = $level->level->getBlock($x, $y, $z);
		
		StaticBlock::setBlockBounds($id, 0.0, 0.0, 0.0, 1.0, 0.1875, 1.0);
		
		if(static::isOpen($meta)){
			$facing = $meta & 3;
			switch($facing){
				case 0:
					StaticBlock::setBlockBounds($id, 0.0, 0.0, 0.8125, 1.0, 1.0, 1.0);
					break;
				case 1:
					StaticBlock::setBlockBounds($id, 0.0, 0.0, 0.0, 1.0, 1.0, 0.1875);
					break;
				case 2:
					StaticBlock::setBlockBounds($id, 0.8125, 0.0, 0.0, 1.0, 1.0, 1.0);
					break;
				case 3:
					StaticBlock::setBlockBounds($id, 0.0, 0.0, 0.0, 0.1875, 1.0, 1.0);
					break;
				default:
					ConsoleAPI::error("wat");
			}
		}
	}
	
	
	
	public function place(Item $item, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		if($face == 0 || $face == 1) return false;
		$level = $block->level;
		$xx = $block->x;
		$y = $block->y;
		$zz = $block->z;
		if($face == 2) ++$zz;
		if($face == 3) --$zz;
		if($face == 4) ++$xx;
		if($face == 5) --$xx;
		if(static::attachesTo($level->level->getBlockID($xx, $y, $zz))){
			$this->meta = match($face){
				3 => 1,
				4 => 2,
				5 => 3,
				default => 0
			};
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
	public static function neighborChanged(Level $level, $x, $y, $z, $nX, $nY, $nZ, $oldID){
		$attach = match($level->level->getBlockDamage($x, $y, $z)){
			0, 4 => $level->level->getBlockID($x, $y, $z + 1),
			1, 5 => $level->level->getBlockID($x, $y, $z - 1),
			2, 6 => $level->level->getBlockID($x + 1, $y, $z),
			3, 7 => $level->level->getBlockID($x - 1, $y, $z),
			default => 0
		};
			
		if($attach == AIR){ //Replace with common break method
			ServerAPI::request()->api->entity->drop(new Position($x, $y, $z, $level), BlockAPI::getItem(TRAPDOOR, 0, 1));
			$level->fastSetBlockUpdate($x, $y, $z, 0, 0, true);
		}
	}
	
	public function onActivate(Item $item, Player $player){
		$this->meta ^= 0x04;
		$this->level->setBlock($this, $this, true, false, true);
		return true;
	}
	
	
	/**
	 * @deprecated use TrapdoorBlock::attachesTo($target->getID());
	 * @param Block $target
	 * @return boolean
	 */
	public function canAttachTo(Block $target){
		$id = $target->getID();
		return static::attachesTo($id);
	}
}