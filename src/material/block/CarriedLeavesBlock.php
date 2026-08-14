<?php

class CarriedLeavesBlock extends SolidBlock{
	public static $blockID;
	public function __construct($meta = 0){
		parent::__construct(LEAVES_CARRIED, $meta, "Carried Leaves");
	}
}