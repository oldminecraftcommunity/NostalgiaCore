<?php

abstract class Block extends Position{
	public static $mushroomSpread = true;
	
	public static $class = array(
			AIR => "AirBlock",
			STONE => "StoneBlock",
			GRASS => "GrassBlock",
			DIRT => "DirtBlock",
			COBBLESTONE => "CobblestoneBlock",
			PLANKS => "PlanksBlock",
			SAPLING => "SaplingBlock",
			BEDROCK => "BedrockBlock",
			WATER => "WaterBlock",
			STILL_WATER => "StillWaterBlock",
			LAVA => "LavaBlock",
			STILL_LAVA => "StillLavaBlock",
			SAND => "SandBlock",
			GRAVEL => "GravelBlock",
			GOLD_ORE => "GoldOreBlock",
			IRON_ORE => "IronOreBlock",
			COAL_ORE => "CoalOreBlock",
			WOOD => "WoodBlock",
			LEAVES => "LeavesBlock",
			SPONGE => "SpongeBlock",
			GLASS => "GlassBlock",
			LAPIS_ORE => "LapisOreBlock",
			LAPIS_BLOCK => "LapisBlock",
			SANDSTONE => "SandstoneBlock",
			BED_BLOCK => "BedBlock",
			POWERED_RAIL => "PoweredRailBlock",
			COBWEB => "CobwebBlock",
			TALL_GRASS => "TallGrassBlock",
			DEAD_BUSH => "DeadBushBlock",
			WOOL => "WoolBlock",
			DANDELION => "DandelionBlock",
			CYAN_FLOWER => "CyanFlowerBlock",
			BROWN_MUSHROOM => "BrownMushroomBlock",
			RED_MUSHROOM => "RedMushRoomBlock",
			GOLD_BLOCK => "GoldBlock",
			IRON_BLOCK => "IronBlock",
			DOUBLE_SLAB => "DoubleSlabBlock",
			SLAB => "SlabBlock",
			BRICKS_BLOCK => "BricksBlock",
			TNT => "TNTBlock",
			BOOKSHELF => "BookshelfBlock",
			MOSS_STONE => "MossStoneBlock",
			OBSIDIAN => "ObsidianBlock",
			TORCH => "TorchBlock",
			FIRE => "FireBlock",

			WOOD_STAIRS => "WoodStairsBlock",
			CHEST => "ChestBlock",

			DIAMOND_ORE => "DiamondOreBlock",
			DIAMOND_BLOCK => "DiamondBlock",
			WORKBENCH => "WorkbenchBlock",
			WHEAT_BLOCK => "WheatBlock",
			FARMLAND => "FarmlandBlock",
			FURNACE => "FurnaceBlock",
			BURNING_FURNACE => "BurningFurnaceBlock",
			SIGN_POST => "SignPostBlock",
			WOOD_DOOR_BLOCK => "WoodDoorBlock",
			LADDER => "LadderBlock",
			RAIL => "RailBlock",
			COBBLESTONE_STAIRS => "CobblestoneStairsBlock",
			WALL_SIGN => "WallSignBlock",

			IRON_DOOR_BLOCK => "IronDoorBlock",
			REDSTONE_ORE => "RedstoneOreBlock",
			GLOWING_REDSTONE_ORE => "GlowingRedstoneOreBlock",

			SNOW_LAYER => "SnowLayerBlock",
			ICE => "IceBlock",
			SNOW_BLOCK => "SnowBlock",
			CACTUS => "CactusBlock",
			CLAY_BLOCK => "ClayBlock",
			SUGARCANE_BLOCK => "SugarcaneBlock",

			FENCE => "FenceBlock",
			PUMPKIN => "PumpkinBlock",
			NETHERRACK => "NetherrackBlock",
			SOUL_SAND => "SoulSandBlock",
			GLOWSTONE_BLOCK => "GlowstoneBlock",

			LIT_PUMPKIN => "LitPumpkinBlock",
			INVISIBLE_BEDROCK => "InvisibleBedrockBlock",
			
			CAKE_BLOCK => "CakeBlock",
			
			TRAPDOOR => "TrapdoorBlock",

			STONE_BRICKS => "StoneBricksBlock",

			IRON_BARS => "IronBarsBlock",
			GLASS_PANE => "GlassPaneBlock",
			MELON_BLOCK => "MelonBlock",
			PUMPKIN_STEM => "PumpkinStemBlock",
			MELON_STEM => "MelonStemBlock",

			FENCE_GATE => "FenceGateBlock",
			BRICK_STAIRS => "BrickStairsBlock",
			STONE_BRICK_STAIRS => "StoneBrickStairsBlock",

			NETHER_BRICKS => "NetherBricksBlock",

			NETHER_BRICKS_STAIRS => "NetherBricksStairsBlock",

			SANDSTONE_STAIRS => "SandstoneStairsBlock",
			
			SPRUCE_WOOD_STAIRS => "SpruceWoodStairsBlock",
			BIRCH_WOOD_STAIRS => "BirchWoodStairsBlock",
			JUNGLE_WOOD_STAIRS => "JungleWoodStairsBlock",
			STONE_WALL => "StoneWallBlock",
			
			CARROT_BLOCK => "CarrotBlock",			
			POTATO_BLOCK => "PotatoBlock",

			QUARTZ_BLOCK => "QuartzBlock",
			QUARTZ_STAIRS => "QuartzStairsBlock",
			DOUBLE_WOOD_SLAB => "DoubleWoodSlabBlock",
			WOOD_SLAB => "WoodSlabBlock",
		
			HAY_BALE => "HayBaleBlock",
			CARPET => "CarpetBlock",
			
			COAL_BLOCK => "CoalBlock",
			
			BEETROOT_BLOCK => "BeetrootBlock",
			STONECUTTER => "StonecutterBlock",
			GLOWING_OBSIDIAN => "GlowingObsidianBlock",
			NETHER_REACTOR => "NetherReactorBlock",
			INFO_UPDATE => "InfoUpdateBlock",
			INFO_UPDATE2 => "InfoUpdate2Block",
			RESERVED6 => "Reserved6Block",
	);
	
