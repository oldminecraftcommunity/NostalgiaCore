<?php
//TODO 0.7 - 0.9
class CarriedGrassBlock extends SolidBlock{
	public static $blockID;
	public function __construct($meta = 0){
		parent::__construct(GRASS_CARRIED, $meta, "Carried Grass");
	}
}