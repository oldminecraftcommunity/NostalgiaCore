<?php

class Entity extends Position
{

	const TYPE = - 1;
	const CLASS_TYPE = - 1;
	const MIN_POSSIBLE_SPEED = 1/8000; //anything below will send 0 to player
	
	public $forceUpdateMovement = false;
	public $searchForClosestPlayers = false;
	public $modifySpeedY = false;
	public $modifedSpeedY = 0.0;
	public $counter = 0;
	public $fallDistance = 0;
	public static $updateOnTick, $allowedAI;
	public static $allowFly = true;
	public static $keepInventory = false;
	public $canBeAttacked;
	public $moveTime, $lookTime, $idleTime, $knockbackTime = 0;
	public $attackTimeout = 0;
	/**
	 * @deprecated always true
	 * @var boolean
	 */
	public $needsUpdate = true;
	public $speedModifer;
	public $hasGravity;
	/**
	 * @var AxisAlignedBB
	 */
	public $boundingBox;
	public $age;
	public $air;
	public $maxAir = 300;
	public $spawntime;
	public $dmgcounter;
	public $eid;
	public $type;
	public $name;
	public $delayBeforePickup;
	public $x, $y, $z;
	public $speedX, $speedY, $speedZ, $speed;
	public $lastX = 0, $lastY  = 0, $lastZ  = 0, $lastYaw  = 0, $lastPitch  = 0, $lastTime = 0, $lastHeadYaw = 0, $lastSpeedX = 0, $lastSpeedY = 0, $lastSpeedZ = 0;
	/**
	 * 0 = lastX, 
	 * 1 = lastY, 
	 * 2 = lastZ, 
	 * 3 = lastYaw, 
	 * 4 = lastPitch, 
	 * 5 = lastTime. 
	 * 
	 * It is not recommended to use this and it is left as a backwards compability.
	 * 
	 * @var array
	 */
	public $last;
	public $yaw, $headYaw;
	public $pitch;
	public $dead;
	public $data;
	public $class;
	public $attach;
	public $closed;
	/**
	 * @var Player
	 */
	public $player;
	public $fallY;
	public $fallStart;
	private $state;
	private $tickCounter;
	private $speedMeasure = array(0, 0, 0, 0, 0, 0, 0);
	public $server;
	private $isStatic;
	public $level;
	public $lastUpdate;
	
	public $check = true;
	public $width = 1;
	public $height = 1;
	/**
	 * @var XorShift128Random
	 */
	public $random;
	public $radius;
	public $inAction = false;
	public $inActionCounter = 0;
	public $hasKnockback;
	public $hasJumped;
	public $invincible, $crouched, $fire, $health, $status;
	public $position;
	public $onGround, $inWater;
	public $carryoverDamage;
	public $gravity;
	
	public $stepHeight = 0.5;
	public $enableAutojump = false;
	public $yOffset = 0.0;
	public $noClip = false;
	
	public $chunkX = 0;
	public $chunkZ = 0;
	
	public $isCollidedHorizontally, $isCollidedVertically, $isCollided;
	/**
	 * Amount of ticks you can be in fire until you start receiving damage
	 * @var integer
	 */
	public $fireResistance = 1;
	
	public $isImmuneToFire = false;
	
	public $inWeb;
	public $inLava;
	public $notOnGroundTicks = 0;
	
	public $moveStrafing, $moveForward;
	
	
	/**
	 * Stores EID of the entity that is riding on this entity.
	 * @var integer
	 */
	public $rider = 0;
	/**
	 * Stores EID of the entity that is being ridden by this entity.
	 * @var integer
	 */
	public $riding = 0;
	
	public function __construct(Level $level, $eid, $class, $type = 0, $data = [])
	{
		$this->random = new XorShift128Random();
		$this->last = [&$this->lastX, &$this->lastY, &$this->lastZ, &$this->lastYaw, &$this->lastPitch, &$this->lastTime]; //pointers to variables
		$this->canBeAttacked = false;
		$this->hasKnockback = false;
		$this->level = $level;
		$this->speedModifer = 1;
		$this->fallY = false;
		$this->fallStart = false;
		$this->server = ServerAPI::request();
		$this->eid = (int) $eid;
		$this->type = (int) $type;
		$this->class = (int) $class;
		$this->player = false;
		$this->attach = false;
		$this->data = $data;
		$this->status = 0;
		$this->health = 20;
		$this->hasGravity = false;
		$this->dmgcounter = array(0, 0, 0);
		$this->air = $this->maxAir;
		$this->fire = 0;
		$this->crouched = false;
		$this->invincible = false;
		$this->lastUpdate = $this->spawntime = microtime(true);
		$this->dead = false;
		$this->closed = false;
		$this->isStatic = false;
		$this->name = "";
		$this->gravity = 0.08;
		$this->state = $this->data["State"] = $this->data["State"] ?? 0;
		$this->x = isset($this->data["x"]) ? (float) $this->data["x"] : 0;
		$this->y = isset($this->data["y"]) ? (float) $this->data["y"] : 0;
		$this->z = isset($this->data["z"]) ? (float) $this->data["z"] : 0;
		$this->speedX = isset($this->data["speedX"]) ? (float) $this->data["speedX"] : 0;
		$this->speedY = isset($this->data["speedY"]) ? (float) $this->data["speedY"] : 0;
		$this->speedZ = isset($this->data["speedZ"]) ? (float) $this->data["speedZ"] : 0;
		$this->speed = 0;
		$this->yaw = isset($this->data["yaw"]) ? (float) $this->data["yaw"] : 0;
		$this->headYaw = $this->data["headYaw"] ?? $this->yaw;
		$this->pitch = isset($this->data["pitch"]) ? (float) $this->data["pitch"] : 0;
		$this->position = array(
			"level" => $this->level,
			"x" => &$this->x,
			"y" => &$this->y,
			"z" => &$this->z,
			"yaw" => &$this->yaw,
			"pitch" => &$this->pitch
		);
		$this->moveTime = 0;
		$this->lookTime = 0;
		$this->onGround = false;
		switch($this->class) {
			case ENTITY_PLAYER:
				$this->player = $this->data["player"];
				$this->setHealth($this->health, "generic", allowHarm: false);
				$this->speedModifer = 1;
				$this->width = 0.6;
				$this->height = 1.8;
				$this->hasKnockback = true;
				$this->hasGravity = true;
				$this->canBeAttacked = true;
				$this->fireResistance = 20;
				
				break;
			case ENTITY_OBJECT:
				if($this->type == OBJECT_PAINTING) break;
				$this->x = $this->data["TileX"] ?? $this->x;
				$this->y = $this->data["TileY"] ?? $this->y;
				$this->z = $this->data["TileZ"] ?? $this->z;
				$this->setHealth(1, "generic", allowHarm: false);
				$this->stepHeight = false;
				$this->setSize(1, 1);
				break;
		}
		$this->radius = $this->width / 2;
		$this->boundingBox = new AxisAlignedBB($this->x - $this->radius, $this->y, $this->z - $this->radius, $this->x + $this->radius, $this->y + $this->height, $this->z + $this->radius);
		$this->updateLast();
		$this->updatePosition();
		if($this->isInVoid()){
			$this->outOfWorld();
		}
	}
	
	public function handlePrePlayerSearcher(){
		
	}
	public function isType(){
		return in_array($this->type, func_get_args());
	}
	/**
	 * @param Entity $entity
	 * @param int $distance
	 * @return boolean
	 */
	public function attackEntity($entity, $distance){
		return false;
	}
	