	/**
	 * @var Material
	 */
	public $material;
	
	public $id;
	public $meta;
	public $name;
	public $boundingBox;
	public $hardness;
	public $breakTime = 0;
	public $isActivable = false;
	public $breakable = true;
	public $isFlowable = false;
	public $isSolid = true;
	public $isTransparent = false;
	public $isReplaceable = false;
	public $isPlaceable = true;
	public $level = false;
	public $hasPhysics = false;
	public $isLiquid = false;
	public $isFullBlock = true;
	public $x = 0;
	public $y = 0;
	public $z = 0;
	public $slipperiness;
	
	public $lightEmission = 0;
	public $lightBlock = 0;
	
	public static function interact(Level $level, $x, $y, $z, Player $player){}
	
	public static function neighborChanged(Level $level, $x, $y, $z, $nX, $nY, $nZ, $oldID){}
	
	public static function getAABB(Level $level, $x, $y, $z){
		return StaticBlock::getAABB(static::$blockID, $x, $y, $z);
	}
	
	public static function containsX($id, $v){
		return !($v == null) && $v->y >= StaticBlock::$minYs[$id] && $v->y <= StaticBlock::$maxYs[$id] && $v->z >= StaticBlock::$minZs[$id] && $v->z <= StaticBlock::$maxZs[$id];
	}
	
	public static function containsY($id, $v){
		return !($v == null) && $v->x >= StaticBlock::$minXs[$id] && $v->x <= StaticBlock::$maxXs[$id] && $v->z >= StaticBlock::$minZs[$id] && $v->z <= StaticBlock::$maxZs[$id];
	}
	
	public static function containsZ($id, $v){
		return !($v == null) && $v->x >= StaticBlock::$minXs[$id] && $v->x <= StaticBlock::$maxXs[$id] && $v->y >= StaticBlock::$minYs[$id] && $v->y <= StaticBlock::$maxYs[$id];
	}
	
	public static function updateShape(Level $level, $x, $y, $z){
		
	}
	
	public static function clip(Level $level, $x, $y, $z, Vector3 $start, Vector3 $end){
		static::updateShape($level, $x, $y, $z);
		$id = $level->level->getBlockID($x, $y, $z);
		
		$start = $start->subtract($x, $y, $z);
		$end = $end->subtract($x, $y, $z);
		
		$v7 = $start->clipX($end, StaticBlock::$minXs[$id]);
		$v8 = $start->clipX($end, StaticBlock::$maxXs[$id]);
		$v9 = $start->clipY($end, StaticBlock::$minYs[$id]);
		$v10 = $start->clipY($end, StaticBlock::$maxYs[$id]);
		$v11 = $start->clipZ($end, StaticBlock::$minZs[$id]);
		$v12 = $start->clipZ($end, StaticBlock::$maxZs[$id]);
		
		if(!self::containsX($id, $v7)) $v7 = null;
		if(!self::containsX($id, $v8)) $v8 = null;
		if(!self::containsY($id, $v9)) $v9 = null;
		if(!self::containsY($id, $v10)) $v10 = null;
		if(!self::containsZ($id, $v11)) $v11 = null;
		if(!self::containsZ($id, $v12)) $v12 = null;
		
		$v13 = null;
		if($v7 != null && ($v13 == null || $start->distanceSquared($v7) < $start->distanceSquared($v13))) $v13 = $v7;
		if($v8 != null && ($v13 == null || $start->distanceSquared($v8) < $start->distanceSquared($v13))) $v13 = $v8;
		if($v9 != null && ($v13 == null || $start->distanceSquared($v9) < $start->distanceSquared($v13))) $v13 = $v9;
		if($v10 != null && ($v13 == null || $start->distanceSquared($v10) < $start->distanceSquared($v13))) $v13 = $v10;
		if($v11 != null && ($v13 == null || $start->distanceSquared($v11) < $start->distanceSquared($v13))) $v13 = $v11;
		if($v12 != null && ($v13 == null || $start->distanceSquared($v12) < $start->distanceSquared($v13))) $v13 = $v12;
		
		if($v13 == null) return null;
		
		$v14 = -1;
		if($v13 == $v7) $v14 = 4;
		if($v13 == $v8) $v14 = 5;
		if($v13 == $v9) $v14 = 0;
		if($v13 == $v10) $v14 = 1;
		if($v13 == $v11) $v14 = 2;
		if($v13 == $v12) $v14 = 3;
		
		return MovingObjectPosition::fromBlock($x, $y, $z, $v14, $v13->add($x, $y, $z));
	}
	
