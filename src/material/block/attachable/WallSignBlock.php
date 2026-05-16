<?php

/***REM_START***/
require_once("SignPostBlock.php");
/***REM_END***/

class WallSignBlock extends SignPostBlock{
	public static $blockID;
	public function __construct($meta = 0){
		TransparentBlock::__construct(WALL_SIGN, $meta, "Wall Sign");
		$this->isSolid = false;
		$this->breakTime = 1;
		$this->hardness = 5;
		$this->material = Material::$wood;
	}
	
	public static function neighborChanged(Level $level, $x, $y, $z, $nX, $nY, $nZ, $oldID){
		$attached = match($level->level->getBlockDamage($x, $y, $z)){
			2 => $level->level->getBlockID($x, $y, $z + 1),
			3 => $level->level->getBlockID($x, $y, $z - 1),
			4 => $level->level->getBlockID($x + 1, $y, $z),
			5 => $level->level->getBlockID($x - 1, $y, $z),
			default => WALL_SIGN
		};
			
		if(!StaticBlock::getIsSolid($attached) && $attached != SIGN_POST && $attached != WALL_SIGN && $attached != GLASS_PANE && $attached != IRON_BARS && $attached != STONE_WALL){
			$level->fastSetBlockUpdate($x, $y, $z, 0, 0, true, true);
			(ServerAPI::request())->api->entity->drop(new Position($x + 0.5, $y + 0.5, $z + 0.5, $level), BlockAPI::getItem(SIGN, 0, 1));
		}
	}
}