	public function setInWeb(){
		$this->inWeb = true;
		$this->fallDistance = 0;
	}
	
	public function addVelocity($vX, $vY = 0, $vZ = 0)
	{
		if($vX instanceof Vector3){
			return $this->addVelocity($vX->x, $vX->y, $vX->z);
		}
		$this->speedX += $vX;
		$this->speedY += $vY;
		$this->speedZ += $vZ;
	}
	
	public function isPushable(){
		return $this->isPlayer();
	}
	
	public function applyCollision(Entity $collided){
		if(!($this->isPlayer() && $collided->isPlayer()) && $this->eid != $collided->eid){
			$diffX = $collided->x - $this->x;
			$diffZ = $collided->z - $this->z;
			$maxDiff = max(abs($diffX), abs($diffZ));
			if($maxDiff > 0.01){
				$sqrtMax = sqrt($maxDiff);
				$diffX /= $sqrtMax;
				$diffZ /= $sqrtMax;
				
				$col = (($v = 1 / $sqrtMax) > 1 ? 1 : $v);
				$diffX *= $col;
				$diffZ *= $col;
				$diffX *= 0.05;
				$diffZ *= 0.05;
				$this->addVelocity(-$diffX, 0, -$diffZ);
				$collided->addVelocity($diffX, 0, $diffZ);
			}
		}
	}
	
	public function isMovingHorizontally()
	{
		return ($this->speedX >= self::MIN_POSSIBLE_SPEED || $this->speedX <= -self::MIN_POSSIBLE_SPEED) || ($this->speedZ >= self::MIN_POSSIBLE_SPEED || $this->speedZ <= -self::MIN_POSSIBLE_SPEED);
	}
	public function isMoving()
	{
		return  $this->isMovingHorizontally() || ($this->speedY > self::MIN_POSSIBLE_SPEED || $this->speedY < -self::MIN_POSSIBLE_SPEED);
	}

	public function setVelocity($vX, $vY = 0, $vZ = 0)
	{
		if($vX instanceof Vector3){
			return $this->setVelocity($vX->x, $vX->y, $vX->z);
		}
		$this->speedX = $vX;
		$this->speedY = $vY;
		$this->speedZ = $vZ;
	}

	/**
	 *
	 * @param mixed $e
	 *			Entity instance or EID
	 * @return string|false if failed
	 */
	public static function getNameOf($e)
	{
		if($e instanceof Entity){
			return $e->getName();
		} elseif(($e = ServerAPI::request()->api->entity->get($e)) != false){
			return $e->getName();
		}
		return false;
	}

	/**
	 *
	 * @param mixed $e
	 *			Entity instance or EID
	 * @return number|false if failed
	 */
	public static function getHeightOf($e)
	{
		if($e instanceof Entity){
			return $e->getHeight();
		} elseif(($e = ServerAPI::request()->api->entity->get($e)) != false){
			return $e->getHeight();
		}
		return false;
	}

	/**
	 *
	 * @param mixed $e
	 *			Entity instance or EID
	 * @return number|false if failed
	 */
	public static function getWidthOf($e)
	{
		if($e instanceof Entity){
			return $e->getWidth();
		} elseif(($e = ServerAPI::request()->api->entity->get($e)) != false){
			return $e->getWidth();
		}
		return false;
	}

	/**
	 * Get an entity height
	 *
	 * @return number
	 */
	public function getHeight()
	{
		return $this->height;
	}

	/**
	 * Get an entity width
	 *
	 * @return number
	 */
	public function getWidth()
	{
		return $this->width;
	}

	public function getDrops()
	{
		if($this->class === ENTITY_PLAYER and $this->player instanceof Player and ($this->player->gamemode & 0x01) === 0){
			$inv = [];
			if(self::$keepInventory){
				return [];
			}
			for($i = 0; $i < PLAYER_SURVIVAL_SLOTS; ++ $i){
				$slot = $this->player->getSlot($i);
				$this->player->setSlot($i, BlockAPI::getItem(AIR, 0, 0));
				if($slot->getID() !== AIR and $slot->count > 0){
					$inv[] = array(
						$slot->getID(),
						$slot->getMetadata(),
						$slot->count
					);
				}
			}
			for($re = 0; $re < 4; $re ++){
				$slot = $this->player->getArmor($re);
				$this->player->setArmor($re, BlockAPI::getItem(AIR, 0, 0));
				if($slot->getID() !== AIR and $slot->count > 0){
					$inv[] = array(
						$slot->getID(),
						$slot->getMetadata(),
						$slot->count
					);
				}
			}
			return $inv;
		}
		return [];
	}

	private function spawnDrops()
	{
		foreach($this->getDrops() as $drop){
			$this->server->api->entity->drop($this, BlockAPI::getItem($drop[0] & 0xFFFF, $drop[1] & 0xFFFF, $drop[2] & 0xFF));
		}
	}
	
	public function canSee(Entity $e){
		return $this->level->rayTraceBlocks(new Vector3($this->x, $this->y + $this->getEyeHeight(), $this->z), new Vector3($e->x, $e->y + $e->getEyeHeight(), $e->z)) == null;
	}
	
	/**
	 * Can entity be collided with or not
	 * @return boolean
	 */
	public function isPickable(){
		return $this->isPlayer() && !$this->dead;
	}
	
	public function handleLavaMovement(){ //TODO maybe try merging with water?
		$this->inLava = $this->level->isLavaInBB($this->boundingBox->expand(-0.1, -0.4, -0.1));
		return $this->inLava;
	}
	
	public function handleWaterMovement(){
		if($this->level->handleMaterialAcceleration($this->boundingBox->expand(0, -0.4, 0)->contract(0.001, 0.001, 0.001), Material::$water, $this)){
			$this->fallDistance = 0;
			$this->inWater = true;
			if($this->fire > 0){
				$this->fire = 0;
				$this->updateMetadata();
			}else{
				$this->fire = 0;
			}
			
		}else{
			$this->inWater = false;
		}
		
		return $this->inWater;
	}
	