	public static function onPlace(Level $level, $x, $y, $z){}
	public static function addVelocityToEntity(Level $level, $x, $y, $z, Entity $entity, Vector3 $velocityVector){}
	public static function onRandomTick(Level $level, $x, $y, $z){}
	public static function onUpdate(Level $level, $x, $y, $z, $type){}
	public static function fallOn(Level $level, $x, $y, $z, Entity $entity, $fallDistance){}
	public static function getCollisionBoundingBoxes(Level $level, $x, $y, $z, Entity $entity){
		return [static::getAABB($level, $x, $y, $z)];
	}
	public static function onEntityCollidedWithBlock(Level $level, $x, $y, $z, Entity $entity){}
	
	
	public function __construct($id, $meta = 0, $name = "Unknown"){
		$this->id = (int) $id;
		$this->meta = ((int) $meta) & 0xf;
		$this->name = $name;
		$this->breakTime = 0.20;
		$this->hardness = 10;
		$this->slipperiness = 0.6;
		$this->boundingBox = new AxisAlignedBB($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
	}
	
	abstract function onBreak(Item $item, Player $player);
	
	abstract function place(Item $item, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz);
	
	abstract function onActivate(Item $item, Player $player);
	
	final public function getHardness(){
		return ($this->hardness);
	}
	
	final public function getName(){
		return $this->name;
	}
	
	final public function getID(){
		return $this->id;
	}
	public function setMetadata($i){
		$this->meta = $i;
	}
	final public function getMetadata(){
		return $this->meta & 0x0F;
	}
	
	public function getDestroyProgress(Player $player){
		if($this->breakTime < 0) return 0;
		if($this->breakTime == 0) return INF; //instabreak
		$id = $this->getID();
		$meta = $this->getMetadata();
		if($player->canDestroy($id, $meta)){
			return $player->getDestroySpeed($id, $meta) / $this->breakTime / 30;
		}
		
		return (1 / $this->breakTime) / 100;
	}
	
	final public function position(Position $v){
		$this->level = $v->level;
		$this->x = (int) $v->x;
		$this->y = (int) $v->y;
		$this->z = (int) $v->z;
		$this->boundingBox->setBounds($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
	}
	
	public function getDrops(Item $item, Player $player){
		if(!isset(Block::$class[$this->id])){ //Unknown blocks
			return array();
		}else{
			return array(
				array($this->id, $this->meta, 1),
			);
		}
	}
	
	public function getBreakTime(Item $item, Player $player){
		if($this->hardness < 0) return INF;
		if(($player->gamemode & 0x01) === 0x01){
			return 0.15;
		}
		if($player->canDestroy($this->getID(), $this->getMetadata()))
		return $this->breakTime;
	}
	
	public function getSide($side, $step = 1){
		$v = parent::getSide($side, $step);
		if($this->level instanceof Level){
			return $this->level->getBlock($v);
		}
		return $v;
	}
	
	final public function __toString(){
		return "Block ". $this->name ." (".$this->id.":".$this->meta.")";
	}
	
	public function isBreakable(Item $item, Player $player){
		return $this->hardness >= 0;
	}
}

/***REM_START***/
require_once("block/GenericBlock.php");
require_once("block/SolidBlock.php");
require_once("block/TransparentBlock.php");
require_once("block/FallableBlock.php");
require_once("block/LiquidBlock.php");
require_once("block/StairBlock.php");
require_once("block/DoorBlock.php");
/***REM_END***/