	public function environmentUpdate($time)
	{
		$tickDiff = ($time - $this->lastUpdate) / 0.05;
		if($this->attackTimeout > 0) $this->attackTimeout -= $tickDiff;
		if($this->attackTimeout < 0) $this->attackTimeout = 0;
		
		if($this->isPlayer() && $this->player->spawned === true && $this->player->blocked !== true && !$this->dead){
			$myBB = $this->boundingBox->grow(1, 0.5, 1);
			foreach($this->server->api->entity->getRadius($this, 2, false) as $item){
				if($item->class === ENTITY_ITEM && !$item->closed && $item->spawntime > 0 && $item->delayBeforePickup <= 0){
					if($item->boundingBox->intersectsWith($myBB)){ 
						if((($this->player->gamemode & 0x01) === 1 || $this->player->hasSpace($item->itemID, $item->meta, $item->stack) === true) && $this->server->api->dhandle("player.pickup", array(
							"eid" => $this->player->eid,
							"player" => $this->player,
							"entity" => $item,
							"block" => $item->itemID,
							"meta" => $item->meta,
							"target" => $item->eid
						)) !== false){
							$item->close();
						}
					}
				}else if($item->class == ENTITY_OBJECT && !$item->closed && $item->type == OBJECT_ARROW && $item->shotByPlayer && $item->inGround && $item->shake <= 0 && $item->boundingBox->intersectsWith($myBB)){
					if(($this->player->gamemode & 0x01) == 1 || $this->player->hasSpace(ARROW, 0, 1)){
						$this->player->addItem(ARROW, 0, 1);
						$item->close();
					}
				}
			}
			unset($myBB);
		} elseif($this->class === ENTITY_ITEM){
			if(($time - $this->spawntime) >= 300){
				$this->close(); // Despawn timer
				return false;
			}
		} elseif($this->class === ENTITY_OBJECT and $this->type === OBJECT_SNOWBALL){
			if(($time - $this->spawntime) >= 60){
				$this->close();
				return false;
			}
		}

		if($this->dead === true){
			$this->fire = 0;
			$this->air = $this->maxAir;
			return false;
		}
		
		$this->handleWaterMovement();
		if($this->fire > 0){ //TODO move somewhere
			if(!$this->isImmuneToFire){
				if(($this->fire % 20) == 0){
					$this->harm(1, "burning");
				}
				--$this->fire;
			}else{
				$this->fire -= 4;
				if($this->fire <= 0) $this->fire = 0;
			}
			
			if($this->fire <= 0){
				$this->updateMetadata();
			}
		}

		if($this->handleLavaMovement()){
			if(!$this->isImmuneToFire){
				$this->harm(4, "fire");
				$oldOnFire = $this->fire;
				if($oldOnFire < 20*30) $this->fire = 20*30; //30 seconds
			}
		}
		
		if($this->isInVoid()){
			$this->outOfWorld();
		}
		
		if(!($this instanceof Painting) && !($this->isPlayer() && $this->player->isSleeping !== false)){ //TODO better way to fix	
			$x = floor($this->x);
			$y = floor($this->y + $this->getEyeHeight());
			$z = floor($this->z);
			if(!StaticBlock::getIsTransparent($this->level->level->getBlockID($x, $y, $z))){
				$this->harm(1, "suffocation");
			}
		}
		
		//air damage
		if($this->isPlayer() || $this instanceof Living){
			$d = $this->y + $this->getEyeHeight();
			$x = floor($this->x);
			$y = floor($d);
			$z = floor($this->z);
			
			$id = $this->level->level->getBlockID($x, $y, $z);
			if($id == WATER || $id == STILL_WATER){
				$f = LiquidBlock::getPercentAir($this->level->level->getBlockDamage($x, $y, $z)) - 0.1111111;
				$f1 = ($y + 1) - $f;
				if($d < $f1){
					$this->air -= $tickDiff;
					if($this->air <= -20){
						$this->harm(2, "water");
						$this->air = 0; //harm every 1 second
					}
				}else{
					$this->air = $this->maxAir;
				}
			}else{
				$this->air = $this->maxAir;
			}
		}
		return true;
	}
	
	public function moveFlying($strafe, $forward, $speed){ //TODO rename?
		$v4 = $strafe*$strafe + $forward*$forward;
		
		if($v4 >= 0.0001){
			$v4 = sqrt($v4);
			if($v4 < 1) $v4 = 1;
			
			$v4 = $speed / $v4;
			$strafe *= $v4;
			$forward *= $v4;
			
			$v5 = sin($this->yaw * M_PI/180);
			$v6 = cos($this->yaw * M_PI/180);
			
			$this->speedX += $strafe*$v6 - $forward*$v5;
			$this->speedZ += $forward*$v6 + $strafe*$v5;
		}
	}
	
	public function updateEntityMovement(){}
	
	public function doBlocksCollision(){
		$minY = floor($this->boundingBox->minY + 0.001);
		$maxY = floor($this->boundingBox->maxY - 0.001);
		$minX = floor($this->boundingBox->minX + 0.001);
		$minZ = floor($this->boundingBox->minZ + 0.001);
		$maxX = floor($this->boundingBox->maxX - 0.001);
		$maxZ = floor($this->boundingBox->maxZ - 0.001);
		
		for($x = $minX; $x <= $maxX; ++$x){
			for($y = $minY; $y <= $maxY; ++$y){
				for($z = $minZ; $z <= $maxZ; ++$z){
					$id = $this->level->level->getBlockID($x, $y, $z);
					
					if($id > 0){
						StaticBlock::$prealloc[$id]::onEntityCollidedWithBlock($this->level, $x, $y, $z, $this);
					}
				}
			}
		}
		
	}
	  
	public function move($dx, $dy, $dz){
		$movX = $this->x;
		$movY = $this->y;
		$movZ = $this->z;
		
		if($this->inWeb){
			$this->inWeb = false;
			$dx *= 0.25;
			$dy *= 0.05;
			$dz *= 0.25;
			
			$this->speedX = $this->speedY = $this->speedZ = 0;
		}
		
		
		$savedDX = $dx;
		$savedDY = $dy;
		$savedDZ = $dz;
		
		$oldBB = clone $this->boundingBox;
		
		
		$aaBBs = $this->level->getCubes($this, $this->boundingBox->addCoord($dx, $dy, $dz));
		
		foreach($aaBBs as $bb){
			$dy = $bb->calculateYOffset($this->boundingBox, $dy);
		}
		$this->boundingBox->offset(0, $dy, 0);
		
		foreach($aaBBs as $bb){
			$dx = $bb->calculateXOffset($this->boundingBox, $dx);
		}
		$this->boundingBox->offset($dx, 0, 0); 
		
		foreach($aaBBs as $bb){
			$dz = $bb->calculateZOffset($this->boundingBox, $dz);
		}
		$this->boundingBox->offset(0, 0, $dz);
		
		$fallingFlag = $this->onGround || $savedDY != $dy && $savedDY < 0;
		
		if($this->stepHeight > 0 && $fallingFlag && ($savedDX != $dx || $savedDZ != $dz)){
			$cx = $dx;
			$cy = $dy;
			$cz = $dz;
			
			$dx = $savedDX;
			$dy = $this->stepHeight;
			$dz = $savedDZ;
			$aabb1 = clone $this->boundingBox;
			$this->boundingBox->setBB($oldBB);
			
			$aaBBs = $this->level->getCubes($this, $this->boundingBox->addCoord($dx, $dy, $dz));
			
			foreach($aaBBs as $bb){
				$dy = $bb->calculateYOffset($this->boundingBox, $dy);
			}
			$this->boundingBox->offset(0, $dy, 0);
			
			foreach($aaBBs as $bb){
				$dx = $bb->calculateXOffset($this->boundingBox, $dx);
			}
			$this->boundingBox->offset($dx, 0, 0);
			
			foreach($aaBBs as $bb){
				$dz = $bb->calculateZOffset($this->boundingBox, $dz);
			}
			$this->boundingBox->offset(0, 0, $dz);
			
			if ($cx*$cx + $cz*$cz >= $dx*$dx + $dz*$dz)
			{
				$dx = $cx;
				$dy = $cy;
				$dz = $cz;
				$this->boundingBox->setBB($aabb1);
			}else{
				$this->modifySpeedY = true;
				$this->modifedSpeedY = 0.5;
			}
			
		}
		
		$this->x = ($this->boundingBox->minX + $this->boundingBox->maxX) / 2;
		$this->y = $this->boundingBox->minY + $this->yOffset;
		$this->z = ($this->boundingBox->minZ + $this->boundingBox->maxZ) / 2;
		$this->isCollidedHorizontally = $savedDX != $dx || $savedDZ != $dz;
		$this->isCollidedVertically = $savedDY != $dy;
		$this->onGround = $savedDY != $dy && $savedDY < 0.0;
		$this->isCollided = $this->isCollidedHorizontally || $this->isCollidedVertically;
		$this->updateFallState($this->speedY);
		
		if($savedDX != $dx) $this->speedX = 0;
		if($savedDY != $dy) $this->speedY = 0;
		if($savedDZ != $dz) $this->speedZ = 0;
		
		
		
		//TODO more stuff -> onEntityWalking
		
		$this->doBlocksCollision();
		
		$oldFire = $this->fire;
		if($this->level->isBoundingBoxOnFire($this->boundingBox->contract(0.001, 0.001, 0.001))){
			$this->harm(1, "fire");
			if(!$this->inWater){
				++$this->fire;
				if($this->fire == 0) $this->fire = 8*20;
			}
		}elseif($this->fire <= 0){
			$this->fire = -$this->fireResistance;
		}
		
		if($this->inWater && $this->fire > 0){
			$this->fire = -$this->fireResistance;
		}
		
		if(($oldFire > 0 && $this->fire <= 0) || ($oldFire <= 0 && $this->fire > 0)){
			$this->updateMetadata(); //TODO rewrite metadata
		}
	}
	
	//in MCP it is called isOffsetPositionInLiquid, in 0.8 - isFree
	public function isFree($offsetX, $offsetY, $offsetZ){
		$offsetBB = $this->boundingBox->getOffsetBoundingBox($offsetX, $offsetY, $offsetZ);
		
		$minX = floor($offsetBB->minX);
		$minY = floor($offsetBB->minY);
		$minZ = floor($offsetBB->minZ);
		$maxX = floor($offsetBB->maxX + 1);
		$maxY = floor($offsetBB->maxY + 1);
		$maxZ = floor($offsetBB->maxZ + 1);
		
		$hasLiquid = false;
		$result = false;
		for($x = $minX; $x < $maxX; ++$x){
			for($z = $minZ; $z < $maxZ; ++$z){
				for($y = $minY - 1; $y < $maxY; ++$y){
					$v12 = $this->level->level->getBlockID($x, $y, $z);
					if($y != ($minY - 1)) $hasLiquid |= StaticBlock::getIsLiquid($v12);
					if($v12 != 0){
						$result = true;
						break 3;
					}
				}
			}
		}
		if($result) return !$hasLiquid;
		return false;
	}
	
	public function getRidingHeight(){
		return $this->yOffset;
	}
	
	public function getRideHeight(){
		return $this->height * 0.75;
	}
	
	public function isInVoid(){
		return $this->y < -1.6;
	}
	
	public function handlePlayerSearcher(Player $player, $dist){
		
	}
	
	public function positionRider($dead){
		if($this->rider != 0){
			$e = $this->level->entityList[$this->rider] ?? false;
			if(!($e instanceof Entity)) return;
			
			$v3 = $this->getRideHeight();
			if($dead || $this->dead) $v3 = 0.01;
			$v4 = $e->getRidingHeight();
			$e->setPos($this->x, $this->y + $v3+$v4, $this->z);
		}
	}
	
	public function handleRiding(){
		if($this->riding != 0){
			$e = $this->server->api->entity->get($this->riding);
			if($e instanceof Entity){
				$this->speedX = $this->speedY = $this->speedZ = 0;
				
				$e->positionRider($e->dead);
				
				$this->fallDistance = 0;
			}else{
				ConsoleAPI::warn("$this is riding invalid entity({$this->riding})");
			}
		}
	}
	
	public function update($now){
		if($this->closed === true){
			return false;
		}
		
		if($this->check === false){
			$this->lastUpdate = $now;
			return;
		}
		$this->environmentUpdate($now);

		if($this->closed === true){
			return false;
		}
		++$this->counter;
		
		if($this->riding != 0){
			$this->handleRiding();
		}
		
		if($this->isStatic === false){
			if(!$this->isPlayer()){
				$this->updateLast();
				$this->updatePosition(); //TODO shouldnt be called in Entity
				$this->updateEntityMovement();
			} else{
				$prevGroundState = $this->onGround;
				$this->onGround = false;
				$this->speedX = -($this->lastX - $this->x);
				$this->speedY = -($this->lastY - $this->y);
				$this->speedZ = -($this->lastZ - $this->z);
				
				$contractedCollisionBB = $this->boundingBox->contract(0.001, 0.001, 0.001);
				$fireMinX = floor($contractedCollisionBB->minX);
				$fireMinY = floor($contractedCollisionBB->minY);
				$fireMinZ = floor($contractedCollisionBB->minZ);
				$fireMaxX = floor($contractedCollisionBB->maxX + 1);
				$fireMaxY = floor($contractedCollisionBB->maxY + 1);
				$fireMaxZ = floor($contractedCollisionBB->maxZ + 1);
				
				$bbbottom = $this->boundingBox->addCoord(0, -0.05, 0);
				$bbbottom->minX = round($this->x, 3) - $this->radius;
				$bbbottom->maxX = round($this->x, 3) + $this->radius;
				$bbbottom->minZ = round($this->z, 3) - $this->radius;
				$bbbottom->maxZ = round($this->z, 3) + $this->radius;
				
				$maxx = $this->x;
				$minx = $this->lastX;
				$maxy = $this->y;
				$miny = $this->lastY;
				$maxz = $this->z;
				$minz = $this->lastZ;
				
				if($this->x < $this->lastX){
					$minx = $this->x;
					$maxx = $this->lastX;
				}
				
				if($this->y < $this->lastY){
					$miny = $this->y;
					$maxy = $this->lastY;
				}
				
				if($this->z < $this->lastZ){
					$minz = $this->z;
					$maxz = $this->lastZ;
				}
				
				$useOldGroundCheck = false;
				$distBetweenLastAndCurrent = sqrt(($this->lastX-$this->x)*($this->lastX-$this->x) + ($this->lastY-$this->y)*($this->lastY-$this->y) + ($this->lastZ-$this->z)*($this->lastZ-$this->z));
				if($distBetweenLastAndCurrent > 10){
					ConsoleAPI::warn("{$this->player} is moving too fast! (Old: {$this->lastX} {$this->lastY} {$this->lastZ}, New: {$this->x} {$this->y} {$this->z}). Skipping new onground check!");
					$useOldGroundCheck = true;
					goto skip_new_onground_check;
				}
				$bbGround = new AxisAlignedBB($minx - $this->radius, $miny, $minz - $this->radius, $maxx + $this->radius, $maxy, $maxz + $this->radius);
				
				for($x = floor($bbGround->minX); $x <= ceil($bbGround->maxX); ++$x){
					for($z = floor($bbGround->minZ); $z <= ceil($bbGround->maxZ); ++$z){
						for($y = floor($bbGround->minY-1); $y <= ceil($bbGround->maxY); ++$y){
							$id = $this->level->level->getBlockID($x, $y, $z);
							$bounds = StaticBlock::$prealloc[$id]::getCollisionBoundingBoxes($this->level, $x, $y, $z, $this);
							foreach($bounds as $bb){
								if($bbGround->intersectsWith($bb)){
									$this->onGround = true;
								}
							}
						}
					}
				}
				
				skip_new_onground_check:
				
				$handleFire = false;
				$handleCactus = false;
				
				for($x = floor($this->boundingBox->minX); $x < ceil($this->boundingBox->maxX); ++$x){
					for($z = floor($this->boundingBox->minZ); $z < ceil($this->boundingBox->maxZ); ++$z){
						for($y = floor($this->boundingBox->minY - 1); $y < ceil($this->boundingBox->maxY); ++$y){
							[$id, $meta] = $this->level->level->getBlock($x, $y, $z);
							
							if($useOldGroundCheck){
								$intersects = 0;
								$bounds = StaticBlock::$prealloc[$id]::getCollisionBoundingBoxes($this->level, $x, $y, $z, $this);
								foreach($bounds as $bb){
									if($bbbottom->intersectsWith($bb)){
										++$intersects;
									}
								}
							}
							
							
							if($id == WATER  || $id === STILL_WATER || $id === COBWEB || $id == LAVA  || $id === STILL_LAVA){
								$this->notOnGroundTicks = 0;
							}
							
							if($useOldGroundCheck){
								intersects:
								if($y <= floor($this->boundingBox->minY) && !$this->onGround){
									if($intersects > 0) $this->onGround = count($bounds) > 0;
								}
							}
							
							if($y >= floor($this->boundingBox->minY)){
								$handleFire = $handleFire || (($id == FIRE || $id == STILL_LAVA || $id == LAVA) && $x >= $fireMinX && $x < $fireMaxX && $y >= $fireMinY && $y < $fireMaxY && $z >= $fireMinZ && $z < $fireMaxZ);
								$handleCactus = $handleCactus || ($id == CACTUS && $x >= $fireMinX && $x < $fireMaxX && $y >= $fireMinY && $y < $fireMaxY && $z >= $fireMinZ && $z < $fireMaxZ);
								
								if($id === WATER || $id === STILL_WATER || $id === COBWEB){
									$this->fallDistance = 0;
									$this->fallStart = $this->y;
								}
							}
							
						}
					}
				}
				
				if($prevGroundState == $this->onGround && !$this->onGround && $this->player->isSleeping == false && !$this->dead && $this->riding == 0){ //isSleeping may be a vector
					++$this->notOnGroundTicks;
				}else if($this->onGround){
					$this->notOnGroundTicks = 0;
				}
				
				if($this->notOnGroundTicks > 80){ //~70 ticks is needed to reach from 127 to 0
					if(($this->player->gamemode & 1) != CREATIVE && !$this->player->blocked && $this->player->spawned){ //survival(0), adventure(2)
						if(!Entity::$allowFly){
							$this->player->close("flying");
						}
					}
				}
				
				
				if($this->isOnLadder()){
					$this->fallDistance = 0;
					$this->notOnGroundTicks = 0;
					$this->fallStart = $this->y;
				}
				
				if(!$this->onGround && $prevGroundState){
					$this->fallStart = $this->y;
				}
				
				$this->updateFallState(($this->speedY <=> 0)*0.1);
				if($this->onGround) $this->fallDistance = 0;
				
				if($handleCactus){
					$this->harm(1, "cactus");
				}
				$oldFire = $this->fire;
				if($handleFire){
					$this->harm(1, "fire");
					if(!$this->inWater){
						++$this->fire;
						if($this->fire == 0) $this->fire = 8*20;
					}
				}elseif($this->fire <= 0){
					$this->fire = -$this->fireResistance;
				}
				
				if($this->inWater && $this->fire > 0){
					$this->fire = -$this->fireResistance;
				}
				
				if(($oldFire > 0 && $this->fire <= 0) || ($oldFire <= 0 && $this->fire > 0)){
					$this->updateMetadata(); //TODO rewrite metadata
				}
			}
		}
		
		
		$this->counterUpdate();
		
		if($this->isPlayer()){
			$this->updateMovement();
		}
		
		if($this->isPlayer()){
			$this->player->entityTick();
		}
		
		$this->needsUpdate = true;
		$this->lastUpdate = $now;
	}
	public function isOnLadder(){
		$x = (int)$this->x;
		$y = (int)$this->boundingBox->minY;
		$z = (int)$this->z;
		return $this->level->level->getBlockID($x, $y, $z) === LADDER;
	}
	
	public function fall(){
		if($this->isPlayer()){
			
			$x = floor($this->x);
			$y = floor($this->y - 1); //TODO not 1
			$z = floor($this->z);
			$bid = $this->level->level->getBlockID($x, $y, $z);
			if($bid > 0){
				$clz = StaticBlock::getBlock($bid);
				$clz::fallOn($this->level, $x, $y, $z, $this, $this->fallStart - $this->y);
			}
			
			$dmg = ceil($this->fallStart - $this->y - 3);
			if($dmg > 0){
				$this->harm($dmg, "fall");
			}
		}
	}
	
	public function canBeShot(){
		return $this->isPlayer();
	}
	
	public function updateFallState($fallTick){
		if($this->onGround && $this->fallDistance > 0){
			$this->fall();
			$this->fallDistance = 0;
		}elseif($fallTick < 0){
			$this->fallDistance -= $fallTick;
		}
		
	}
	
	public function updateMovement()
	{
		if($this->closed === true){
			return false;
		}
		$now = microtime(true);
		if($this->isStatic === false && ($this->lastX != $this->x || $this->lastY != $this->y || $this->lastZ != $this->z || $this->lastYaw != $this->yaw || $this->lastPitch != $this->pitch || $this->lastHeadYaw != $this->headYaw || $this->forceUpdateMovement)){
			$this->forceUpdateMovement = false;
			if($this->class === ENTITY_PLAYER){
				if($this->server->api->handle("entity.move", $this) === false){
					if($this->class === ENTITY_PLAYER){
						$this->player->teleport(new Vector3($this->last[0], $this->last[1], $this->last[2]), $this->last[3], $this->last[4]);
					}
				} else{
					$players = $this->server->api->player->getAll($this->level);
					if($this->player instanceof Player){
						$this->updateLast();
						unset($players[$this->player->CID]);
						$pk = new MovePlayerPacket();
						$pk->eid = $this->eid;
						$pk->x = $this->x;
						$pk->y = $this->y - ($this->player->isSleeping ? 1.7375 : 0);
						$pk->z = $this->z;
						$pk->yaw = $this->yaw;
						$pk->pitch = $this->pitch;
						$pk->bodyYaw = $this->headYaw;
						foreach($players as $player){
							if($player->hasEntity($this) && !$this->player->isInvisibleFor($player)) $player->entityQueueDataPacket(clone $pk);
						}
					}
				}
			}
		}
		$this->lastUpdate = $now;
	}
	
	/**
	 * Handle fall out of world
	 */
	public function outOfWorld(){
		if($this->isPlayer()){
			$this->health = 0;
			$this->makeDead("void");
		}else{
			$this->close();
		}
	}
	
	public function getEyeHeight(){ //TODO in vanilla player's eyeHeight is 0.12
		return $this->isPlayer() ? $this->height - 0.12 : $this->height * 0.85;
	}
	
	public function interactWith(Entity $e, $action)
	{
		if($this->isPlayer() and ($this->server->api->getProperty("pvp") == false or $this->server->difficulty <= 0 or ($this->player->gamemode & 0x01) === 0x01)){
			return false;
		}

		if($action === InteractPacket::ACTION_ATTACK && $e->isPlayer()){
			$slot = $e->player->getHeldItem();
			$damage = $slot->getDamageAgainstOf($this);
			$this->harm($damage, $e->eid);
			$slot->hurtEnemy($this, $e->player); //TODO dont try destroying if harm was cancelled?
			return true;
		}
		return false;
	}

	public function getDirection()
	{
		$rotation = ($this->yaw - 90) % 360;
		if($rotation < 0){
			$rotation += 360.0;
		}
		if((0 <= $rotation and $rotation < 45) or (315 <= $rotation and $rotation < 360)){
			return 2; //x-
		} elseif(45 <= $rotation and $rotation < 135){
			return 3; //z-
		} elseif(135 <= $rotation and $rotation < 225){
			return 0; //x+
		} elseif(225 <= $rotation and $rotation < 315){
			return 1; //z+
		} else{
			return null;
		}
	}

	/**
	 * METADATA VALUES
	 * *****************
	 * Types: Get input type of <value>
	 * 0 -> Byte
	 * 1 -> Short
	 * 2 -> Integer
	 * 3 -> Float
	 * 4 -> Length of <value>, Short
	 * 5 -> [Short, Byte, Short]
	 * 6 -> [Integer, Integer, Integer]
	 * *****************
	 * 0 => ["type" => 0, "value" => $flags] --> DATA_FLAGS
	 * 1 => ["type" => 1, "value" => $this->air] --> Entity Air
	 * 14 => ["type" => 0, "value" => 1] --> IsBaby, value: 0 => false, 1 => true
	 * 16 => ["type" => 0, "value" => 0] --> Fuse(TNT), Saddled(Pig), Creeper(29 ticks before explosion)
	 * 17 => ["type" => 6, "value" => [0, 0, 0]] --> Bed Position <?>
	 *
	 *
	 * DATA FLAGS IDS
	 * 0 - fire
	 * 1 - crouching
	 * 4 - inAction(ex.: using a bow)
	 */
	public function getMetadata()
	{
		$flags = 0;
		$flags ^= $this->fire > 0 ? 0b1 : 0;
		$flags ^= ($this->crouched ? 0b1 : 0) << 1;
		$flags ^= ($this->inAction ? 0b1 : 0) << 4;
		$d = [
			0 => [
				"type" => 0,
				"value" => $flags
			],
			1 => [
				"type" => 1,
				"value" => (int)$this->air
			],
			14 => [
				"type" => 0,
				"value" => 0
			],
			16 => [
				"type" => 0,
				"value" => 0
			],
			17 => [
				"type" => 6,
				"value" => [
					0,
					0,
					0
				]
			]
		];
		$d[16]["value"] = $this->data["State"];
		if($this->isPlayer()){
			if($this->player->isSleeping !== false){
				$d[16]["value"] = 2;
				$d[17]["value"] = [
					$this->player->isSleeping->x,
					$this->player->isSleeping->y,
					$this->player->isSleeping->z
				];
			}
		}
		return $d;
	}

	public function updateMetadata()
	{
		$this->server->api->dhandle("entity.metadata", $this);
	}

	public function sendLinkPackets(Player $player){
		if($this->rider != 0){
			$e = $this->level->entityList[$this->rider] ?? false;
			
			if($e != false) {
				$e->spawn($player);
				$player->eventHandler(["rider" => $this->rider, "riding" => $this->eid, "type" => 0], "entity.link");
			}
		}
		if($this->riding != 0){
			$e = $this->level->entityList[$this->riding] ?? false;
			
			if($e != false) {
				$e->spawn($player);
				$player->eventHandler(["rider" => $this->eid, "riding" => $this->riding, "type" => 0], "entity.link");
			}
		}
	}
	
	/**
	 * @param Player $player
	 * @return boolean
	 */
	public function spawn(Player $player)
	{
		if($player->eid === $this->eid or $this->closed !== false or ($player->level !== $this->level and $this->class !== ENTITY_PLAYER)){
			return false;
		}
		if($this->class != ENTITY_PLAYER) return false;
		if($this->player->connected !== true or $this->player->spawned === false) return false;
		if(!$player->hasEntity($this)){
			$player->addEntity($this);
			if($this->level != $player->entity->level || $this->player->gamemode === SPECTATOR) $this->player->setInvisibleFor($player, true, send:false);
			$pk = new AddPlayerPacket();
			$pkm = new MovePlayerPacket();
			$pk->clientID = 0; // $this->player->clientID;
			$pk->username = $this->player->username;
			$pkm->eid = $pk->eid = $this->eid;
			
			if($this->player->isInvisibleFor($player)){
				$pkm->x = $pk->x = -256;
				$pkm->y = $pk->y = 128;
				$pkm->z = $pk->z = -256;
				$pkm->yaw = $pk->yaw = 0;
				$pkm->pitch = $pk->pitch = 0;
				$pkm->bodyYaw = 0;
			}else{
				$pkm->x = $pk->x = $this->x;
				$pkm->y = $pk->y = $this->y;
				$pkm->z = $pk->z = $this->z;
				$pkm->yaw = $pk->yaw = $this->yaw;
				$pkm->pitch = $pk->pitch = $this->pitch;
				$pkm->bodyYaw = $this->headYaw;
			}
			
			$pk->itemID = 0;
			$pk->itemAuxValue = 0;
			$pk->metadata = $this->getMetadata();
			$player->entityQueueDataPacket($pk);
			//AddPlayerPacket doesnt read yaw correctly in vanilla(uses PacketUtil::Rot_degreesToChar instead of PacketUtil::Rot_charToDegrees)
			$player->entityQueueDataPacket($pkm);
			
			$pk = new PlayerEquipmentPacket();
			$pk->eid = $this->eid;
			$pk->item = $this->player->getSlot($this->player->slot)->getID();
			$pk->meta = $this->player->getSlot($this->player->slot)->getMetadata();
			$pk->slot = 0;
			$player->entityQueueDataPacket($pk);
			$this->player->sendArmor($player);
			
			$this->sendLinkPackets($player);
			return true;
		}
		return false;
	}
	
	public function counterUpdate(){
		if($this->knockbackTime > 0){
			--$this->knockbackTime;
		}
		
		if($this->moveTime > 0){
			--$this->moveTime;
		}
		if($this->lookTime > 0){
			--$this->lookTime;
		}
		if($this->idleTime > 0){
			--$this->idleTime;
		}
		
		
		if($this->inAction){
			++$this->inActionCounter;
		}
	}
	
	public function close()
	{
		if($this->closed === false){
			$this->closed = true;
			
			
			$rider = $this->level->entityList[$this->rider] ?? false;
			if($rider != false) $rider->setRiding(null);
			$this->setRiding(null);
			
			$this->server->api->entity->remove($this->eid);
		}
	}

	public function __destruct()
	{
		$this->close();
	}

	public function getEID()
	{
		return $this->eid;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function look($pos2)
	{
		$pos = $this->getPosition();
		$angle = Utils::angle3D($pos2, $pos);
		$this->yaw = $angle["yaw"];
		$this->pitch = $angle["pitch"];
	}

	public function updateAABB()
	{
		$this->boundingBox->setBounds(
			$this->x - $this->radius, $this->y - $this->yOffset, $this->z - $this->radius, 
			$this->x + $this->radius, $this->y + $this->height - $this->yOffset, $this->z + $this->radius
		);
	}

	public function updatePosition()
	{
		$this->sendMoveUpdate();
		$this->updateAABB();
	}
	
	public function setPosition(Vector3 $pos, $yaw = false, $pitch = false, $headYaw = false)
	{
		$this->x = $pos->x;
		$this->y = $pos->y;
		$this->z = $pos->z;
		if($yaw !== false){
			$this->yaw = $yaw;
		}
		if($pitch !== false){
			$this->pitch = $pitch;
		}
		
		if($headYaw !== false) $this->headYaw = $headYaw;
	}
	public function inBlockNonVector($x, $y, $z, $radius = 0.8)
	{
		return $y == ceil($this->y) || $y == (ceil($this->y)+1) && max(abs($x - ($this->x-0.5)), abs($z - ($this->z-0.5)));
	}
	public function inBlock(Vector3 $block, $radius = 0.8)
	{
		$me = new Vector3($this->x - 0.5, $this->y, $this->z - 0.5);
		if(($block->y == (ceil($this->y)) or $block->y == (ceil($this->y) + 1)) and $block->maxPlainDistance($me) < $radius){
			return true;
		}
		return false;
	}

	public function touchingBlock(Vector3 $block, $radius = 0.9)
	{
		$me = new Vector3($this->x - 0.5, $this->y, $this->z - 0.5);
		if(($block->y == (((int) $this->y) - 1) or $block->y == ((int) $this->y) or $block->y == (((int) $this->y) + 1)) and $block->maxPlainDistance($me) < $radius){
			return true;
		}
		return false;
	}

	public function isSupport(Vector3 $pos, $radius = 1)
	{
		$me = new Vector2($this->x - 0.5, $this->z - 0.5);
		$diff = $this->y - $pos->y;
		if($me->distance(new Vector2($pos->x, $pos->z)) < $radius and $diff > - 0.7 and $diff < 1.6){
			return true;
		}
		return false;
	}

	public function resetSpeed()
	{
		$this->speedMeasure = array(0, 0, 0, 0, 0, 0, 0);
	}

	public function getSpeed()
	{
		return $this->speed;
	}

	public function getSpeedMeasure()
	{
		return array_sum($this->speedMeasure) / count($this->speedMeasure);
	}

	public function calculateVelocity()
	{
		$diffTime = max(0.05, abs(microtime(true) - $this->last[5]));
		$origin = new Vector2($this->last[0], $this->last[2]);
		$final = new Vector2($this->x, $this->z);
		$speedX = ($this->last[0] - $this->x) / $diffTime;
		$speedY = ($this->last[1] - $this->y) / $diffTime;
		$speedZ = ($this->last[2] - $this->z) / $diffTime;
		if($this->speedX != $speedX or $this->speedY != $speedY or $this->speedZ != $speedZ){
			$this->speedX = $speedX;
			$this->speedY = $speedY;
			$this->speedZ = $speedZ;
			$this->server->api->handle("entity.motion", $this);
		}
		$this->speed = $origin->distance($final) / $diffTime;
		unset($this->speedMeasure[key($this->speedMeasure)]);
		$this->speedMeasure[] = $this->speed;
	}
	/**
	 * @return array
	 */
	public function createSaveData(){
		$data = [
			"id" => $this->type,
			"Health" => $this->health,
			"Pos" => [
				0 => $this->x,
				1 => $this->y,
				2 => $this->z,
			],
			"Rotation" => [
				0 => $this->yaw,
				1 => $this->pitch,
			],
			"speedX" => $this->speedX,
			"speedY" => $this->speedY,
			"speedZ" => $this->speedZ,
			
		];
		if($this->class === ENTITY_OBJECT){
			$data["TileX"] = $this->x;
			$data["TileY"] = $this->y;
			$data["TileZ"] = $this->z;
		}
		if($this->class === ENTITY_FALLING){
			$data["Tile"] = $this->data["Tile"];
		}
		if($this->class === ENTITY_ITEM){
			$data["Item"] = [
				"id" => $this->itemID,
				"Damage" => $this->meta,
				"Count" => $this->stack,
			];
			
			$data["id"] = 64; //ty shoghicp
		}
		return $data;
	}
 
	public function updateLast()
	{
		$this->lastX = $this->x;
		$this->lastY = $this->y;
		$this->lastZ = $this->z;
		$this->lastYaw = $this->yaw;
		$this->lastPitch = $this->pitch;
		$this->lastTime = microtime(true);
		$this->lastHeadYaw = $this->headYaw;
		$this->lastSpeedZ = $this->speedZ;
		$this->lastSpeedY = $this->speedY;
		$this->lastSpeedX = $this->speedX;
	}

	public function getPosition($round = false)
	{
		return !isset($this->position) ? false : ($round === true ? array_map("floor", $this->position) : $this->position);
	}

	public function harm($dmg, $cause = "generic", $force = false)
	{
		if (!$this->canBeAttacked) {
			return false;
		}
		if(is_numeric($cause) && ($entity = $this->server->api->entity->get($cause)) != false){
			if($this->attackTimeout > 0) return false;
			if(!$entity->isPlayer()){
				switch($this->server->difficulty){
					//case 0: looks like mobs also have 0 attack dmg in peaceful mode
					//	$dmg = 0;
					//	break;
					case 1:
						$dmg = (int)($dmg / 3) + 1;
						break;
					case 3:
						$dmg = 3 * (int)($dmg / 2);
						break;
				}
			}
		}
		
		$dmg = $this->applyArmor($dmg, $cause);
		$ret = $this->setHealth(max(- 128, $this->getHealth() - ((int) $dmg)), $cause, $force);
		
		if($ret && is_numeric($cause) && ($entity = $this->server->api->entity->get($cause)) != false){ //TODO simplify
			$this->attackTimeout = 10;
		}
		
		if ($ret != false && $this->hasKnockback && is_numeric($cause) && ($entity = $this->server->api->entity->get($cause)) != false) {
			
			$d = $entity->x - $this->x;

			for ($d1 = $entity->z - $this->z; $d * $d + $d1 * $d1 < 0.0001; $d1 = (lcg_value() - lcg_value()) * 0.01) {
				$d = (lcg_value() - lcg_value()) * 0.01;
			}
			
			$this->knockBack($d, $d1);
			if($this->isPlayer()){
				$pk = new SetEntityMotionPacket();
				$pk->eid = $this->eid;
				$pk->speedX = $this->speedX;
				$pk->speedY = $this->speedY;
				$pk->speedZ = $this->speedZ;
				$this->player->directDataPacket($pk, 0);
			}
			$this->knockbackTime = 10;
		}

		return $ret;
	}

	public function setState($v)
	{
		$this->state = $v;
		$this->data["State"] = $v;
		$this->updateMetadata();
	}

	public function getState()
	{
		return $this->state;
	}

	public function heal($health, $cause = "generic")
	{
		return $this->setHealth(min(20, $this->getHealth() + ((int) $health)), $cause, allowHarm: false);
	}
	
	public function setPos($x, $y, $z){
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
		
		$this->boundingBox->minX = $x - $this->radius;
		$this->boundingBox->minY = ($y - $this->yOffset); //TODO what is this + $this->ySize;
		$this->boundingBox->minZ = $z - $this->radius;
		
		$this->boundingBox->maxX = $x + $this->radius;
		$this->boundingBox->maxY = $this->boundingBox->minY + $this->height;
		$this->boundingBox->maxZ = $z + $this->radius;
	}
	
	/**
	 * Sets entity that is being ridden by this entity.
	 * @param ?Entity $riding
	 */
	public function setRiding(?Entity $riding)
	{
		if($riding == null){
			if($this->riding != 0){
				$riding = $this->server->api->entity->get($this->riding);
				if($riding instanceof Entity){
					$riding->rider = 0;
				}else{
					ConsoleAPI::warn("SR Failed to get entity that is being ridden by $this");
				}
			}
			$this->riding = 0;
		}else{
			if(!isset($this->level->entityList[$riding->eid])){
				ConsoleAPI::warn("Tried linking $this with $riding that doesnt exist in the world!");
				return;
			}
			$this->riding = $riding->eid;
			$riding->rider = $this->eid;
			
		}
		$this->server->api->dhandle("entity.link", ["rider" => $this->eid, "riding" => $this->riding, "type" => 0]);
	}

	public function isPlayer()
	{
		return isset($this->player) && $this->player instanceof Player;
	}

	public function sendMoveUpdate()
	{
		if($this->class === ENTITY_PLAYER){
			//$this->player->teleport(new Vector3($this->x, $this->y, $this->z), false, false, true, false);
		}
	}

	public function moveEntityWithOffset($oX, $oY, $oZ)
	{
		$oX = $oX === 0 ? $this->speedX : ($this->getSpeedModifer() * $oX * $this->getSpeed());
		$oY = $oY <= 0 ? $this->speedY : (0.40);
		$oZ = $oZ === 0 ? $this->speedZ : ($this->getSpeedModifer() * $oZ * $this->getSpeed());
		$this->setVelocity($oX, $oY, $oZ);
	}

	public function getSpeedModifer()
	{
		return $this->speedModifer;
	}
	public function getArmorValue(){
		$pnts = 0;
		if($this->isPlayer()){
			foreach($this->player->armor as $slot => $part){
				if($part instanceof ArmorItem){
					$pnts += $part->getDamageReduceAmount();
				}
			}
		}
		return $pnts;
	}
	
	public function hurtArmor($dmg){
		if($this->isPlayer()){
			$v2 = (int)($dmg / 4);
			if($v2 < 1) $v2 = 1;
			
			foreach($this->player->armor as $slot => $part){
				$part->hurtAndBreak($v2, $this->player, helditem: false);
				if($part->count <= 0) $this->player->setArmor($slot, BlockAPI::getItem(0, 0, 0), send: false);
			}
			$this->player->sendArmor();
		}
	}
	
	public function applyArmor($damage, $cause){
		if(is_numeric($cause) || $cause === "explosion"){
			$var3 = 25 - $this->getArmorValue();
			$this->hurtArmor($damage); //uses damage without armor affection
			$var4 = $damage * $var3 + $this->carryoverDamage;
			$damage = $var4 / 25;
			$this->carryoverDamage = $var4 % 25;
		}
		return $damage;
	}
	
	public function setHealth($health, $cause = "generic", $force = false, $allowHarm = true)
	{
		$health = (int) $health;
		$harm = false;
		if($health < $this->health){
			$harm = true;
			$dmg = $this->health - $health;
			if(($this->class !== ENTITY_PLAYER or (($this->player instanceof Player) and (($this->player->gamemode & 0x01) === 0x00 or $force === true))) and ($this->dmgcounter[0] < microtime(true) or $this->dmgcounter[1] < $dmg) and !$this->dead){
				$this->dmgcounter[0] = microtime(true) + 0.5;
				$this->dmgcounter[1] = $dmg;
			} else{
				return false; // Entity inmunity
			}
		}elseif($health === $this->health and !$this->dead){
			$harm = true;
		}
		
		if($this->server->api->dhandle("entity.health.change", array(
			"entity" => $this,
			"eid" => $this->eid,
			"health" => $health,
			"cause" => $cause
		)) !== false or $force === true){
			
			$this->health = min(127, max(- 127, $health));
			if($harm === true && $allowHarm){
				$pk = new EntityEventPacket;
				$pk->eid = $this->eid;
				$pk->event = EntityEventPacket::ENTITY_DAMAGE;
				foreach($this->level->players as $p){
					if($p->hasEntity($this)) $p->entityQueueDataPacket(clone $pk);
				}
			}
			
			if($this->player instanceof Player){
				$pk = new SetHealthPacket();
				$pk->health = $this->health;
				if($this->player->hasEntity($this)) $this->player->entityQueueDataPacket($pk);
			}
			if($this->health <= 0 and $this->dead === false){
				$this->makeDead($cause);
			} elseif($this->health > 0){
				$this->dead = false;
			}
			return true;
		}
		return false;
	}

	public function setSize($w, $h)
	{
		$this->width = $w;
		$this->height = $h;
		$this->radius = $w / 2;
	}
	
	public function makeDead($cause){
		if($this->server->api->dhandle("entity.death", ["entity" => $this, "cause" => $cause]) === false) return false;
		$this->spawnDrops();
		$this->air = $this->maxAir;
		$this->fire = 0;
		$this->crouched = false;
		$this->fallY = false;
		$this->fallStart = false;
		$this->updateMetadata();
		$this->dead = true;
		if($this->player instanceof Player){
			$pk = new MoveEntityPacket_PosRot();
			$pk->eid = $this->eid;
			$pk->x = -256;
			$pk->y = 128;
			$pk->z = -256;
			$pk->yaw = 0;
			$pk->pitch = 0;
			foreach($this->level->players as $player){
				if($player->hasEntity($this) && $player != $this->player) $player->entityQueueDataPacket(clone $pk);
			}
		}else{
			$pk = new EntityEventPacket;
			$pk->eid = $this->eid;
			$pk->event = EntityEventPacket::ENTITY_DEAD;
			foreach($this->level->players as $player){
				if($player->hasEntity($this)) $player->entityQueueDataPacket(clone $pk);
			}
		}

		if($this->player instanceof Player){
			$this->player->blocked = true;
			$this->server->api->dhandle("player.death", [
				"player" => $this->player,
				"cause" => $cause
			]);
			if($this->server->api->getProperty("hardcore") == 1){ //poor player =<
				$this->server->api->ban->ban($this->player->username);
			}
		} else{
			if($this instanceof Painting || $this instanceof Minecart){ //TODO better fix
				$this->close();
			}
			else{
				$this->server->api->schedule(40, [$this, "close"], []);
			}
		}
	}
	
	public function getAttackDamage(){
		return 0;
	}
	
	public function setSpeed($s)
	{
		$this->speed = $s;
	}

	public function knockBack($d, $d1)
	{
		if($this->closed || $this->dead){
			return false;
		}
		$f = sqrt($d * $d + $d1 * $d1);
		$f1 = 0.4;
		$this->speedX /= 2;
		$this->speedY /= 2;
		$this->speedZ /= 2;
		$this->speedX -= ($d / $f) * $f1;
		$this->speedY += 0.4;
		$this->speedZ -= ($d1 / $f) * $f1;
		if($this->speedY > 0.4){
			$this->speedY = 0.4;
		}
	}

	public function getHealth()
	{
		return $this->health;
	}

	public function __toString()
	{
		return "Entity(eid={$this->eid},x={$this->x},y={$this->y},z={$this->z},level=" . $this->level->getName() . ",class={$this->class},type={$this->type})";
	}
	
	/**
	 * Debug
	 */
	public function printSpeed($add = ""){
		ConsoleAPI::debug("$add {$this->speedX}:{$this->speedY}:{$this->speedZ}");
	}
	
	/*
	 * Deprecated methods.
	 * Those methods were left only for compability with older plugins
	 */
	/**
	 *
	 * @deprecated Use {@link Entity::getHeightOf} or {@link Entity::getWidthOf} instead
	 * @throws Exception
	 */
	public static function getSizeOf($e)
	{
		throw new Exception("Use getHeightOf or getWidthOf method instead of this.");
	}
	
	
	/**
	 * @deprecated Use {@link Entity::getHeight} or {@link Entity::getWidth} instead
	 * @throws Exception
	 */
	public function getSize()
	{
		throw new Exception("Use getHeight or getWidth method instead of this.");
	}
	
	/**
	 * @deprecated use {@link Entity::setRiding} instead
	 */
	public function stopRiding(){
		$this->setRiding(null);
	}
}
