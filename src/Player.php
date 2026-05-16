<?php

class Player{
	const CHATMESSAGE_ORDER_CHANNEL = 1;
	const BLOCKUPDATE_ORDER_CHANNEL = 2;
	const ENTITY_ORDER_CHANNEL = 3;
	public static $blacklistedUsernames = [
		"rcon" => true,
		"console" => true,
		"server" => true,
		//windows my beloved
		"con" => true,
		"prn" => true,
		"aux" => true,
		"nul" => true,
		"com1" => true,
		"com2" => true,
		"com3" => true,
		"com4" => true,
		"com5" => true,
		"com6" => true,
		"com7" => true,
		"com8" => true,
		"com9" => true,
		"com0" => true,
		"lpt1" => true,
		"lpt2" => true,
		"lpt3" => true,
		"lpt4" => true,
		"lpt5" => true,
		"lpt6" => true,
		"lpt7" => true,
		"lpt8" => true,
		"lpt9" => true,
		"lpt0" => true
	];
	public static $experimentalHotbar = false;
	public static $allowDroppingSingleItems = true;
	public static $disableEmojisInChat = true;
	/** @var Config */
	public $data;
	/** @var Entity */
	public $entity = false;
	public $auth = false;
	public $CID;
	public $MTU;
	public $spawned = false;
	/**
	 * Stores player inventory. Should not be accessed directly: use Player::addItem/Player::removeItem/Player::setSlot for setting and Player::getSlot for getting.
	 * @var array
	 */
	public $inventory;
	public $slot;
	public $hotbar;
	public $curHotbarIndex = 0;
	/**
	 * @var Item[]
	 */
	public $armor = [];
	public $loggedIn = false;
	public $gamemode;
	public $gmKickSoon = false;
	public $lastBreak, $connectionStartedAt;
	public $windowCnt = 2;
	public $windows = [];
	public $blocked = true;
	public $saveInventory = true;
	public $achievements = [];
	public $chunksLoaded = [];
	public $lastCorrect;
	public $craftingItems = [];
	public $toCraft = [];
	public $loginData = [];
	/** @var Level */
	public $level;
	private $server;
	private $receiveCount = -1;
	private $buffer;
	private $bufferLen = 0;
	private $evid = [];
	private $lastMovement = 0;
	private $forceMovement = false;
	private $timeout;
	private $connected = true;
	private $clientID;
	private $ip;
	private $port;
	private $counter = [0, 0, 0, 0];
	public $username;
	public $iusername;
	private $eid = false;
	private $startAction = false;
	public $isSleeping = false;
	public $sleepingTime = 0;
	private $chunksOrder = [];
	private $lastMeasure = 0;
	private $bandwidthRaw = 0;
	private $bandwidthStats = [0, 0, 0];
	private $lag = [];
	private $lagStat = 0;
	public $spawnPosition;
	private $packetLoss = 0;
	private $lastChunk = false;
	private $bigCnt;
	private $packetStats;
	private $chunkCount = [];
	private $received = [];
	
	/**
	 * Stores packets that must be always received by clients.
	 * @var array
	 */
	public $packetAlwaysRecoverQueue = [];
	public $recoveryQueue = [];
	private $receiveQueue = [];
	private $resendQueue = [];
	private $ackQueue = [];
	
	public $slotCount = 7;
	public $bedPosition = null;
	
	public $entityMovementQueue;
	public $entityMovementQueueLength = 0;
	public $entityMovementQueueSeqIndex = 0;
	
	/**
	 * @var RakNetPacket
	 */
	public $entityDataQueue;
	public $entityDataQueueLength = 0;
	public $entityDataQueueOrderIndex = 0;
	
	public $blockUpdateQueue;
	public $blockUpdateQueueLength = 0;
	public $blockUpdateQueueOrderIndex = 0;
	
	public $chatMessagesQueue;
	public $chatMessagesQueueLength = 0;
	public $chatMessagesOrderIndex = 0;
	
	
	public $chunkDataSent = [];
	
	/**
	 * Sent by a client while it is linked to some entity.
	 * @var boolean $isJumping
	 * @var boolean $isSneaking
	 */
	public $isJumping, $isSneaking;

	/**
	 * should server send inventory to a client or not. Should not be changed directly: use Player::sendInventory() to change to true.
	 * @var boolean
	 */
	public $sendingInventoryRequired = false;
	public $expectedSetSlotPackets = [];
	public $expectedSetSlotIndex = -1;
	public $lastExpectedSetSlotIndexReceived = -1;
	
	private $lastPing = -1;
	
	/**
	 * Stores local entity ids. Format: global => local.
	 * Should be modified only by Player::addEntity or Player::removeEntity.
	 * Entity ID 0 is reserved for non-existent entities.
	 * @var array
	 */
	public $global2localEID = [0 => 0];
	/**
	 * Stores global entity ids. Format: local => global.
	 * Should be modified only by Player::addEntity or Player::removeEntity.
	 * @var array
	 */
	public $local2GlobalEID = [0 => 0];
	/**
	 * Stores last local entity id. Increments every time Player::addEntity is called.
	 * @var array
	 */
	public $lastLocalEID = 0;
	
	public $invisibleFor = [];
	
	/**
	 * @param integer $clientID
	 * @param string $ip
	 * @param integer $port
	 * @param integer $MTU
	 */
	public function __construct($clientID, $ip, $port, $MTU){
		$this->bigCnt = 0;
		$this->MTU = min($MTU, 1100);
		$this->server = ServerAPI::request();
		$this->lastBreak = microtime(true);
		$this->connectionStartedAt = microtime(true);
		$this->clientID = $clientID;
		$this->CID = PocketMinecraftServer::clientID($ip, $port);
		$this->ip = $ip;
		$this->port = $port;
		$this->spawnPosition = $this->server->spawn;
		$this->timeout = microtime(true) + 20;
		$this->inventory = [];
		$this->armor = [];
		$this->gamemode = $this->server->gamemode;
		$this->saveInventory = ($this->gamemode & 1) == SURVIVAL; //survival, adventure
		$this->level = $this->server->api->level->getDefault();
		$this->slot = 0;
		$this->hotbar = [0, 1, 2, 3, 4, 5, 6, 7, 8];
		$this->packetStats = [0, 0];
		
		$this->buffer = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
		$this->buffer->data = [];
		
		$this->entityDataQueue = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
		$this->entityDataQueue->data = [];
		
		$this->entityMovementQueue = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
		$this->entityMovementQueue->data = [];
		
		$this->blockUpdateQueue = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
		$this->blockUpdateQueue->data = [];
		
		$this->chatMessagesQueue = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
		$this->chatMessagesQueue->data = [];
		
		//$this->server->schedule(20 * 60, [$this, "clearQueue"], [], true);
		
		$this->evid[] = $this->server->event("server.close", [$this, "close"]);
		console("[DEBUG] New Session started with $ip:$port. MTU {$this->MTU}, Client ID {$this->clientID}", true, true, 2);
	}
	
	/**
	 * Gives a new local id for entity. 
	 * @param Entity $e
	 * @return boolean
	 */
	public function addEntity(Entity $e){
		$local = ++$this->lastLocalEID;
		$this->global2localEID[$e->eid] = $local;
		$this->local2GlobalEID[$local] = $e->eid;
		//ConsoleAPI::info("Adding Entity {$e->eid} => {$local}");
		return true;
	}
	
	public function hasEntity(Entity $e){
		return isset($this->global2localEID[$e->eid]);
	}
	
	/**
	 * Removes an entity.
	 * @param Entity $e
	 * @return boolean
	 */
	public function removeEntity(Entity $e){
		$local = $this->global2localEID[$e->eid] ?? null;
		if($local == null) return false;
		unset($this->global2localEID[$e->eid]);
		unset($this->local2GlobalEID[$local]);
		//ConsoleAPI::info("Removing Entity {$e->eid} => {$local}");
		return true;
	}
	
	public function __get($name){
		if(isset($this->{$name})){
			return ($this->{$name});
		}
		return null;
	}
	
	/**
	 * @param int $slot inventory slot. -1 - drop
	 * @param int $id
	 * @param int $meta
	 * @param int $count
	 */
	public function addExpectedSetSlotPacket(int $slot, int $id, int $meta, int $count){
		ConsoleAPI::debug("Adding expected set slot: $slot $id $meta $count");
		$this->expectedSetSlotPackets[++$this->expectedSetSlotIndex]["$slot $id $meta $count"] = $this->server->ticks;
	}
	
	public function isExpectedSetSlot(int $slot, Item $item){
		$index = "$slot {$item->getID()} {$item->getMetadata()} {$item->count}";
		foreach($this->expectedSetSlotPackets as $i => $stuff){
			$a = key($stuff);
			
			if($index == $a){
				return $i;
			}
		}
		return false;
	}
	
	public function removeExpectedSetSlot(int $index){
		unset($this->expectedSetSlotPackets[$index]);
	}
	
	public function getSpawn(){
		return $this->spawnPosition;
	}
	
	public function setBedPosition(?Position $bedPos){
		if($bedPos != null) $this->bedPosition = [$bedPos->level->getName(), (int)$bedPos->x, (int)$bedPos->y, (int)$bedPos->z];
		else $this->bedPosition = null;
		$this->data->set("bed-position", $this->bedPosition);
	}
	
	
	/**
	 * @param Vector3 $pos position of the <b>top block</b> of the bed
	 * 
	 * @return boolean
	 */
	public function sleepOn(Position $pos){
		foreach($this->server->api->player->getAll($this->level) as $p){
			if($p->isSleeping instanceof Vector3){
				if($pos->distance($p->isSleeping) <= 0.1){
					return false;
				}
			}
		}
		
		$this->setBedPosition($pos);
		$this->isSleeping = $pos;
		$this->sleepingTime = 0;
		$meta = $this->level->level->getBlockDamage($pos->x, $pos->y, $pos->z);
		$xoff = BedBlock::PLAYER_BED_OFFS_X[$meta & 3];
		$zoff = BedBlock::PLAYER_BED_OFFS_Z[$meta & 3];
		$this->teleport(new Position($pos->x + $xoff, $pos->y + 0.9375, $pos->z + $zoff, $this->level), false, false, false, false);
		//TODO change player hitbox size after he starts sleeping
		if($this->entity instanceof Entity){
			$this->entity->forceUpdateMovement = true;
			$this->entity->updateMovement();
			$this->entity->updateMetadata();
		}
		
		return true;
	}
	
	public function setSlotCount($cnt){
		$this->slotCount = $cnt;
		$this->data->set("slot-count", $this->slotCount);
	}
	
	/**
	 * @param Vector3 $pos
	 * @param float|boolean $yaw
	 * @param float|boolean $pitch
	 * @param float|boolean $terrain
	 * @param float|boolean $force
	 *
	 * @return boolean
	 */
	public function teleport(Vector3 $pos, $yaw = false, $pitch = false, $terrain = true, $force = true){
		if($this->entity instanceof Entity and $this->level instanceof Level){
			$this->entity->check = false;
			if($yaw === false){
				$yaw = $this->entity->yaw;
			}
			if($pitch === false){
				$pitch = $this->entity->pitch;
			}
			if($this->server->api->dhandle("player.teleport", ["player" => $this, "target" => $pos]) === false){
				$this->entity->check = true;
				return false;
			}

			if($pos instanceof Position and $pos->level instanceof Level and $pos->level !== $this->level){
				if($this->server->api->dhandle("player.teleport.level", ["player" => $this, "origin" => $this->level, "target" => $pos->level]) === false){
					$this->entity->check = true;
					return false;
				}

				foreach($this->level->entityList as $e){
					if($e->eid !== $this->entity->eid){
						if($e->isPlayer()){
							$this->setInvisibleFor($e->player, true);
							$e->player->setInvisibleFor($this, true);
						}else{
							$pk = new RemoveEntityPacket();
							$pk->eid = $e->eid;
							$this->entityQueueDataPacket($pk);
							$this->removeEntity($e);
						}
					}
				}

				$this->level->freeAllChunks($this);
				unset($this->level->players[$this->CID]);
				unset($this->level->entityListPositioned["{$this->entity->chunkX} {$this->entity->chunkZ}"][$this->eid]);
				unset($this->level->entityList[$this->eid]);
				$this->level = $pos->level;
				$this->entity->level = $this->level;
				$this->level->entityList[$this->entity->eid] = $this->entity;
				$this->level->players[$this->CID] = $this;
				$this->chunksLoaded = [];
				$this->server->api->entity->spawnToAll($this->entity);
				foreach($this->level->entityList as $e){
					if($e->eid !== $this->entity->eid){
						if(!$e->isPlayer()){
							$e->spawn($this);
						}
					}
				}
							
				$pk = new SetTimePacket;
				$pk->time = $this->level->getTime();
				$this->dataPacket($pk);
				$terrain = true;
				foreach($this->level->players as $player){
					if($player !== $this and $player->entity instanceof Entity){
						$player->setInvisibleFor($this, false);
						$this->setInvisibleFor($player, false);

						$pk = new PlayerEquipmentPacket;
						$pk->eid = $this->eid;
						$pk->item = $this->getSlot($this->slot)->getID();
						$pk->meta = $this->getSlot($this->slot)->getMetadata();
						$pk->slot = 0;
						$player->entityQueueDataPacket($pk);
						$this->sendArmor($player);

						$pk = new PlayerEquipmentPacket;
						$pk->eid = $player->eid;
						$pk->item = $player->getSlot($player->slot)->getID();
						$pk->meta = $player->getSlot($player->slot)->getMetadata();
						$pk->slot = 0;
						$this->entityQueueDataPacket($pk);
						$player->sendArmor($this);
					}
				}
				
				$resyncpos = new Position($pos->x, $pos->y, $pos->z, $pos->level);
			}else{
				$resyncpos = new Vector3($pos->x, $pos->y, $pos->z);
			}
			
			$this->lastCorrect = $resyncpos;
			$this->entity->fallY = false;
			$this->entity->fallStart = false;
			$this->entity->notOnGroundTicks = 0;
			$this->entity->setPosition($pos, $yaw, $pitch);
			$this->entity->resetSpeed();
			$this->entity->updateLast();
			$this->entity->calculateVelocity();
			if($terrain === true){
				$this->orderChunks();
				if($this->spawned) $this->getNextChunk($this->level);
			}
			$this->entity->check = true;
			if($force === true){
				$this->forceMovement = $resyncpos;
			}
		}
		
		$pk = new SetEntityMotionPacket();
		$pk->eid = $this->eid;
		$pk->speedX = 0;
		$pk->speedY = 0;
		$pk->speedZ = 0;
		$this->dataPacketAlwaysRecover($pk);
		
		$pk = new MovePlayerPacket;
		$pk->eid = $this->eid;
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$pk->bodyYaw = $yaw;
		$pk->pitch = $pitch;
		$pk->yaw = $yaw;
		$this->dataPacketAlwaysRecover($pk);
	}

	/**
	 * Sends data packet that wont be buffered. Packets are not ordered.
	 * @param RakNetDataPacket $packet
	 * @param int $reliability=0 unused
	 * @param bool $recover=true can the packet be recovered or not. If true the packet will be recovered only once. Otherwise, it won't be recovered at all.
	 * @returns int[]|false - array with seqNumber of the packet. false if player is not connected. empty array if cancelled by DataPacketSendEvent.
	 */
	public function directDataPacket(RakNetDataPacket $packet, $reliability = 0, $recover = true){
		if($this->connected === false) return false;
		if(EventHandler::callEvent(new DataPacketSendEvent($this, $packet)) === BaseEvent::DENY) return [];
		if(!$this->convertToLocalEIDPacket($packet)) return false;
		
		$packet->encode();
		$pk = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
		$pk->data[] = $packet;
		$pk->seqNumber = $this->counter[0]++;
		$pk->sendtime = microtime(true);
		if($recover !== false){
			$this->recoveryQueue[$pk->seqNumber] = $pk;
		}
		
		$this->send($pk);
		return [$pk->seqNumber];
	}
	
	/**
	 * Sends data packet that will be recovered only once. Packet MAY not arrive at all or arrive out of order.
	 * @param RakNetDataPacket $packet
	 */
	public function dataPacket(RakNetDataPacket $packet){
		if($this->connected === false) return false;
		if(EventHandler::callEvent(new DataPacketSendEvent($this, $packet)) === BaseEvent::DENY) return;
		if(!$this->convertToLocalEIDPacket($packet)) return false;
		
		$packet->encode();
		$len = strlen($packet->buffer) + 1;
		$MTU = $this->MTU - 24;
		if($len > $MTU){
			return $this->directBigRawPacket($packet);
		}
		
		if(($this->bufferLen + $len) >= $MTU){
			$this->sendBuffer();
		}

		$packet->messageIndex = $this->counter[3]++;
		$packet->reliability = RakNetInfo::RELIABILITY_RELIABLE;
		@$this->buffer->data[] = $packet;
		$this->bufferLen += 6 + $len;
		return [];
	}

	/**
	 * Sends data packet that will always be recovered. Packets MAY arrive out of order.
	 * @param RakNetDataPacket $packet
	 */
	public function dataPacketAlwaysRecover(RakNetDataPacket $packet){
		if($this->connected === false) return false;
		if(EventHandler::callEvent(new DataPacketSendEvent($this, $packet)) === BaseEvent::DENY) return false;
		if(!$this->convertToLocalEIDPacket($packet)) return false;
		
		$pk = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
		$pk->data[] = $packet;
		$pk->seqNumber = $this->counter[0]++;
		$pk->sendtime = microtime(true);
		
		$packet->encode();
		$len = strlen($packet->buffer) + 1;
		$MTU = $this->MTU - 24;
		if($len > $MTU){
			return $this->sendBigPacketAlwaysRecover($packet); //TODO fix
		}
		
		$packet->messageIndex = $this->counter[3]++;
		$packet->reliability = RakNetInfo::RELIABILITY_RELIABLE;
		
		$this->send($pk);
		$this->packetAlwaysRecoverQueue[$pk->seqNumber] = $pk;
		return [$pk->seqNumber];
	}
	public function sendChatMessagePacket_big(RakNetDataPacket $pk){
		$sendtime = microtime(true);
		$size = $this->MTU - 34;
		if($size <= 0) return false;
		$buffer = str_split($pk->buffer, $size);
		$bigCnt = $this->bigCnt;
		$this->bigCnt = ($this->bigCnt + 1) % 0x10000;
		$cnts = [];
		$bufCount = count($buffer);
		
		$orderIndex = $this->chatMessagesOrderIndex++;
		foreach($buffer as $i => $buf){
			$cnts[] = $count = $this->counter[0]++;
			
			$pk = new UnknownPacket;
			$pk->packetID = $pk->pid();
			$pk->reliability = RakNetInfo::RELIABILITY_RELIABLE_ORDERED;
			
			$pk->orderChannel = Player::CHATMESSAGE_ORDER_CHANNEL;
			$pk->orderIndex = $orderIndex;
			
			$pk->hasSplit = true;
			$pk->splitCount = $bufCount;
			$pk->splitID = $bigCnt;
			$pk->splitIndex = $i;
			$pk->buffer = $buf;
			$pk->messageIndex = $this->counter[3]++;
			
			$rk = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
			$rk->data[] = $pk;
			$rk->seqNumber = $count;
			$rk->sendtime = $sendtime;
			$this->packetAlwaysRecoverQueue[$count] = $rk;
			$this->send($rk);
		}
		return $cnts;
	}
	public function blockQueueDataPacket_big(RakNetDataPacket $pk){
		$sendtime = microtime(true);
		$size = $this->MTU - 34;
		if($size <= 0) return false;
		$buffer = str_split($pk->buffer, $size);
		$bigCnt = $this->bigCnt;
		$this->bigCnt = ($this->bigCnt + 1) % 0x10000;
		$cnts = [];
		$bufCount = count($buffer);
		
		$orderIndex = $this->blockUpdateQueueOrderIndex++;
		foreach($buffer as $i => $buf){
			$cnts[] = $count = $this->counter[0]++;
			
			$pk = new UnknownPacket;
			$pk->packetID = $pk->pid();
			$pk->reliability = RakNetInfo::RELIABILITY_RELIABLE_ORDERED;
			
			$pk->orderChannel = Player::BLOCKUPDATE_ORDER_CHANNEL;
			$pk->orderIndex = $orderIndex;
			
			$pk->hasSplit = true;
			$pk->splitCount = $bufCount;
			$pk->splitID = $bigCnt;
			$pk->splitIndex = $i;
			$pk->buffer = $buf;
			$pk->messageIndex = $this->counter[3]++;
			
			$rk = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
			$rk->data[] = $pk;
			$rk->seqNumber = $count;
			$rk->sendtime = $sendtime;
			$this->packetAlwaysRecoverQueue[$count] = $rk;
			$this->send($rk);
		}
		return $cnts;
	}
	
	public function entityQueueDataPacket_big(RakNetDataPacket $pk){
		$sendtime = microtime(true);
		$size = $this->MTU - 34;
		if($size <= 0) return false;
		$buffer = str_split($pk->buffer, $size);
		$bigCnt = $this->bigCnt;
		$this->bigCnt = ($this->bigCnt + 1) % 0x10000;
		$cnts = [];
		$bufCount = count($buffer);
		
		$orderIndex = $this->entityDataQueueOrderIndex++;
		foreach($buffer as $i => $buf){
			$cnts[] = $count = $this->counter[0]++;
			
			$pk = new UnknownPacket;
			$pk->packetID = $pk->pid();
			$pk->reliability = RakNetInfo::RELIABILITY_RELIABLE_ORDERED;
			
			$pk->orderChannel = Player::ENTITY_ORDER_CHANNEL;
			$pk->orderIndex = $orderIndex;
			
			$pk->hasSplit = true;
			$pk->splitCount = $bufCount;
			$pk->splitID = $bigCnt;
			$pk->splitIndex = $i;
			$pk->buffer = $buf;
			$pk->messageIndex = $this->counter[3]++;
			
			$rk = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
			$rk->data[] = $pk;
			$rk->seqNumber = $count;
			$rk->sendtime = $sendtime;
			$this->packetAlwaysRecoverQueue[$count] = $rk;
			$this->send($rk);
		}
		return $cnts;
	}
	
	public function sendBigPacketAlwaysRecover(RakNetDataPacket $pk){
		if($this->connected === false) return false;
		if(EventHandler::callEvent(new DataPacketSendEvent($this, $pk)) === BaseEvent::DENY) return;
		
		$pk->encode();
		$sendtime = microtime(true);
		$size = $this->MTU - 34;
		if($size <= 0) return false;
		$buffer = str_split($pk->buffer, $size);
		$bigCnt = $this->bigCnt;
		$this->bigCnt = ($this->bigCnt + 1) % 0x10000;
		$cnts = [];
		$bufCount = count($buffer);
		foreach($buffer as $i => $buf){
			$cnts[] = $count = $this->counter[0]++;
			
			$pk = new UnknownPacket;
			$pk->packetID = $pk->pid();
			$pk->reliability = RakNetInfo::RELIABILITY_RELIABLE;
			$pk->hasSplit = true;
			$pk->splitCount = $bufCount;
			$pk->splitID = $bigCnt;
			$pk->splitIndex = $i;
			$pk->buffer = $buf;
			$pk->messageIndex = $this->counter[3]++;
			
			$rk = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
			$rk->data[] = $pk;
			$rk->seqNumber = $count;
			$rk->sendtime = $sendtime;
			$this->packetAlwaysRecoverQueue[$count] = $rk;
			$this->send($rk);
		}
		return $cnts;
	}
	
	private function directBigRawPacket(RakNetDataPacket $packet){
		if($this->connected === false){
			return false;
		}

		$sendtime = microtime(true);

		$size = $this->MTU - 34;
		if($size <= 0) return false;
		$buffer = str_split($packet->buffer, $size);
		$bigCnt = $this->bigCnt;
		$this->bigCnt = ($this->bigCnt + 1) % 0x10000;
		$cnts = [];
		$bufCount = count($buffer);
		foreach($buffer as $i => $buf){
			$cnts[] = $count = $this->counter[0]++;

			$pk = new UnknownPacket;
			$pk->packetID = $packet->pid();
			$pk->reliability = RakNetInfo::RELIABILITY_RELIABLE;
			$pk->hasSplit = true;
			$pk->splitCount = $bufCount;
			$pk->splitID = $bigCnt;
			$pk->splitIndex = $i;
			$pk->buffer = $buf;
			$pk->messageIndex = $this->counter[3]++;

			$rk = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
			$rk->data[] = $pk;
			$rk->seqNumber = $count;
			$rk->sendtime = $sendtime;
			$this->recoveryQueue[$count] = $rk;
			$this->send($rk);
		}
		return $cnts;
	}

	public function send(RakNetPacket $packet){
		if($this->connected === true){
			$packet->ip = $this->ip;
			$packet->port = $this->port;
			$len = $this->server->send($packet);
			if($len > $this->MTU){
				ConsoleAPI::warn("Packet length is more than {$this->iusername}'s mtu!($len > {$this->MTU})");
			}
			$this->bandwidthRaw += $len;
		}
	}

	public function sendBuffer(){
		if($this->bufferLen > 0 and $this->buffer instanceof RakNetPacket){
			$this->buffer->seqNumber = $this->counter[0]++;
			$this->recoveryQueue[$this->buffer->seqNumber] = $this->buffer;
			$this->buffer->sendtime = microtime(true);
			$this->send($this->buffer);
		}
		$this->bufferLen = 0;
		$this->buffer = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
		$this->buffer->data = [];
	}

	/**
	 * @param integer $slot
	 *
	 * @return Item
	 */
	public function getSlot($slot){
		return $this->inventory[(int)$slot] ?? BlockAPI::getItem(AIR, 0, 0);
	}
	
	/**
	 * @param integer $slot
	 * @param Item $item
	 * @param boolean $send
	 *
	 * @return boolean
	 */
	public function setSlot($slot, Item $item, $send = true, $addexpected = true){
		$slot = (int) $slot;
		$old = $this->getSlot($slot);
		
		$this->inventory[$slot] = $item;
		
		if($send === true){
			$this->sendInventorySlot($slot);
		}
		
		if($addexpected){
			$this->addExpectedSetSlotPacket($slot, $item->getID(), $item->getMetadata(), $item->count);
		}
		
		return true;
	}

	/**
	 * @param Player|string|boolean|void $player
	 */
	public function sendArmor($player = false){
		$data = [
			"player" => $this,
			"eid" => $this->eid,
			"slots" => []
		];
		for($i = 0; $i < 4; ++$i){
			if(isset($this->armor[$i]) and ($this->armor[$i] instanceof Item) and $this->armor[$i]->getID() > AIR){
				$data["slots"][$i] = $this->armor[$i]->getID() !== AIR ? $this->armor[$i]->getID() - 256 : 0;
			}else{
				$this->armor[$i] = BlockAPI::getItem(AIR, 0, 0);
				$data["slots"][$i] = 255;
			}
		}
		if($player instanceof Player){
			if($player === $this){
				$pk = new ContainerSetContentPacket;
				$pk->windowid = 0x78; //Armor window id
				$pk->slots = $this->armor;
				$this->entityQueueDataPacket($pk);
			}else{
				$pk = new PlayerArmorEquipmentPacket;
				$pk->eid = $this->eid;
				$pk->slots = $data["slots"];
				$player->entityQueueDataPacket($pk);
			}
		}else{
			$this->server->api->dhandle("player.armor", $data);
		}
	}
	public $lastOrderX = 0;
	public $lastOrderZ = 0;
	
	public function orderChunks(){
		if(!($this->entity instanceof Entity) or $this->connected === false){
			return false;
		}
		$X = ((int)$this->entity->x) >> 4;
		$Z = ((int)$this->entity->z) >> 4;
		$this->chunksOrder = [];
		$this->lastOrderX = $X;
		$this->lastOrderZ = $Z;
		
		$v = new Vector2($X, $Z);
		for($x = 0; $x < 16; ++$x){
			for($z = 0; $z < 16; ++$z){
				$dist = $v->distance(new Vector2($x, $z));
				for($y = 0; $y < 8; ++$y){
					$d = $x . ":" . $y . ":" . $z;
					if(!isset($this->chunksLoaded[$d])){
						$this->chunksOrder[$d] = $dist;
					}
				}
			}
		}
		
		asort($this->chunksOrder);
	}
	
	public function loaddAllChunks(){
		for($x = 0; $x < 16; $x++){
			for($z = 0; $z < 16; $z++){
				$this->useChunk($x, $z);
			}
		}
	}
	
	public function useChunk($X, $Z){
		$Yndex = 0;
		for($iY = 0; $iY < 8; ++$iY){
			if(isset($this->chunksOrder["$X:$iY:$Z"])){
				unset($this->chunksOrder["$X:$iY:$Z"]);
				$this->chunksLoaded["$X:$iY:$Z"] = true;
				$Yndex |= 1 << $iY;
			}
		}
		$minX = ($X << 4);
		$maxX = (($X << 4) + 15);
		$minZ = ($Z << 4);
		$maxZ = (($Z << 4) + 15);
		
		$pk = new ChunkDataPacket;
		$pk->chunkX = $X;
		$pk->chunkZ = $Z;
		$pk->data = $this->level->getOrderedChunk($X, $Z, $Yndex);
		$cnt = $this->blockQueueDataPacket($pk);
		$this->chunkDataSent["$X:$Z"] = true;
		
		$tiles = $this->server->query("SELECT ID FROM tiles WHERE spawnable = 1 AND level = '{$this->level->getName()}' AND x >= $minX AND x <= $maxX AND z >= $minZ AND z <= $maxZ;");
		$this->lastChunk = false;
		if($tiles !== false and $tiles !== true){
			while(($tile = $tiles->fetchArray(SQLITE3_ASSOC)) !== false){
				$tile = $this->server->api->tile->getByID($tile["ID"]);
				if($tile instanceof Tile){
					$tile->spawn($this);
				}
			}
		}
		
		if($cnt === false){
			return false;
		}
	}
	
	public $currentChunk = false;
	
	public function getDestroySpeed($id, $meta){
		return $this->getHeldItem()->getDestroySpeed($id, $meta);
	}
	
	public function canDestroy($id, $meta){
		$mat = StaticBlock::getMaterial($id);
		if($mat->alwaysDestroyable) return true;
		
		return $this->getHeldItem()->canDestroySpecial($id, $meta);
	}
	
	public function onChunkReceived($blockX, $blockZ){
		$minX = $blockX;
		$maxX = $blockX + 15;
		$minZ = $blockZ;
		$maxZ = $blockZ + 15;
		$tiles = $this->server->query("SELECT ID, x, y, z FROM tiles WHERE level = '{$this->level->getName()}' AND x >= $minX AND x <= $maxX AND z >= $minZ AND z <= $maxZ;");
		$this->lastChunk = false;
		if($tiles !== false and $tiles !== true){
			while(($tile = $tiles->fetchArray(SQLITE3_ASSOC)) !== false){
				$tile = $this->server->api->tile->getByID($tile["ID"]);
				if($tile instanceof Tile){
					$tile->spawn($this);
				}
			}
		}
	}
	
	public function getNextChunk($world){
		if($this->connected === false or $world != $this->level){
			return false;
		}
		
		foreach($this->chunkCount as $count => $t){
			if(isset($this->packetAlwaysRecoverQueue[$count])){
				$this->server->schedule(MAX_CHUNK_RATE, [$this, "getNextChunk"], $world);
				return;
			}else{
				unset($this->chunkCount[$count]);
			}
		}

		if(is_array($this->lastChunk)){
			$this->onChunkReceived($this->lastChunk[0], $this->lastChunk[1]);
			$this->lastChunk = false;
		}

		$c = key($this->chunksOrder);
		$d = $c != null ? $this->chunksOrder[$c] : null;
		if($c === null or $d === null){
			$this->server->schedule(40, [$this, "getNextChunk"], $world);
			return false;
		}

		unset($this->chunksOrder[$c]);
		$this->chunksLoaded[$c] = true;
		$id = explode(":", $c);
		$X = $id[0];
		$Z = $id[2];
		$Y = $id[1];
		$x = $X << 4;
		$z = $Z << 4;
		$y = $Y << 4;
		$this->level->useChunk($X, $Z, $this);
		$Yndex = 1 << $Y;
		for($iY = 0; $iY < 8; ++$iY){
			if(isset($this->chunksOrder["$X:$iY:$Z"])){
				unset($this->chunksOrder["$X:$iY:$Z"]);
				$this->chunksLoaded["$X:$iY:$Z"] = true;
				$Yndex |= 1 << $iY;
			}
		}
		$pk = new ChunkDataPacket;
		$pk->chunkX = $X;
		$pk->chunkZ = $Z;
		$pk->data = $this->level->getOrderedChunk($X, $Z, $Yndex);
		$cnt = $this->blockQueueDataPacket($pk);
		$this->chunkDataSent["$X:$Z"] = true;
		
		if($cnt === false){
			return false;
		}
		
		$this->chunkCount = [];
		foreach($cnt as $i => $count){
			$this->chunkCount[$count] = true;
		}

		$this->lastChunk = [$x, $z];
		if($this->lastOrderX != ((int)$this->entity->x >> 4) || $this->lastOrderZ != ((int)$this->entity->z >> 4)){
			$this->orderChunks();
		}
		$this->server->schedule(MAX_CHUNK_RATE, [$this, "getNextChunk"], $world);
	}

	/**
	 * @param Vector3 $pos
	 */
	public function setSpawn(Vector3 $pos){
		if(!($pos instanceof Position)){
			$level = $this->entity->level;
		}else{
			$level = $pos->level;
		}
		$this->spawnPosition = new Position($pos->x, $pos->y + 1, $pos->z, $level);
		$pk = new SetSpawnPositionPacket;
		$pk->x = (int) $this->spawnPosition->x;
		$pk->y = (int) $this->spawnPosition->y;
		$pk->z = (int) $this->spawnPosition->z;
		$this->dataPacket($pk);
	}

	public function sendInventorySlot($s){
		$this->sendInventory();
		return true;
	}

	public function sendInventory(){
		$this->sendingInventoryRequired = true;
	}

	/**
	 * @param $type
	 * @param $damage
	 * @param $count
	 *
	 * @return boolean
	 */
	public function hasSpace($type, $damage, $count){
		$inv = $this->inventory;
		while($count > 0){
			$add = 0;
			foreach($inv as $s => $item){
				if($item->getID() === AIR){
					$add = min($item->getMaxStackSize(), $count);
					$inv[$s] = BlockAPI::getItem($type, $damage, $add);
					break;
				}elseif($item->getID() === $type and $item->getMetadata() === $damage){
					$add = min($item->getMaxStackSize() - $item->count, $count);
					
					if($add <= 0){
						continue;
					}
					$inv[$s] = BlockAPI::getItem($type, $damage, $item->count + $add);
					break;
				}
			}
			if($add <= 0){
				return false;
			}
			$count -= $add;
		}
		return true;
	}

	/**
	 * @param integer $slot
	 *
	 * @return Item
	 */
	public function getArmor($slot){
		return $this->armor[(int)$slot] ?? BlockAPI::getItem(AIR, 0, 0);
	}

	public function setArmor($slot, Item $armor, $send = true){
		$this->armor[(int) $slot] = $armor;
		if($send === true){
			$this->sendArmor($this);
		}
		return true;
	}
	
	public function invisibilityHandler(&$data, $event){
		$target = $data["target"];
		if($target == $this){
			$for = $data["for"];
			if($target->level != $for->level || $target->gamemode == SPECTATOR) {
				$data["status"] = true;
			}
		}
	}

	/**
	 * @param mixed &$data
	 * @param string $event
	 */
	public function eventHandler($data, $event){
		switch($event){
			case "entity.link":
				$pk = new SetEntityLinkPacket();
				$pk->rider = $data["rider"];
				$pk->riding = $data["riding"];
				$pk->type = 0;
				$this->entityQueueDataPacket($pk);
				break;
			case "tile.update":
				if($data->level === $this->level){
					if($data->class === TILE_FURNACE){
						foreach($this->windows as $id => $w){
							if($w === $data){
								$pk = new ContainerSetDataPacket;
								$pk->windowid = $id;
								$pk->property = 0; //Smelting
								$pk->value = floor($data->data["CookTime"]);
								$this->blockQueueDataPacket($pk);

								$pk = new ContainerSetDataPacket;
								$pk->windowid = $id;
								$pk->property = 1; //Fire icon
								$pk->value = $data->data["BurnTicks"];
								$this->blockQueueDataPacket($pk);
							}
						}
					}
				}
				break;
			case "tile.container.slot":
				if($data["tile"]->level === $this->level){
					foreach($this->windows as $id => $w){
						if($w === $data["tile"]){
							$pk = new ContainerSetSlotPacket;
							$pk->windowid = $id;
							$pk->slot = $data["slot"] + ($data["offset"] ?? 0);
							$pk->item = $data["slotdata"];
							$this->blockQueueDataPacket($pk);
						}
					}
				}
				break;
			case "player.armor":
				if($data["player"]->level === $this->level){
					if($data["eid"] === $this->eid){
						$this->sendArmor($this);
						break;
					}
					$pk = new PlayerArmorEquipmentPacket;
					$pk->eid = $data["eid"];
					$pk->slots = $data["slots"];
					$this->entityQueueDataPacket($pk);
				}
				break;
			case "player.pickup":
				if($data["eid"] === $this->eid){
					$pk = new TakeItemEntityPacket;
					$pk->eid = $data["eid"];
					$pk->target = $data["entity"]->eid;
					$this->entityQueueDataPacket($pk);
					
					if(($this->gamemode & 0x01) === 0x00){
						$this->addItem($data["entity"]->itemID, $data["entity"]->meta, $data["entity"]->stack, false);
					}
					switch($data["entity"]->itemID){
						case WOOD:
							AchievementAPI::grantAchievement($this, "mineWood");
							break;
						case DIAMOND:
							AchievementAPI::grantAchievement($this, "diamond");
							break;
						case LEATHER:
							AchievementAPI::grantAchievement($this, "leather");
							break;
					}
				}elseif($data["entity"]->level === $this->level){
					$pk = new TakeItemEntityPacket;
					$pk->eid = $data["eid"];
					$pk->target = $data["entity"]->eid;
					$this->entityQueueDataPacket($pk);
				}
				break;
			case "player.equipment.change":
				if($data["eid"] === $this->eid or $data["player"]->level !== $this->level){
					break;
				}
				$data["slot"] = 0;

				$pk = new PlayerEquipmentPacket;
				$pk->eid = $data["eid"];
				$pk->item = $data["item"]->getID();
				$pk->meta = $data["item"]->getMetadata();
				$pk->slot = $data["slot"];
				$this->entityQueueDataPacket($pk);

				break;
			case "entity.motion":
				if($data->eid === $this->eid || $data->level !== $this->level){
					break;
				}
				if(($data->speedX === 0 && $data->speedY === 0 && $data->speedZ === 0) || ($data->speedX === $data->lastSpeedX && $data->speedY === $data->lastSpeedY && $data->lastSpeedZ === $data->speedZ)){
					break;
				}
				$pk = new SetEntityMotionPacket;
				$pk->eid = $data->eid;
				$pk->speedX = $data->speedX;
				$pk->speedY = $data->speedY;
				$pk->speedZ = $data->speedZ;
				$this->entityQueueDataPacket($pk);
				break;
			case "entity.animate":
				$pk = new AnimatePacket;
				$pk->eid = $data["eid"];
				$pk->action = $data["action"]; //1 swing arm,
				$this->entityQueueDataPacket($pk);
				break;
			case "entity.metadata":
				if(!$this->hasEntity($data)) break;
				$eid = $data->eid;
				if($data->level === $this->level){
					$pk = new SetEntityDataPacket;
					$pk->eid = $eid;
					$pk->metadata = $data->getMetadata();
					$this->entityQueueDataPacket($pk);
				}
				break;
			case "entity.event":
				$eid = $data["entity"]->eid;
				if($data["entity"]->level === $this->level){
					$pk = new EntityEventPacket;
					$pk->eid = $eid;
					$pk->event = $data["event"];
					$this->entityQueueDataPacket($pk);
				}
				break;
			case "server.chat":
				if(($data instanceof Container) === true){
					if(!$data->check($this->username) and !$data->check($this->iusername)){
						return;
					}else{
						$message = $data->get();
						$this->sendChat(preg_replace('/\x1b\[[0-9;]*m/', "", $message["message"]), $message["player"]); //Remove ANSI codes from chat
					}
				}else{
					$message = (string) $data;
					$this->sendChat(preg_replace('/\x1b\[[0-9;]*m/', "", (string) $data)); //Remove ANSI codes from chat
				}
				break;
		}
	}

	/**
	 * @param $type
	 * @param $damage
	 * @param integer $count
	 * @param boolean $send
	 *
	 * @return boolean
	 */
	public function addItem($type, $damage, $count, $send = true, $addexpected = true, $drop=true){
		$toadd = BlockAPI::getItem($type, $damage, $count);
		foreach($this->inventory as $s => $item){ //force check the inventory for non-full stacks of this item first
			if($item->getID() === $type and $item->getMetadata() === $damage){
				$add = min($toadd->getMaxStackSize() - $item->count, $count);
				
				if($add <= 0){
					continue;
				}
				$item->count += $add;
				if($send) $this->sendInventorySlot($s);
				if($addexpected) $this->addExpectedSetSlotPacket($s, $item->getID(), $item->getMetadata(), $item->count);
				
				$count -= $add;
				if($count <= 0) return true;
			}
		}
		
		foreach($this->inventory as $s => $item){
			if($item->getID() === AIR){
				$add = min($toadd->getMaxStackSize(), $count);
				$this->inventory[$s] = BlockAPI::getItem($type, $damage, $add);
				
				if($send) $this->sendInventorySlot($s);
				
				if($addexpected) $this->addExpectedSetSlotPacket($s, $type, $damage, $add);
				$count -= $add;
				if($count <= 0) return true;
			}
		}
		if($count <= 0) return true;
		if(!$drop) return false;
		
		while($count > 0){
			$stacksz = min($toadd->getMaxStackSize(), $count);
			$count -= $stacksz;
			
			$f1 = 0.3;
			$sX = -sin(($this->entity->yaw / 180) * M_PI) * cos(($this->entity->pitch / 180) * M_PI) * $f1;
			$sZ = cos(($this->entity->yaw / 180) * M_PI) * cos(($this->entity->pitch / 180) * M_PI) * $f1;
			$sY = -sin(($this->entity->pitch / 180) * M_PI) * $f1 + 0.1;
			$f1 = 0.02;
			$f3 = $this->entity->random->nextFloat() * M_PI * 2.0;
			$f1 *= $this->entity->random->nextFloat();
			$sX += cos($f3) * $f1;
			$sY += ($this->entity->random->nextFloat() - $this->entity->random->nextFloat()) * 0.1;
			$sZ += sin($f3) * $f1;
			$this->server->api->entity->dropRawPos($this->level, $this->entity->x, $this->entity->y - 0.3 + $this->entity->height - 0.12, $this->entity->z, BlockAPI::getItem($type, $damage, $stacksz), $sX, $sY, $sZ);
			if($addexpected) $this->addExpectedSetSlotPacket(-1, $type, $damage, $stacksz);
		}
		return true;
	}

	/**
	 * @param string $message
	 * @param string $author
	 */
	public function sendChat($message, $author = ""){
		$mes = explode("\n", $message);
		foreach($mes as $m){
			if(preg_match_all('#@([@A-Za-z_]{1,})#', $m, $matches, PREG_OFFSET_CAPTURE) > 0){
				$offsetshift = 0;
				foreach($matches[1] as $selector){
					if($selector[0][0] === "@"){ //Escape!
						$m = substr_replace($m, $selector[0], $selector[1] + $offsetshift - 1, strlen($selector[0]) + 1);
						--$offsetshift;
						continue;
					}
					switch(strtolower($selector[0])){
						case "player":
						case "username":
							$m = substr_replace($m, $this->username, $selector[1] + $offsetshift - 1, strlen($selector[0]) + 1);
							$offsetshift += strlen($selector[0]) - strlen($this->username) + 1;
							break;
					}
				}
			}

			if($m !== ""){
				$pk = new MessagePacket;
				$pk->source = ($author instanceof Player) ? $author->username : $author;
				$pk->message = TextFormat::clean($m); //Colors not implemented :(
				$this->sendChatMessagePacket($pk);
			}
		}
	}
	
	
	public function isInvisibleFor(Player $player){
		return isset($this->invisibleFor[$player->CID]);
	}
	
	public function setInvisibleFor(Player $player, $b, $send=true){
		$data = [
			"target" => $this,
			"for" => $player,
			"status" => $b
		];
		
		$this->server->handle("player.invisible", $data);
		$b = $data["status"];
		if(!($this->entity instanceof Entity)) $send = false;
		if($b){
			$this->invisibleFor[$player->CID] = $b;
			if($send){
				$pk = new MovePlayerPacket();
				$pk->x = -256;
				$pk->y = 128;
				$pk->z = -256;
				$pk->eid = $this->eid;
				$pk->yaw = 0;
				$pk->pitch = 0;
				$pk->headYaw = 0;
				$player->entityQueueDataPacket($pk);
			}
		}else{
			unset($this->invisibleFor[$player->CID]);
			if($send){
				$pk = new MovePlayerPacket();
				$pk->eid = $this->entity->eid;
				$pk->x = $this->entity->x;
				$pk->y = $this->entity->y;
				$pk->z = $this->entity->z;
				$pk->yaw = $this->entity->yaw;
				$pk->pitch = $this->entity->pitch;
				$pk->bodyYaw = $this->entity->headYaw;
				$player->entityQueueDataPacket($pk);
			}
		}
	}
	
	public function makeInvisibleForAllPlayers(){
		foreach($this->server->api->player->getA as $player){
			if($player->CID != $this->CID) $this->setInvisibleFor($player, true);
		}
	}
	public function getGamemode(){
		switch($this->gamemode){
			case SURVIVAL:
				return "survival";
			case CREATIVE:
				return "creative";
			case ADVENTURE:
				return "adventure";
			case VIEW:
				return "view";
		}
	}
	
	public function checkSpawnPosition(){
		if($this->server->api->dhandle("player.checkspawnpos", ["player" => $this]) === false) return;
		$level = $this->spawnPosition->level;
		if(!isset($this->server->api->level->levels[$level->getName()])){
			ConsoleAPI::warn("Level to respawn {$this->iusername} was unloaded, changing spawnpoint to default.");
			$level = $this->server->api->level->getDefault();
			$this->spawnPosition = $level->getSpawn();
		}else{
			$x0 = floor($this->spawnPosition->x - $this->entity->width/2);
			$x1 = floor($this->spawnPosition->x + $this->entity->width/2 + 1);
			$y0 = floor($this->spawnPosition->y);
			$y1 = floor($this->spawnPosition->y + $this->entity->height + 1);
			$z0 = floor($this->spawnPosition->z - $this->entity->width/2);
			$z1 = floor($this->spawnPosition->z + $this->entity->width/2 + 1);
			
			for($x = $x0; $x < $x1; ++$x) {
				for($z = $z0; $z < $z1; ++$z) {
					for($y = $y0; $y < $y1; ++$y) {
						$bid = $this->entity->level->level->getBlockID($x, $y, $z);
						if($bid > 0 && StaticBlock::getIsSolid($bid)){
							$blockBounds = StaticBlock::$prealloc[$bid]::getCollisionBoundingBoxes($this->entity->level, $x, $y, $z, $this->entity);
							
							foreach($blockBounds as $blockBound){
								if($this->entity->boundingBox->intersectsWith($blockBound)){
									$this->sendChat("Your spawn positon is obstructed.");
									goto reset_spawn_pos;
								}
							}
						}
					}
				}
			}
			return;
			reset_spawn_pos:
			$this->spawnPosition = $this->server->api->level->getDefault()->getSpawn();
		}
	}
	
	public function setGamemode($gm){
		if($gm < 0 or $gm > 3 or $this->gamemode === $gm){
			return false;
		}

		if($this->server->api->dhandle("player.gamemode.change", ["player" => $this, "gamemode" => $gm]) === false){
			return false;
		}

		$this->save();
		$inv = &$this->inventory;
		if($gm === VIEW){
			$this->armor = [];
			$this->sendArmor();
		}
		if(($this->gamemode & 0x01) === ($gm & 0x01)){
			if(($gm & 0x01) === 0x01 && ($gm & 0x02) === 0x02){ //gm == VIEW
				$inv = [];
				foreach(BlockAPI::$creative as $item){
					$inv[] = BlockAPI::getItem(0, 0, 1);
				}
			}elseif(($gm & 0x01) === 0x01){
				$inv = [];
				foreach(BlockAPI::$creative as $item){
					$inv[] = BlockAPI::getItem($item[0], $item[1], 1);
				}
			}
			$this->gamemode = $gm;
			$this->saveInventory = ($this->gamemode & 1) == SURVIVAL;
			$this->sendChat("Your gamemode has been changed to " . $this->getGamemode() . ".\n");
		}else{
			foreach($this->inventory as $slot => $item){
				$inv[$slot] = BlockAPI::getItem(AIR, 0, 0);
			}
			$this->hotbar = $gm == CREATIVE ? BlockAPI::$creativeHotbarSlots : [0, 1, 2, 3, 4, 5, 6, 7, 8];
			$this->lastCorrect = $this->entity->copy();
			$this->blocked = true;	
			if(($this->gamemode & 1) != ($gm & 1)){
				$this->saveInventory = false;
			}
			$this->gamemode = $gm;
			$this->sendChat("Your gamemode has been changed to " . $this->getGamemode() . ", you've to do a forced reconnect.\n");
			$this->gmKickSoon = true;
			$this->server->schedule(30, [$this, "close"], "gamemode change", false, true); //Forces a kick
			
		}
		
		if($this->gamemode === SPECTATOR){
			foreach($this->server->api->player->getAll() as $player){
				if($player->CID != $this->CID) $this->setInvisibleFor($player, true);
			}
		}
		if($this->gamemode === CREATIVE){
			foreach($this->server->api->player->getAll() as $player){
				if($player->CID != $this->CID) $this->setInvisibleFor($player, false);
			}
		}
		
		$this->inventory = $inv;
		$this->sendSettings();
		$this->sendInventory();
		return true;
	}

	public function sendSettings($nametags = true){
		/*
		 bit mask | flag name
		0b00000001 allowInteract
		0b00000010 - enablePVP
		0b00000100 - enablePVE
		0b00001000 - field_3 (<?>autojump)
		0b00010000 - dayLightCycle (0 enabled 1 disabled)
		0b00100000 - field_5 (<?>nametags_visible)
		0b01000000 - unused
		0b10000000 - unused
		*/
		$flags = 0;
		if(($this->gamemode & 0x02) === 0x02){
			$flags |= 0x01; //Do not allow placing/breaking blocks, adventure mode
		}

		if($nametags !== false){
			$flags |= 0x20; //Show Nametags
		}
		
		//$flags |= 0x2; //pvp
		//$flags |= 0x4; //pve
		
		$pk = new AdventureSettingsPacket;
		$pk->flags = $flags;
		$this->dataPacket($pk);
	}

	public function measureLag(){
		if($this->connected === false){
			return false;
		}
		if($this->packetStats[1] > 2){
			$this->packetLoss = $this->packetStats[1] / max(1, $this->packetStats[0] + $this->packetStats[1]);
		}else{
			$this->packetLoss = 0;
		}
		$this->packetStats = [0, 0];
		array_shift($this->bandwidthStats);
		$this->bandwidthStats[] = $this->bandwidthRaw / max(0.00001, microtime(true) - $this->lastMeasure);
		$this->bandwidthRaw = 0;
		$this->lagStat = array_sum($this->lag) / max(1, count($this->lag));
		$this->lag = [];
		$this->sendBuffer();
		$this->lastMeasure = microtime(true);
	}

	public function getLag(){
		return $this->lagStat * 1000;
	}

	public function getPacketLoss(){
		return $this->packetLoss;
	}

	public function getBandwidth(){
		return array_sum($this->bandwidthStats) / max(1, count($this->bandwidthStats));
	}

	public function clearQueue(){
		if($this->connected === false){
			return false;
		}
		if(($cnt = count($this->received)) > PLAYER_MAX_QUEUE){
			ksort($this->received);
			foreach($this->received as $c => $t){
				unset($this->received[$c]);
				--$cnt;
				if($cnt <= PLAYER_MAX_QUEUE){
					break;
				}
			}
		}
	}

	public function handlePacketQueues(){
		if($this->server->ticks % 40 == 0){ //2s
			$this->sendPing();
		}
		
		if($this->connected === false){
			return false;
		}
		$time = microtime(true);
		
		if($this->loggedIn === false && ($time - $this->connectionStartedAt) >= 20){
			$this->close("timeout");
			return false;
		}
		
		if($time > $this->timeout){
			$this->close("timeout");
			return false;
		}

		if(($ackCnt = count($this->ackQueue)) > 0){
			rsort($this->ackQueue);
			$safeCount = (int) (($this->MTU - 1) / 4);
			$packetCnt = (int) ($ackCnt / $safeCount + 1);
			for($p = 0; $p < $packetCnt; ++$p){
				$pk = new RakNetPacket(RakNetInfo::ACK);
				$pk->packets = [];
				for($c = 0; $c < $safeCount; ++$c){
					if(($k = array_pop($this->ackQueue)) === null){
						break;
					}
					$pk->packets[] = $k;
				}
				$this->send($pk);
			}
			$this->ackQueue = [];
		}

		if($this->sendingInventoryRequired){
			
			$pk = new ContainerSetContentPacket;
			$pk->windowid = 0;
			if($this->gmKickSoon){
				$pk->slots = [];
				$hotbar = [];
			}else if($this->gamemode !== CREATIVE){
				$hotbar = [];
				foreach($this->hotbar as $slot){
					$hotbar[] = $slot <= -1 ? -1 : $slot + 9;
				}
				$pk->slots = $this->inventory;
			}else{
				$pk->slots = [];
				for($i = 0; $i < 9; ++$i){
					if(!isset($this->hotbar[$i])) [$id, $meta] = [0, 0];
					else [$id, $meta] = BlockAPI::$creative[$this->hotbar[$i]] ?? [0, 0];
					$pk->slots[$i] = BlockAPI::getItem($id, $meta, 1);
				}
				$hotbar = [9, 10, 11, 12, 13, 14, 15, 16, 17];
				
			}
			$pk->hotbar = $hotbar;
			$this->dataPacket($pk);
			
			$this->sendingInventoryRequired = false;
		}
		
		if(($receiveCnt = count($this->receiveQueue)) > 0){
			ksort($this->receiveQueue);
			foreach($this->receiveQueue as $count => $packets){
				unset($this->receiveQueue[$count]);
				foreach($packets as $p){
					if($p instanceof RakNetDataPacket and $p->hasSplit === false){
						if(isset($p->messageIndex) and $p->messageIndex !== false){
							if($p->messageIndex > $this->receiveCount){
								$this->receiveCount = $p->messageIndex;
							}elseif($p->messageIndex !== 0){
								if(isset($this->received[$p->messageIndex])){
									continue;
								}
								switch($p->pid()){
									case 0x01:
									case ProtocolInfo::PING_PACKET:
									case ProtocolInfo::PONG_PACKET:
									case ProtocolInfo::MOVE_PLAYER_PACKET:
									case ProtocolInfo::REQUEST_CHUNK_PACKET:
									case ProtocolInfo::ANIMATE_PACKET:
									case ProtocolInfo::SET_HEALTH_PACKET:
										break;
								}
							}
							$this->received[$p->messageIndex] = true;
						}
						$p->decode();
						$this->handleDataPacket($p);
					}
				}
			}
		}

		if($this->chatMessagesQueueLength > 0 && $this->server->ticks % 5 == 0){ //send 4 times/second
			$this->sendChatBuffer();
		}
		
		if($this->bufferLen > 0){
			$this->sendBuffer();
		}
		
		if($this->blockUpdateQueueLength > 0){
			$this->sendBlockUpdateQueue();
		}
		
		if($this->entityDataQueueLength > 0){
			$this->sendEntityDataQueue();
		}
		
		if($this->entityMovementQueueLength > 0){
			$this->sendEntityMovementUpdateQueue();
		}

		$limit = $time - 5; //max lag
		foreach($this->recoveryQueue as $count => $data){
			if($data->sendtime > $limit){
				continue;
			}
			unset($this->recoveryQueue[$count]);
			$this->resendQueue[$count] = $data;
		}
		
		foreach($this->packetAlwaysRecoverQueue as $cnt => $data){
			$maxDelay = (isset($this->chunkCount[$cnt])) ? 1 : 5;
			if($time - $data->sendtime >= $maxDelay){
				$this->resendQueue[$cnt] = $data;
			}
		}

		if(($resendCnt = count($this->resendQueue)) > 0){
			$limit = 25;
			foreach($this->resendQueue as $count => $data){
				if(!--$limit) break;
				unset($this->resendQueue[$count]);
				$this->packetStats[1]++;
				$this->lag[] = $time - $data->sendtime;
				$data->sendtime = $time;
				$this->send($data);
			}
		}
	}
	
	public function sendChatBuffer(){
		if($this->chatMessagesQueueLength > 0 && $this->chatMessagesQueue instanceof RakNetPacket){
			$this->chatMessagesQueue->seqNumber = $this->counter[0]++;
			$this->packetAlwaysRecoverQueue[$this->chatMessagesQueue->seqNumber] = $this->chatMessagesQueue;
			$this->chatMessagesQueue->sendtime = microtime(true);
			$this->send($this->chatMessagesQueue);
		}
		
		$this->chatMessagesQueueLength = 0;
		$this->chatMessagesQueue = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
		$this->chatMessagesQueue->data = [];
	}
	
	public function sendEntityMovementUpdateQueue(){
		if($this->entityMovementQueueLength > 0 && $this->entityMovementQueue instanceof RakNetPacket){
			$this->entityMovementQueue->seqNumber = $this->counter[0]++;
			$this->entityMovementQueue->sendtime = microtime(true);
			$this->send($this->entityMovementQueue);
		}
		$this->entityMovementQueueLength = 0;
		$this->entityMovementQueue = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
		$this->entityMovementQueue->data = [];
	}
	
	public function sendEntityDataQueue(){
		if($this->entityDataQueueLength > 0 && $this->entityDataQueue instanceof RakNetPacket){
			$this->entityDataQueue->seqNumber = $this->counter[0]++;
			$this->packetAlwaysRecoverQueue[$this->entityDataQueue->seqNumber] = $this->entityDataQueue;
			$this->entityDataQueue->sendtime = microtime(true);
			$this->send($this->entityDataQueue);
		}
		$this->entityDataQueueLength = 0;
		$this->entityDataQueue = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
		$this->entityDataQueue->data = [];
	}
	
	public function sendBlockUpdateQueue(){
		if($this->blockUpdateQueueLength > 0 && $this->blockUpdateQueue instanceof RakNetPacket){
			$this->blockUpdateQueue->seqNumber = $this->counter[0]++;
			$this->packetAlwaysRecoverQueue[$this->blockUpdateQueue->seqNumber] = $this->blockUpdateQueue;
			$this->blockUpdateQueue->sendtime = microtime(true);
			$this->send($this->blockUpdateQueue);
		}
		$this->blockUpdateQueueLength = 0;
		$this->blockUpdateQueue = new RakNetPacket(RakNetInfo::DATA_PACKET_0);
		$this->blockUpdateQueue->data = [];
	}
	
	/**
	 * Sends packet in block order channel. Packets are always delivered and ordered.
	 * Used for sending tileentity data, chunk data, updateblock packets.
	 * @param RakNetPacket $pk
	 */
	public function blockQueueDataPacket(RakNetDataPacket $pk){
		if($this->connected === false) return false;
		if(EventHandler::callEvent(new DataPacketSendEvent($this, $pk)) === BaseEvent::DENY) return;
		if(!$this->convertToLocalEIDPacket($pk)) return false;
		
		$pk->encode();
		
		$len = 1 + strlen($pk->buffer);
		$MTU = $this->MTU - 24;
		if($len > $MTU) return $this->blockQueueDataPacket_big($pk);
		if(($this->blockUpdateQueueLength + $len+10) >= $MTU){
			$this->sendBlockUpdateQueue();
		}
		
		$pk->messageIndex = $this->counter[3]++;
		$pk->reliability = RakNetInfo::RELIABILITY_RELIABLE_ORDERED;
		$pk->orderChannel = Player::BLOCKUPDATE_ORDER_CHANNEL;
		$pk->orderIndex = $this->blockUpdateQueueOrderIndex++;
		@$this->blockUpdateQueue->data[] = $pk;
		$this->blockUpdateQueueLength += 10 + $len;
	}
	
	public function convertToGlobalEIDPacket(RakNetDataPacket $pk){
		if(!$pk->eidsToGlobal($this)){
			//ConsoleAPI::warn("Failed to convert eids to global in {$pk->pid()}! (Player: {$this->ip}:{$this->port}). Stacktrace: ");
			//foreach(explode("\n", (new Exception())->getTraceAsString()) as $s) ConsoleAPI::warn($s);
			return false;
		}
		return true;
	}
	public function convertToLocalEIDPacket(RakNetDataPacket $pk){
		if(!$pk->eidsToLocal($this)){
			//ConsoleAPI::debug("Failed to convert eids to local in {$pk->pid()}! (Player: {$this->ip}:{$this->port}). Stacktrace: ");
			//foreach(explode("\n", (new Exception())->getTraceAsString()) as $s) ConsoleAPI::debug($s);
			return false;
		}
		return true;
	}
	
	/**
	 * Sends a packet in entity order channel. Packets are always delivered and ordered.
	 * @param RakNetDataPacket $pk
	 * @return void|boolean|boolean|number[]
	 */
	public function entityQueueDataPacket(RakNetDataPacket $pk){
		if($this->connected === false) return false;
		if(EventHandler::callEvent(new DataPacketSendEvent($this, $pk)) === BaseEvent::DENY) return;
		if(!$this->convertToLocalEIDPacket($pk)) return false;
		$pk->encode();
		
		$len = 1 + strlen($pk->buffer);
		$MTU = $this->MTU - 24;
		if($len > $MTU) return $this->entityQueueDataPacket_big($pk);
		
		if(($this->entityDataQueueLength + $len+10) >= $MTU){
			$this->sendEntityDataQueue();
		}
		
		$pk->messageIndex = $this->counter[3]++;
		
		$pk->reliability = RakNetInfo::RELIABILITY_RELIABLE_ORDERED;
		$pk->orderChannel = Player::ENTITY_ORDER_CHANNEL;
		$pk->orderIndex = $this->entityDataQueueOrderIndex++;
		@$this->entityDataQueue->data[] = $pk;
		$this->entityDataQueueLength += 10 + $len;
	}
	
	/**
	 * Adds block update into queue. Does nothing if chunk wasnt loaded.
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $id
	 * @param int $meta
	 * @return boolean
	 */
	public function addBlockUpdateIntoQueue($x, $y, $z, $id, $meta){
		$xC = $x >> 4;
		$zC = $z >> 4;
		if(!isset($this->chunkDataSent["$xC:$zC"])){
			ConsoleAPI::debug("Ignoring block update $x $y $z $id $meta: chunk data was not sent.");
			return false; //dont send updateblockpacket as it will be buffered and updated only after all chunks will load
		}
		$packet = new UpdateBlockPacket();
		$packet->x = $x;
		$packet->y = $y;
		$packet->z = $z;
		$packet->block = $id;
		$packet->meta = $meta;
		$this->blockQueueDataPacket($packet);
	}
	
	public function addEntityMovementUpdateToQueue(Entity $e){
		$len = 0;
		$packets = 0;
		$motionSent = false;
		$moveSent = false;
		$headSent = false;
		$localeid = $this->global2localEID[$e->eid] ?? false;
		if($localeid === false){
			ConsoleAPI::warn("Attempt to convert global eid to local failed! (Global: {$e->eid}, Player: {$this->ip}:{$this->port}). Stacktrace: ");
			foreach(explode("\n", (new Exception())->getTraceAsString()) as $s) ConsoleAPI::warn($s);
			return false;
		}
		
		$svdYSpeed = $e->speedY;
		if($e->modifySpeedY){
			$e->speedY = $e->modifedSpeedY;
		}
		if($e->speedX != 0 || $e->speedY != 0 || $e->speedZ != 0 || $e->speedY != $e->lastSpeedY || $e->speedX != $e->lastSpeedX || $e->speedZ != $e->lastSpeedZ){
			if(!($e->speedY < 0 && $e->onGround) || $e->speedX != 0 || $e->speedZ != 0 || $e->speedY != $e->lastSpeedY || $e->speedX != $e->lastSpeedX || $e->speedZ != $e->lastSpeedZ){
				$motion = new SetEntityMotionPacket();
				$motion->eid = $localeid;
				$motion->speedX = $e->speedX;
				$motion->speedY = $e->speedY;
				$motion->speedZ = $e->speedZ;
				$motion->encode();
				$len += 10 + strlen($motion->buffer);
				++$packets;
				$motionSent = true;
			}
		}
		if($e->modifySpeedY){
			$e->speedY = $svdYSpeed;
			$e->modifySpeedY = false;
		}
		
		if($e->x != $e->lastX || $e->y != $e->lastY || $e->z != $e->lastZ || $e->yaw != $e->lastYaw || $e->pitch != $e->lastPitch){
			if($e->headYaw != $e->lastHeadYaw){
				$move = new MovePlayerPacket();
				$move->eid = $localeid;
				$move->x = $e->x;
				$move->y = $e->y + $e->yOffset;
				$move->z = $e->z;
				$move->yaw = $e->yaw;
				$move->pitch = $e->pitch;
				$move->bodyYaw = $e->headYaw;
				$move->encode();
			}else{
				$move = new MoveEntityPacket_PosRot();
				$move->eid = $localeid;
				$move->x = $e->x;
				$move->y = $e->y + $e->yOffset;
				$move->z = $e->z;
				$move->yaw = $e->yaw;
				$move->pitch = $e->pitch;
				$move->encode();
			}
			
			$len += strlen($move->buffer) + 10;
			++$packets;
			$moveSent = true;
		}else if($e->headYaw != $e->lastHeadYaw){
			$headyaw = new RotateHeadPacket();
			$headyaw->eid = $localeid;
			$headyaw->yaw = $e->headYaw;
			$headyaw->encode();
			$len += strlen($headyaw->buffer) + 10;
			++$packets;
			$headSent = true;
		}
		if($packets <= 0) return;
		//console("Update {$e}: $packets, mot: $motionSent, mov: $moveSent, hed: $headSent");
		$MTU = $this->MTU - 24;
		if(($this->entityMovementQueueLength + $len) >= $MTU){
			$this->sendEntityMovementUpdateQueue();
		}
		if($motionSent){
			$motion->messageIndex = 0; //force 0 cuz reliability 0
			$motion->reliability = RakNetInfo::RELIABILITY_UNRELIABLE_SEQUENCED;
			$motion->orderIndex = $this->entityDataQueueOrderIndex;
			$motion->orderChannel = self::ENTITY_ORDER_CHANNEL;
			$motion->seqIndex = $this->entityMovementQueueSeqIndex++;
			$this->entityMovementQueue->data[] = $motion;
		}
		if($moveSent){
			$move->messageIndex = 0;
			$move->reliability = RakNetInfo::RELIABILITY_UNRELIABLE_SEQUENCED;
			$move->orderIndex = $this->entityDataQueueOrderIndex;
			$move->orderChannel = self::ENTITY_ORDER_CHANNEL;
			$move->seqIndex = $this->entityMovementQueueSeqIndex++;
			$this->entityMovementQueue->data[] = $move;
		}
		if($headSent){
			$headyaw->messageIndex = 0;
			$headyaw->reliability = RakNetInfo::RELIABILITY_UNRELIABLE_SEQUENCED;
			$headyaw->orderIndex = $this->entityDataQueueOrderIndex;
			$headyaw->orderChannel = self::ENTITY_ORDER_CHANNEL;
			$headyaw->seqIndex = $this->entityMovementQueueSeqIndex++;
			$this->entityMovementQueue->data[] = $headyaw;
		}
		
		$this->entityMovementQueueLength += $len;
	}
	
	/**
	 * @param string $reason Reason for closing connection
	 * @param boolean $msg Set to false to silently disconnect player. No broadcast.
	 */
	public function close($reason = "", $msg = true){
		if($this->connected === true){
			foreach($this->evid as $ev){
				$this->server->deleteEvent($ev);
			}
			if($this->username != ""){
				$this->server->api->handle("player.quit", $this);
				$this->save();
			}
			$reason = $reason == "" ? "server stop" : $reason;
			$this->sendChat("You have been kicked. Reason: " . $reason . "\n");
			$this->sendBuffer();
			$this->directDataPacket(new DisconnectPacket);
			$this->connected = false;
			$this->level->freeAllChunks($this);
			$this->loggedIn = false;
			$this->buffer = null;
			unset($this->buffer);
			$this->recoveryQueue = [];
			$this->receiveQueue = [];
			$this->resendQueue = [];
			$this->ackQueue = [];
			$this->server->api->player->remove($this->CID);
			if($msg === true and $this->username != "" and $this->spawned !== false){
				$this->server->api->chat->broadcast($this->username . " left the game: " . $reason);
			}
			
			foreach($this->server->api->player->getAll() as $player){
				$player->setInvisibleFor($this, false);
			}
			
			$this->spawned = false;
			console("[INFO] " . FORMAT_AQUA . $this->username . FORMAT_RESET . "[/" . $this->ip . ":" . $this->port . "] logged out due to " . $reason);
			$this->windows = [];
			$this->armor = [];
			$this->inventory = [];
			$this->chunksLoaded = [];
			$this->chunksOrder = [];
			$this->chunkCount = [];
			$this->craftingItems = [];
			$this->received = [];
			unset($this->server->api->chat->lastTells[$this->iusername]);
			//$this->entity->close();
		}
	}

	public function save(){
		if($this->entity instanceof Entity){
			$this->data->set("achievements", $this->achievements);
			$this->data->set("position", [
				"level" => $this->entity->level->getName(),
				"x" => (float) $this->entity->x,
				"y" => (float) $this->entity->y,
				"z" => (float) $this->entity->z,
				"yaw" => (float) $this->entity->yaw,
				"pitch" => (float) $this->entity->pitch
			]);
			$this->data->set("spawn", [
				"level" => $this->spawnPosition->level->getName(),
				"x" => $this->spawnPosition->x,
				"y" => $this->spawnPosition->y,
				"z" => $this->spawnPosition->z
			]);
			
			if($this->saveInventory){
				$inv = [];
				foreach($this->inventory as $slot => $item){
					if($item instanceof Item){
						if($slot < (($this->gamemode & 0x01) === 0 ? PLAYER_SURVIVAL_SLOTS : PLAYER_CREATIVE_SLOTS)){
							$inv[$slot] = [$item->getID(), $item->getMetadata(), $item->count];
						}
					}
				}
				$this->data->set("inventory", $inv);
			}
			
			$this->data->set("hotbar", $this->hotbar);

			if($this->saveInventory){
				$armor = [];
				foreach($this->armor as $slot => $item){
					if($item instanceof Item){
						$armor[$slot] = [$item->getID(), $item->getMetadata()];
					}
				}
				$this->data->set("armor", $armor);
			}
			
			if($this->entity instanceof Entity){
				$this->data->set("health", $this->entity->getHealth());
			}
			$this->data->set("gamemode", $this->gamemode);
		}
	}
	
	public function sendChatMessagePacket(RakNetDataPacket $packet){
		if($this->connected === false) return false;
		if(EventHandler::callEvent(new DataPacketSendEvent($this, $packet)) === BaseEvent::DENY) return false;
		if(!$this->convertToLocalEIDPacket($packet)) return false;
		
		$packet->encode();
		$len = strlen($packet->buffer) + 1;
		$MTU = $this->MTU - 24;
		if($len > $MTU) return $this->sendChatMessagePacket_big($packet);
		
		if(($this->chatMessagesQueueLength + $len+10) >= $MTU) $this->sendChatBuffer();
		
		$packet->reliability = RakNetInfo::RELIABILITY_RELIABLE_ORDERED;
		$packet->messageIndex = $this->counter[3]++;
		$packet->orderChannel = Player::CHATMESSAGE_ORDER_CHANNEL;
		$packet->orderIndex = $this->chatMessagesOrderIndex++;
		$this->chatMessagesQueue->data[] = $packet;
		$this->chatMessagesQueueLength += 10+$len; //reliability(1)+lenbits(2)+messageindex(3)+orderChannel(1)+orderIndex(3) = 10
		return true;
	}
	
	public function entityTick(){
		
		if($this->isSleeping) ++$this->sleepingTime;
		if($this->server->difficulty == 0 && $this->entity->counter % (20 * 15) == 0){
			if($this->entity->health < 20 && $this->entity->health > 0){
				$this->entity->setHealth(min(20, $this->entity->health + 1), "regeneration");
			}
		}
		
		$cnt = 0;
		foreach($this->expectedSetSlotPackets as $s => $stuff){
			$b = current($stuff);
			if(($this->server->ticks - $b) > 10*20){ //keep slot as expected for 10 seconds, no idea how good will it work
				++$cnt;
				$this->removeExpectedSetSlot($s);
			}
		}
		if($cnt > 0) ConsoleAPI::debug("$cnt slots were removed from {$this->iusername}'s expected queue due to timeout");
	}

	public function sendPing() {
		$pk = new PingPacket;
		$pk->time = intdiv(hrtime(true), 1_000_000);
		$this->directDataPacket($pk);
	}

	public function getPing() {
		return $this->lastPing;
	}

	public function handleDataPacket(RakNetDataPacket $packet){
		if($this->connected === false){
			return;
		}
		if(!$this->convertToGlobalEIDPacket($packet)) return;

		if(EventHandler::callEvent(new DataPacketReceiveEvent($this, $packet)) === BaseEvent::DENY){
			return;
		}

		switch($packet->pid()){
			case 0x01:
				break;
			case ProtocolInfo::PONG_PACKET:
				$currentTime = intdiv(hrtime(true), 1_000_000);
				if($currentTime > $packet->ptime){
					$this->lastPing = $currentTime - $packet->ptime;
				}
				break;
			case ProtocolInfo::PING_PACKET:
				$pk = new PongPacket;
				$pk->ptime = $packet->time;
				$pk->time = intdiv(hrtime(true), 1_000_000);
				$this->directDataPacket($pk);
				break;
			case ProtocolInfo::DISCONNECT_PACKET:
				$this->close("client disconnect");
				break;
			case ProtocolInfo::CLIENT_CONNECT_PACKET:
				if($this->loggedIn === true){
					break;
				}
				$pk = new ServerHandshakePacket;
				$pk->port = $this->port;
				$pk->session = $packet->session;
				$pk->session2 = Utils::readLong("\x00\x00\x00\x00\x04\x44\x0b\xa9");
				$this->dataPacket($pk);
				break;
			case ProtocolInfo::CLIENT_HANDSHAKE_PACKET:
				if($this->loggedIn === true){
					break;
				}
				break;
			case ProtocolInfo::LOGIN_PACKET:
				if($this->loggedIn === true){
					break;
				}
				$this->username = $packet->username;
				$this->iusername = strtolower($this->username);
				$this->loginData = ["clientId" => $packet->clientId, "loginData" => $packet->loginData];
				if(count($this->server->clients) > $this->server->maxClients and !$this->server->api->ban->isOp($this->iusername)){
					$this->close("server is full!", false);
					return;
				}
				if($packet->protocol1 !== ProtocolInfo::CURRENT_PROTOCOL){
					if($packet->protocol1 < ProtocolInfo::CURRENT_PROTOCOL){
						$pk = new LoginStatusPacket;
						$pk->status = 1;
						$this->directDataPacket($pk);
					}else{
						$pk = new LoginStatusPacket;
						$pk->status = 2;
						$this->directDataPacket($pk);
					}
					$this->close("Incorrect protocol #" . $packet->protocol1, false);
					return;
				}
				if(preg_match('#[^a-zA-Z0-9_]#', $this->username) > 0 || $this->username === "" || isset(Player::$blacklistedUsernames[$this->iusername]) || strlen($this->iusername) > 16){
					$this->close("Bad username", false);
					return;
				}
				if($this->server->api->handle("player.connect", $this) === false){
					$this->close("Unknown reason", false);
					return;
				}

				if($this->server->whitelist === true and !$this->server->api->ban->inWhitelist($this->iusername)){
					$this->close("Server is white-listed", false);
					return;
				}elseif($this->server->api->ban->isBanned($this->iusername) or $this->server->api->ban->isIPBanned($this->ip)){
					$this->close("You are banned!", false);
					return;
				}
				$this->loggedIn = true;

				if(!isset($this->CID) or $this->CID == null){
					console("[DEBUG] Player " . $this->username . " does not have a CID", true, true, 2);
					$this->CID = Utils::readLong(Utils::getRandomBytes(8, false));
				}
				$u = $this->server->api->player->get($this->iusername, false);
				if($u !== false){
					$this->close("this player already in game");
					return;
				}

				$this->server->api->player->add($this->CID);
				if($this->server->api->handle("player.join", $this) === false){
					$this->close("join cancelled", false);
					return;
				}

				if(!($this->data instanceof Config)){
					$this->close("no config created", false);
					return;
				}

				$this->auth = true;
				
				$inv = [];
				if(!$this->data->exists("inventory") || ($this->gamemode & 0x01) === 0x01){
					if(($this->gamemode & 0x01) === 0x01){
						if(($this->gamemode & 0x02) === 0x02){
							foreach(BlockAPI::$creative as $item){
								$inv[] = [0, 0, 1];
							}
						}else{
							foreach(BlockAPI::$creative as $item){
								$inv[] = [$item[0], $item[1], 1];
							}
						}
					}
				}else{
					$inv = [];
					foreach($this->data->get("inventory") as $slot => $item){
						$inv[$slot] = [$item[0], $item[1], $item[2]];
					}
				}
				
				$this->achievements = $this->data->get("achievements");
				$this->data->set("caseusername", $this->username);
				$this->inventory = [];
				foreach($inv as $slot => $item){
					if(!is_array($item) or count($item) < 3){
						$item = [AIR, 0, 0];
					}
					$this->inventory[$slot] = BlockAPI::getItem($item[0], $item[1], $item[2]);
				}

				$this->armor = [];
				if(($this->gamemode & 0x01) == 0){
					foreach($this->data->get("armor") as $slot => $item){
						$this->armor[$slot] = BlockAPI::getItem($item[0], $item[1], $item[0] === 0 ? 0 : 1);
					}
				}

				$this->data->set("lastIP", $this->ip);
				$this->data->set("lastID", $this->clientID);

				$this->server->api->player->saveOffline($this->data);


				$pk = new LoginStatusPacket;
				$pk->status = 0;
				$this->dataPacket($pk);
				
				if($this->data->exists("hotbar")){
					$this->hotbar = $this->data->get("hotbar");
				}else{
					if(($this->gamemode & 0x01) === 0x01) $this->hotbar = BlockAPI::$creativeHotbarSlots;
					else $this->hotbar = [0, 1, 2, 3, 4, 5, 6, 7, 8];
				}
				
				$this->slot = $this->hotbar[0];
				
				for($i = 0; $i < count($this->hotbar); ++$i){
					if($this->hotbar[$i] > (($this->gamemode & 1) == 0 ? 36 : count(BlockAPI::$creative))) $this->hotbar[$i] = -1;
					if($this->hotbar[$i] < -1) $this->hotbar[$i] = -1;
				}
				if($this->data->exists("slot-count")){
					$this->slotCount = $this->data->get("slot-count");
				}else{
					$this->data->set("slot-count", $this->slotCount);
				}
				
				if($this->data->exists("bed-position")){
					$this->bedPosition = $this->data->get("bed-position");
				}else{
					$this->setBedPosition(null);
				}
				
				$this->entity = $this->server->api->entity->add($this->level, ENTITY_PLAYER, 0, ["player" => $this]);
				$this->eid = $this->entity->eid;
				$this->server->query("UPDATE players SET EID = " . $this->eid . " WHERE CID = " . $this->CID . ";");

				$this->addEntity($this->entity);

				$pk = new StartGamePacket;
				$pk->seed = $this->level->getSeed();
				$pk->x = $this->data->get("position")["x"];
				$pk->y = ceil($this->data->get("position")["y"])+1;
				$pk->z = $this->data->get("position")["z"];
				$pk->generator = 0;
				$pk->gamemode = $this->gamemode & 0x01;
				$pk->eid = $this->entity->eid;
				
				$this->entity->x = $pk->x;
				$this->entity->y = $pk->y-0.9;
				$this->entity->z = $pk->z;
				$this->dataPacket($pk);
				
				//should cache it for client(unless the queue was full and startgamepacket was sent without settime
				//and client receives time before startgame - TODO maybe make it ordered?)
				$pk = new SetTimePacket;
				$pk->time = $this->level->getTime();
				$pk->started = !$this->level->isTimeStopped();
				$this->dataPacket($pk);
				
				if(($level = $this->server->api->level->get($this->data->get("spawn")["level"])) !== false){
					$this->spawnPosition = new Position($this->data->get("spawn")["x"], $this->data->get("spawn")["y"], $this->data->get("spawn")["z"], $level);

					$pk = new SetSpawnPositionPacket;
					$pk->x = (int) $this->spawnPosition->x;
					$pk->y = (int) $this->spawnPosition->y;
					$pk->z = (int) $this->spawnPosition->z;
					$this->dataPacket($pk);
				}
				$this->entity->check = false;
				$this->entity->setName($this->username);
				$this->entity->data["CID"] = $this->CID;
				$this->evid[] = $this->server->event("server.chat", [$this, "eventHandler"]);
				$this->evid[] = $this->server->event("entity.motion", [$this, "eventHandler"]);
				$this->evid[] = $this->server->event("entity.animate", [$this, "eventHandler"]);
				$this->evid[] = $this->server->event("entity.event", [$this, "eventHandler"]);
				$this->evid[] = $this->server->event("entity.metadata", [$this, "eventHandler"]);
				$this->evid[] = $this->server->event("entity.link", [$this, "eventHandler"]);
				$this->evid[] = $this->server->event("player.equipment.change", [$this, "eventHandler"]);
				$this->evid[] = $this->server->event("player.armor", [$this, "eventHandler"]);
				$this->evid[] = $this->server->event("player.pickup", [$this, "eventHandler"]);
				$this->evid[] = $this->server->event("tile.container.slot", [$this, "eventHandler"]);
				$this->evid[] = $this->server->event("tile.update", [$this, "eventHandler"]);
				$this->evid[] = $this->server->event("player.invisible", [$this, "invisibilityHandler"]);
				$this->lastMeasure = microtime(true);
				$this->server->schedule(50, [$this, "measureLag"], [], true);
				
				
				console("[INFO] " . FORMAT_AQUA . $this->username . FORMAT_RESET . "[/{$this->ip}:{$this->port}] logged in with entity id {$this->eid} at ({$this->entity->level->getName()}, " . round($this->entity->x, 2) . ", " . round($this->entity->y, 2) . ", " . round($this->entity->z, 2) . ") MTU: {$this->MTU}");
				break;
			case ProtocolInfo::READY_PACKET:
				if($this->loggedIn === false){
					break;
				}
				
				switch($packet->status){
					case 1: //Spawn!!
						if($this->spawned !== false){
							break;
						}
						
						$pk = new SetTimePacket;
						$pk->time = $this->level->getTime();
						$pk->started = !$this->level->isTimeStopped();
						$this->dataPacketAlwaysRecover($pk);
						
						$pos = new Position($this->entity->x, $this->entity->y, $this->entity->z, $this->level);
						$pData = $this->data->get("position");
						$this->entity->setHealth($this->data->get("health"), "spawn", true, allowHarm: false);
						$this->spawned = true;
						$this->teleport($pos, $pData["yaw"] ?? false, $pData["pitch"] ?? false, true, true);
						$this->server->api->player->spawnAllPlayers($this);
						$this->server->api->player->spawnToAllPlayers($this);
						$this->server->api->entity->spawnAll($this);
						$this->server->api->entity->spawnToAll($this->entity);

						//$this->server->schedule(5, [$this->entity, "update"], [], true);
						//$this->server->schedule(2, [$this->entity, "updateMovement"], [], true);
						$this->sendArmor();
						$array = explode("@n", (string)$this->server->motd);
						foreach($array as $msg){
							$this->sendChat($msg."\n");
						}

						$this->sendInventory();
						$this->sendSettings();
						$this->server->schedule(50, [$this, "orderChunks"], []);
						$this->blocked = false;

						$this->server->send2Discord($this->username . " joined the game");
						$this->server->handle("player.spawn", $this);
						break;
					case 2://Chunk loaded?
						break;
				}
				break;
			case ProtocolInfo::ROTATE_HEAD_PACKET:
				if($this->spawned === false){
					break;
				}
				if(($this->entity instanceof Entity)){
					if($this->blocked === true or $this->server->api->handle("player.move", $this->entity) === false){
						if($this->lastCorrect instanceof Vector3 && !$this->entity->dead){
							$this->teleport($this->lastCorrect, $this->entity->yaw, $this->entity->pitch, false);
						}
					}else{
						$this->entity->setPosition($this->entity, $packet->yaw, $this->entity->pitch);
					}
				}
				break;
			case ProtocolInfo::MOVE_PLAYER_PACKET:
				if($this->spawned === false){
					break;
				}
				if($this->isSleeping) break;
				
				
				//prevent crash caused by nan values
				if(is_nan($packet->x) || is_nan($packet->y) || is_nan($packet->z)){
					ConsoleAPI::warn("{$this->username} Attempted to crash the server using NaN position!");
					$this->close("invalid position");
					break;
				}
				
				if(is_infinite($packet->yaw) || is_infinite($packet->pitch) || is_infinite($packet->bodyYaw)){
					ConsoleAPI::warn("{$this->username} Attempted to crash other clients using INF rotation!");
					$this->close("invalid rotation");
					break;
				}
				
				//doesnt crash anything but allows players to break their model rendering(make them/head invisible)
				if(is_nan($packet->yaw) || is_nan($packet->pitch) || is_nan($packet->bodyYaw)){
					ConsoleAPI::warn("{$this->username} Attempted to break their model using NAN rotation!");
					$this->close("invalid rotation");
					break;
				}
				
				if(($this->entity instanceof Entity) && $packet->messageIndex > $this->lastMovement){
					$this->lastMovement = $packet->messageIndex;
					//prevent all movement this far - vanilla collisions break there
					//farther distances(like inf) may cause client and server crash
					if(abs($packet->x) >= 8388608 || abs($packet->y) >= 8388608 || abs($packet->z) >= 8388608){
						$this->addEntityMovementUpdateToQueue($this->entity);
						break;
					}
					
					$newPos = new Vector3($packet->x, $packet->y, $packet->z);
					if($this->forceMovement instanceof Vector3){
						if($this->forceMovement->distance($newPos) <= 0.7){
							$this->forceMovement = false;
						}else{
							$this->teleport($this->forceMovement, $this->entity->yaw, $this->entity->pitch, false);
							break;
						}
					}
					$speed = $this->entity->getSpeedMeasure();
					if($this->blocked === true or ($this->server->api->getProperty("allow-flight") !== true and (($speed > 9 and ($this->gamemode & 0x01) === 0x00) or $speed > 20 or $this->entity->distance($newPos) > 7)) or $this->server->api->handle("player.move", $this->entity) === false){
						if($this->lastCorrect instanceof Vector3 && !$this->entity->dead){
							$this->teleport($this->lastCorrect, $this->entity->yaw, $this->entity->pitch, false);
						}
					}else{
						$this->entity->setPosition($newPos, $packet->yaw, $packet->pitch, $packet->bodyYaw);
					}
					$this->entity->updateAABB();
				}
				
				break;
			case ProtocolInfo::PLAYER_EQUIPMENT_PACKET:
				if($this->spawned === false){
					break;
				}
				$packet->eid = $this->eid;

				$data = [];
				$data["eid"] = $packet->eid;
				$data["player"] = $this;

				if($packet->slot === 0){
					$data["slot"] = -1;
					$data["item"] = BlockAPI::getItem(AIR, 0, 0);
					if($this->server->handle("player.equipment.change", $data) !== false){
						$this->slot = -1;
					}
					break;
				}else if($packet->slot > 0){
					$packet->slot -= 9;
				}


				if(($this->gamemode & 0x01) === SURVIVAL){
					$data["item"] = $this->getSlot($packet->slot);
					if(!($data["item"] instanceof Item)){
						break;
					}
				}elseif(($this->gamemode & 0x01) === CREATIVE){
					$packet->slot = false;
					foreach(BlockAPI::$creative as $i => $d){
						if($d[0] === $packet->item and $d[1] === $packet->meta){
							$packet->slot = $i;
						}
					}
					if($packet->slot !== false){
						$data["item"] = $this->getSlot($packet->slot);
					}else{
						break;
					}
				}else{
					break;//?????
				}

				$data["slot"] = $packet->slot;
				
				if($this->gamemode == VIEW && $packet->item != AIR){
					$this->sendInventory();
					break;
				}
				
				if($this->server->handle("player.equipment.change", $data) !== false){
					if(!Player::$experimentalHotbar) $this->slot = $packet->slot;
					if(($this->gamemode & 0x01) === SURVIVAL){
						$has = false;
						$slotPos = 0;
						$packetSlotPos = 0;
						for($i = 0; $i < $this->slotCount; ++$i){
							if($this->slot == $this->hotbar[$i]) $slotPos = $i;
							if($packet->slot == $this->hotbar[$i]){
								$packetSlotPos = $i;
								$has = true;
								break;
							}
						}
						
						if(Player::$experimentalHotbar && $has) {
							$this->slot = $packet->slot;
							$this->curHotbarIndex = $packetSlotPos;
						}
						if(!$has){
							if(Player::$experimentalHotbar) {
								$this->slot = $packet->slot;
								$this->hotbar[$this->curHotbarIndex] = $packet->slot;
							}else{
								$this->curHotbarIndex = 0;
								array_pop($this->hotbar);
								array_unshift($this->hotbar, $this->slot);
							}
						}
						
						if(Player::$experimentalHotbar) $this->sendInventory();
					}else{
						$this->slot = $packet->slot;
					}
				}else{
					//$this->sendInventorySlot($packet->slot);
					$this->sendInventory();
				}
				if($this->entity->inAction === true){
					$this->entity->inAction = false;
					$this->entity->inActionCounter = 0;
					$this->entity->updateMetadata();
				}
				break;
			case ProtocolInfo::REQUEST_CHUNK_PACKET:
				//console("request x:".$packet->chunkX.", z: ".$packet->chunkZ." chunk");
				//$this->useChunk($packet->chunkX, $packet->chunkZ);
				//$this->lastChunk = [$packet->chunkX, $packet->chunkZ];
				break;
			case ProtocolInfo::USE_ITEM_PACKET:
				if(!($this->entity instanceof Entity)){
					break;
				}

				$blockVector = new Vector3($packet->x, $packet->y, $packet->z);
				
				if(($this->spawned === false or $this->blocked === true) and $packet->face >= 0 and $packet->face <= 5){
					$target = $this->level->getBlock($blockVector);
					$block = $target->getSide($packet->face);
					$this->addBlockUpdateIntoQueue($target->x, $target->y, $target->z, $target->getID(), $target->getMetadata());
					$this->addBlockUpdateIntoQueue($block->x, $block->y, $block->z, $block->getID(), $block->getMetadata());
					break;
				}
				$this->craftingItems = [];
				$this->toCraft = [];
				$this->craftingType = CraftingRecipes::TYPE_INVENTORY;
				$packet->eid = $this->eid;
				$data = [];
				$data["eid"] = $packet->eid;
				$data["player"] = $this;
				$data["face"] = $packet->face;
				$data["x"] = $packet->x;
				$data["y"] = $packet->y;
				$data["z"] = $packet->z;
				$data["item"] = $packet->item;
				$data["meta"] = $packet->meta;
				$data["fx"] = $packet->fx;
				$data["fy"] = $packet->fy;
				$data["fz"] = $packet->fz;
				$data["posX"] = $packet->posX;
				$data["posY"] = $packet->posY;
				$data["posZ"] = $packet->posZ;

				//nc: check item desync
				$heldItem = $this->getHeldItem();
				$pmeta = ($packet->meta & 0xff);
				$hmeta = ($heldItem->getMetadata() & 0xff); //in 0.8.1 useitempacket uses byte for meta
				
				if($heldItem->getID() != $packet->item || $hmeta != $pmeta){
					$this->sendInventory();
					if($heldItem->getID() == $packet->item && $heldItem->getMaxDurability() != false){ //id matches, meta is used as durability -> allow to perform whatever action the player wants to do
						goto allow_use_item;
					}
					ConsoleAPI::warn("{$this->username}'s heldItem doesnt match on clientside({$packet->item} {$pmeta}) and serverside({$heldItem->getID()} {$hmeta}) when using item. Resending inventory.");
					
					if($packet->face >= 0 && $packet->face <= 5){
						$target = $this->level->getBlock($blockVector);
						$block = $target->getSide($packet->face);
						$this->addBlockUpdateIntoQueue($target->x, $target->y, $target->z, $target->getID(), $target->getMetadata());
						$this->addBlockUpdateIntoQueue($block->x, $block->y, $block->z, $block->getID(), $block->getMetadata());
					}
					break;
				}
				allow_use_item:

				if($packet->face >= 0 and $packet->face <= 5){ //Use Block, place
					if($this->entity->inAction === true){
						$this->entity->inAction = false;
						$this->entity->inActionCounter = 0;
						$this->entity->updateMetadata();
					}

					if($this->blocked === true or ($this->entity->position instanceof Vector3 and $blockVector->distance($this->entity->position) > 10)){

					}elseif($this->getSlot($this->slot)->getID() !== $packet->item or ($this->getSlot($this->slot)->isTool() === false and $this->getSlot($this->slot)->getMetadata() !== $packet->meta)){
						$this->sendInventorySlot($this->slot);
					}else{
						$this->server->api->block->playerBlockAction($this, $blockVector, $packet->face, $packet->fx, $packet->fy, $packet->fz);
						break;
					}
					$target = $this->level->getBlock($blockVector);
					$block = $target->getSide($packet->face);
					
					$this->addBlockUpdateIntoQueue($target->x, $target->y, $target->z, $target->getID(), $target->getMetadata());
					$this->addBlockUpdateIntoQueue($block->x, $block->y, $block->z, $block->getID(), $block->getMetadata());
					break;
				}elseif($packet->face === 0xff){
					
					$slotItem = $this->getHeldItem();
					if($slotItem->getID() == SNOWBALL || $slotItem->getID() == EGG){ //TODO better way
						$x = $packet->x * 0.000030518;
						$y = $packet->y * 0.000030518;
						$z = $packet->z * 0.000030518;
						
						$d = sqrt($x*$x + $y*$y + $z*$z);
						
						if($d >= 0.0001){
							$shootX = $x / $d;
							$shootY = $y / $d;
							$shootZ = $z / $d;
							
							$data = [
								"x" => $this->entity->x,
								"y" => $this->entity->y + $this->entity->getEyeHeight(),
								"z" => $this->entity->z,
								"yaw" => $this->entity->yaw,
								"pitch" => $this->entity->pitch,
								"shooter" => $this->entity->eid,
								"shootX" => $shootX,
								"shootY" => $shootY,
								"shootZ" => $shootZ
							];
							
							if($slotItem->getID() == EGG){
								$e = $this->server->api->entity->add($this->entity->level, ENTITY_OBJECT, OBJECT_EGG, $data);
							}else{
								$e = $this->server->api->entity->add($this->level, ENTITY_OBJECT, OBJECT_SNOWBALL, $data);
							}
							
							if(($this->gamemode & 0x01) == 0x0) {
								if($slotItem !== false){
									if($slotItem->count == 1) $this->inventory[$this->slot] = BlockAPI::getItem(AIR, 0, 0);
									else $slotItem->count -= 1;
								}
							}
							
							$this->server->api->entity->spawnToAll($e);
						}
						
					}else{
						if($this->server->handle("player.action", $data) !== false){
							$this->entity->inAction = true;
							$this->entity->inActionCounter = 0;
							$this->startAction = microtime(true);
							$this->entity->updateMetadata();
						}
					}
				}
				break;
			case ProtocolInfo::PLAYER_ACTION_PACKET:
				if($this->spawned === false or $this->blocked === true){
					break;
				}
				$packet->eid = $this->eid;
				$this->craftingItems = [];
				$this->toCraft = [];
				$this->craftingType = CraftingRecipes::TYPE_INVENTORY;
				
				switch($packet->action){
					case 5: //Shot arrow
						if($this->entity->inAction){
							$arrowSlot = $this->hasItem(ARROW);
							if($this->getSlot($this->slot)->getID() === BOW && (($this->gamemode & 0x01) == 0x1 || $arrowSlot !== false)){
								if($this->startAction !== false){
									$initalPower = $this->entity->inActionCounter;
									$power = $initalPower / 20;
									$power = ($power * $power + $power * 2) / 3;
									if($power >= 0.1){
										if($power > 1) $power = 1;
										$this->server->dhandle("player.shoot", [
											"player" => $this,
											"power" => &$power,
										]);
										
										$d = [
											"x" => $this->entity->x,
											"y" => $this->entity->y + 1.6,
											"z" => $this->entity->z,
											"yaw" => $this->entity->yaw,
											"pitch" => $this->entity->pitch,
											"shooter" => $this->entity->eid,
										];
										$e = $this->server->api->entity->add($this->level, ENTITY_OBJECT, OBJECT_ARROW, $d);
										$e->speedX = -sin(($e->yaw / 180) * M_PI) * cos(($e->pitch / 180) * M_PI);
										$e->speedZ = cos(($e->yaw / 180) * M_PI) * cos(($e->pitch / 180) * M_PI);
										$e->speedY = -sin(($e->pitch / 180) * M_PI);
										$e->shooterEID = $this->entity->eid;
										$e->shotByEntity = true;
										/**
										 * Max usage: 72000ticks
										 * initalPower = 72000 - (72000 - usedCtr)
										 * power = initialPower / 20'
										 * power = (power*power+power*2)/3
										 * powerMax is 1, powerMin is 0.1
										 * args: xvel, yvel, zvel, (power+power)*1.5, 1.0
										 */
										$e->critical = ($power == 1);
										$e->shoot($e->speedX, $e->speedY, $e->speedZ, ($power+$power) * 1.5, 1.0);
										$this->server->api->entity->spawnToAll($e);
										if(($this->gamemode & 0x01) == 0x0) {
											$this->getHeldItem()->hurtAndBreak(1, $this);
											$this->removeItem(ARROW, 0, 1, send: true);
										}
									}
								}
							}else{ //inv desynced, resend
								$this->sendInventory();
							}
						}
						$this->startAction = false;
						$this->entity->inAction = false;
						$this->entity->inActionCounter = 0;
						$this->entity->updateMetadata();
						break;
					case 6: //get out of the bed
						$this->stopSleep();
				}
				break;
			case ProtocolInfo::REMOVE_BLOCK_PACKET:
				$blockVector = new Vector3($packet->x, $packet->y, $packet->z);
				if($this->spawned === false or $this->blocked === true or $this->entity->distance($blockVector) > 8){
					[$id, $meta] = $this->level->level->getBlock($blockVector->x, $blockVector->y, $blockVector->z);
					$this->addBlockUpdateIntoQueue($blockVector->x, $blockVector->y, $blockVector->z, $id, $meta);
					break;
				}
				$this->craftingItems = [];
				$this->toCraft = [];
				$this->craftingType = CraftingRecipes::TYPE_INVENTORY;
				$this->server->api->block->playerBlockBreak($this, $blockVector);
				break;
			case ProtocolInfo::PLAYER_ARMOR_EQUIPMENT_PACKET:
				if($this->spawned === false or $this->blocked === true){
					break;
				}
				$this->craftingItems = [];
				$this->toCraft = [];
				$this->craftingType = CraftingRecipes::TYPE_INVENTORY;
				$packet->eid = $this->eid;
				for($i = 0; $i < 4; ++$i){
					$s = $packet->slots[$i];
					if($s === 0 or $s === 255){
						$s = BlockAPI::getItem(AIR, 0, 0);
					}else{
						$s = BlockAPI::getItem($s + 256, 0, 1);
					}
					$slot = $this->armor[$i];
					
					if($slot->getID() !== AIR && $s->getID() === AIR){
						$this->addItem($slot->getID(), $slot->getMetadata(), $slot->count, false);
						$this->armor[$i] = BlockAPI::getItem(AIR, 0, 0);
						$packet->slots[$i] = 255;
					}elseif($s->getID() !== AIR and $slot->getID() === AIR and ($sl = $this->hasItem($s->getID())) !== false){
						$this->armor[$i] = $this->getSlot($sl);
						$this->setSlot($sl, BlockAPI::getItem(AIR, 0, 0), false);
					}elseif($s->getID() !== AIR && $slot->getID() !== AIR and ($slot->getID() !== $s->getID() or $slot->getMetadata() !== $s->getMetadata()) and ($sl = $this->hasItem($s->getID())) !== false){
						$item = $this->armor[$i];
						$this->armor[$i] = $this->getSlot($sl);
						$this->setSlot($sl, $item, false);
					}else{
						$packet->slots[$i] = 255;
					}
				}
				$this->sendArmor();
				if($this->entity->inAction === true){
					$this->entity->inAction = false;
					$this->entity->inActionCounter = 0;
					$this->entity->updateMetadata();
				}
				break;
			case ProtocolInfo::INTERACT_PACKET:
				if($this->spawned === false){
					break;
				}
				$packet->eid = $this->eid;
				$data = [];
				$data["target"] = $packet->target;
				$data["eid"] = $packet->eid;
				$data["action"] = $packet->action;
				$this->craftingItems = [];
				$this->toCraft = [];
				$this->craftingType = CraftingRecipes::TYPE_INVENTORY;
				$target = $this->server->api->entity->get($packet->target);
				if($target instanceof Entity and $this->entity instanceof Entity and $this->gamemode !== VIEW and $this->blocked === false and ($target instanceof Entity) and $this->entity->distance($target) <= 8){
					$data["targetentity"] = $packet->target;
					$data["entity"] = $this->entity;
					$data["player"] = $this;
					if($this->server->handle("player.interact", $data) !== false){
						$target->interactWith($this->entity, $packet->action);
					}
				}

				break;
			case ProtocolInfo::ANIMATE_PACKET:
				if($this->spawned === false){
					break;
				}
				$packet->eid = $this->eid;
				$this->server->api->dhandle("entity.animate", ["eid" => $packet->eid, "entity" => $this->entity, "action" => $packet->action]);
				break;
			case ProtocolInfo::RESPAWN_PACKET:
				if($this->spawned === false){
					break;
				}
				if(@$this->entity->dead === false){
					break;
				}
				$this->craftingItems = [];
				$this->toCraft = [];
				$this->craftingType = CraftingRecipes::TYPE_INVENTORY;
				
				$this->checkSpawnPosition();
				$tpTarget = $this->level->getSpawn();
				if($this->bedPosition != null){
					[$levelname, $x, $y, $z] = $this->bedPosition;
					$level = $this->server->api->level->get($levelname);
					if($level === false || $level->level->getBlockID($x, $y, $z) != BED_BLOCK){
						remove_bed:
						$this->sendChat("The bed is missing or cannot be accessed.");
						$this->setBedPosition(null);
					}else{
						$spawnPoint = BedBlock::findStandUpPosition($level, $x, $y, $z);
						if($spawnPoint == null) goto remove_bed;
						$spawnPoint->x += 0.5;
						$spawnPoint->z += 0.5;
						$tpTarget = $spawnPoint;
					}
				}
				
				$this->teleport($tpTarget, false, false, true, false);
				$pk = new MovePlayerPacket();
				$pk->eid = $this->entity->eid;
				$pk->x = $this->entity->x;
				$pk->y = $this->entity->y;
				$pk->z = $this->entity->z;
				$pk->yaw = $this->entity->yaw;
				$pk->pitch = $this->entity->pitch;
				$pk->bodyYaw = $this->entity->headYaw;
				foreach($this->entity->level->players as $player){
					if($player->entity->eid != $this->entity->eid && !$this->isInvisibleFor($player)){
						$player->entityQueueDataPacket(clone $pk);
					}
				}
				
				if($this->entity instanceof Entity){
					$this->entity->fire = 0;
					$this->entity->air = $this->entity->maxAir;
					$this->entity->setHealth(20, "respawn", true);
					$this->entity->updateMetadata();
				}else{
					break;
				}
				$this->sendInventory();
				$this->blocked = false;
				$this->server->handle("player.respawn", $this);
				break;
			case ProtocolInfo::SET_HEALTH_PACKET: //Not used
				break;
			case ProtocolInfo::ENTITY_EVENT_PACKET:
				if($this->spawned === false or $this->blocked === true){
					break;
				}
				$this->craftingItems = [];
				$this->toCraft = [];
				$this->craftingType = CraftingRecipes::TYPE_INVENTORY;
				$packet->eid = $this->eid;
				if($this->entity->inAction === true){
					$this->entity->inAction = false;
					$this->entity->inActionCounter = 0;
					$this->entity->updateMetadata();
				}
				switch($packet->event){
					case 9: //Eating
						$slot = $this->getSlot($this->slot);
						$foodHeal = Item::getFoodHeal($slot->getID());
						if($this->entity->getHealth() < 20 && $foodHeal != 0){
							$pk = new EntityEventPacket;
							$pk->eid = $this->eid;
							$pk->event = EntityEventPacket::ENTITY_COMPLETE_USING_ITEM;
							$this->entityQueueDataPacket($pk);

							$this->entity->heal($foodHeal, "eating");
							--$slot->count;
							if($slot->count <= 0){
								$this->setSlot($this->slot, BlockAPI::getItem(AIR, 0, 0), false);
							}
							if($slot->getID() === MUSHROOM_STEW or $slot->getID() === BEETROOT_SOUP){
								$this->addItem(BOWL, 0, 1, false);
							}
						}
						break;
				}
				break;
			case ProtocolInfo::DROP_ITEM_PACKET:
				if($this->spawned === false or $this->blocked === true){
					break;
				}
				
				if($this->gamemode & 0x01 == 1){
					ConsoleAPI::warn("{$this->iusername} tried dropping item while in creative!");
					return;
				}
				
				$packet->eid = $this->eid;
				$prevItem = $packet->item;
				$newItem = $this->getHeldItem();
				
				if(($n = $this->isExpectedSetSlot(-1, $prevItem))){
					ConsoleAPI::debug("{$this->username}: Expected drop slot dropped");
					$this->removeExpectedSetSlot($n);
					return;
				}
				
				if($newItem->getID() != $prevItem->getID() || $newItem->getMetadata() != $prevItem->getMetadata()){
					
					if(count($this->inventory) >= 36){
						foreach($this->inventory as $slot => $item){
							if($item->getID() == 0) goto inv_desync_on_drop;
						}
						
						$this->addCraftingResult(-1, $prevItem->getID(), $prevItem->getMetadata(), $prevItem->count);
						break;
					}
					inv_desync_on_drop:
					
					ConsoleAPI::debug("{$this->username} tried dropping item from non-held slot or inventory desynchronized.");
					$this->sendInventory();
					break;
				}
				
				if($newItem->count < $prevItem->count){
					ConsoleAPI::warn("{$this->username} tried dropping too many items(serverside stack has {$newItem->count}, tried dropping {$prevItem->count}.");
					$this->sendInventory();
					return;
				}
				if(Player::$allowDroppingSingleItems){
					$packet->item = BlockAPI::getItem($newItem->getID(), $newItem->getMetadata(), $prevItem->count);
				}else{
					$packet->item = $newItem;
				}
				
				$this->craftingItems = [];
				$this->toCraft = [];
				$this->craftingType = CraftingRecipes::TYPE_INVENTORY;
				$data["eid"] = $packet->eid;
				$data["unknown"] = $packet->randomly;
				$data["randomly"] = $packet->randomly;
				$data["item"] = $packet->item;
				$data["player"] = $this;
				if($this->blocked === false and $this->server->handle("player.drop", $data) !== false){
					$f1 = 0.3;
					$sX = -sin(($this->entity->yaw / 180) * M_PI) * cos(($this->entity->pitch / 180) * M_PI) * $f1;
					$sZ = cos(($this->entity->yaw / 180) * M_PI) * cos(($this->entity->pitch / 180) * M_PI) * $f1;
					$sY = -sin(($this->entity->pitch / 180) * M_PI) * $f1 + 0.1;
					$f1 = 0.02;
					$f3 = $this->entity->random->nextFloat() * M_PI * 2.0;
					$f1 *= $this->entity->random->nextFloat();
					$sX += cos($f3) * $f1;
					$sY += ($this->entity->random->nextFloat() - $this->entity->random->nextFloat()) * 0.1;
					$sZ += sin($f3) * $f1;
					$this->server->api->entity->dropRawPos($this->level, $this->entity->x, $this->entity->y - 0.3 + $this->entity->height - 0.12, $this->entity->z, $packet->item, $sX, $sY, $sZ);
					$newItem->count -= $packet->item->count; 
					if($newItem->count <= 0){
						$this->setSlot($this->slot, BlockAPI::getItem(0, 0, 0), false);
					}
				}else{
					$this->sendInventory(); //send if blocked
				}
				if($this->entity->inAction === true){
					$this->entity->inAction = false;
					$this->entity->inActionCounter = 0;
					$this->entity->updateMetadata();
				}
				break;
			case ProtocolInfo::MESSAGE_PACKET:
				if($this->spawned === false){
					break;
				}
				$this->craftingItems = [];
				$this->toCraft = [];
				$this->craftingType = CraftingRecipes::TYPE_INVENTORY;
				if(trim($packet->message) != "" and strlen($packet->message) <= 255){
					$message = $packet->message;
					if($message[0] === "/"){ //Command
						if($this instanceof Player){
							console("[DEBUG] " . FORMAT_AQUA . $this->username . FORMAT_RESET . " issued server command: " . $message);
						}else{
							console("[DEBUG] " . FORMAT_YELLOW . "*" . $this . FORMAT_RESET . " issued server command: " . $message);
						}
						$this->server->api->console->run(substr($message, 1), $this);
					}else{
						$data = ["player" => $this, "message" => $message];
						if(Player::$disableEmojisInChat && Utils::hasEmoji($data["message"])){
							$this->sendChat("Your message contains illegal characters");
							break;
						}
						
						//if($message == "pf"){
						//	Living::$pathfind = !Living::$pathfind;
						//}
						
						if($this->server->api->handle("player.chat", $data) !== false){
							$this->server->send2Discord("<" . $this->username . "> " . $message);
							if(isset($data["message"])){
								$this->server->api->chat->send($this, $data["message"]);
							}else{
								$this->server->api->chat->send($this, $message);
							}
						}
					}
				}
				break;
			case ProtocolInfo::CONTAINER_CLOSE_PACKET:
				if($this->spawned === false){
					break;
				}
				$this->craftingItems = [];
				$this->toCraft = [];
				$this->craftingType = CraftingRecipes::TYPE_INVENTORY;
				if(isset($this->windows[$packet->windowid])){
					if(is_array($this->windows[$packet->windowid])){
						foreach($this->windows[$packet->windowid] as $ob){
							$pk = new TileEventPacket;
							$pk->x = $ob->x;
							$pk->y = $ob->y;
							$pk->z = $ob->z;
							$pk->case1 = 1;
							$pk->case2 = 0;
							$this->server->api->player->broadcastPacket($this->level->players, $pk);
						}
					}elseif($this->windows[$packet->windowid]->class === TILE_CHEST){
						$pk = new TileEventPacket;
						$pk->x = $this->windows[$packet->windowid]->x;
						$pk->y = $this->windows[$packet->windowid]->y;
						$pk->z = $this->windows[$packet->windowid]->z;
						$pk->case1 = 1;
						$pk->case2 = 0;
						$this->server->api->player->broadcastPacket($this->level->players, $pk);
					}
				}
				unset($this->windows[$packet->windowid]);

				$pk = new ContainerClosePacket;
				$pk->windowid = $packet->windowid;
				$this->dataPacket($pk);
				break;
			case ProtocolInfo::CONTAINER_SET_SLOT_PACKET:
				if($this->spawned === false or $this->blocked === true){
					break;
				}
				if($packet->windowid === 0){ //crafting
					
					if(($this->gamemode & 1) == CREATIVE){ //sets hotbar item - works only in creative(-based) mode
						if($this->gamemode == VIEW) break; //TODO maybe send inventory?
						if($packet->slot < 0 || $packet->slot > 8) break;
						$it = $packet->item;
						$yes = BlockAPI::hasCreativeItem($it->getID(), $it->getMetadata());
						if($yes === false){ //TODO maybe send inventory
							break;
						}
						$this->hotbar[$packet->slot] = $yes;
						return;
					}
					
					$slot = $this->getSlot($packet->slot);
					$citem = $packet->item;
					
					if(($n = $this->isExpectedSetSlot($packet->slot, $packet->item)) !== false){
						//ConsoleAPI::debug("Expected setslot at $n found {$packet->item}");
						$this->removeExpectedSetSlot($n);
						break;
					}else{
						//ConsoleAPI::info("unexpected setslot, assuming caused by crafting {$packet->slot} {$packet->item}");
					}
					
					if($slot->getID() == $citem->getID() && $slot->getMetadata() == $citem->getMetadata()){
						if($citem->count > $slot->count){ //item added, result
							$this->addCraftingResult($packet->slot, $slot->getID(), $slot->getMetadata(), $citem->count - $slot->count);
						}else if($citem->count < $slot->count){ //item removed, ingridient
							$this->addCraftingIngridient($packet->slot, $slot->getID(), $slot->getMetadata(), $slot->count - $citem->count);
						}else{
							//item synchronized
						}
					}else if($citem->getID() == 0 && $slot->getID() > 0){ //client sent air, server has not air, ingridient
						$this->addCraftingIngridient($packet->slot, $slot->getID(), $slot->getMetadata(), $slot->count);
					}else if($slot->getID() == 0 && $citem->getID() > 0){ //client sent non-air, server has air, result
						$this->addCraftingResult($packet->slot, $citem->getID(), $citem->getMetadata(), $citem->count);
					}else{ //client sent result, server has ingridients 
						$this->addCraftingIngridient($packet->slot, $slot->getID(), $slot->getMetadata(), $slot->count);
						$this->addCraftingResult($packet->slot, $citem->getID(), $citem->getMetadata(), $citem->count);
					}
				}else{
					$this->toCraft = [];
					$this->craftingItems = [];
					$this->craftingType = CraftingRecipes::TYPE_INVENTORY;
				}
				
				if(!isset($this->windows[$packet->windowid])){
					break;
				}

				if(is_array($this->windows[$packet->windowid])){
					$tiles = $this->windows[$packet->windowid];
					if($packet->slot >= 0 and $packet->slot < CHEST_SLOTS){
						$tile = $tiles[0];
						$slotn = $packet->slot;
						$offset = 0;
					}elseif($packet->slot >= CHEST_SLOTS and $packet->slot <= (CHEST_SLOTS << 1)){
						$tile = $tiles[1];
						$slotn = $packet->slot - CHEST_SLOTS;
						$offset = CHEST_SLOTS;
					}else{
						break;
					}

					$item = BlockAPI::getItem($packet->item->getID(), $packet->item->getMetadata(), $packet->item->count);

					$slot = $tile->getSlot($slotn);
					if($this->server->api->dhandle("player.container.slot", [
						"tile" => $tile,
						"slot" => $packet->slot,
						"offset" => $offset,
						"slotdata" => $slot,
						"itemdata" => $item,
						"player" => $this
					]) === false){
						$pk = new ContainerSetSlotPacket;
						$pk->windowid = $packet->windowid;
						$pk->slot = $packet->slot;
						$pk->item = $slot;
						$this->blockQueueDataPacket($pk);
						break;
					}
					
					if($item->getID() !== AIR and $slot->getID() == $item->getID()){
						if($slot->count < $item->count){
							if($this->removeItem($item->getID(), $item->getMetadata(), $item->count - $slot->count, false) === false){
								break;
							}
						}elseif($slot->count > $item->count){
							$this->addItem($item->getID(), $item->getMetadata(), $slot->count - $item->count, false);
						}
					}else{
						if($this->removeItem($item->getID(), $item->getMetadata(), $item->count, false) === false){
							break;
						}
						$this->addItem($slot->getID(), $slot->getMetadata(), $slot->count, false);
					}
					$tile->setSlot($slotn, $item, true, $offset);
				}else{
					$tile = $this->windows[$packet->windowid];
					if(($tile->class !== TILE_CHEST and $tile->class !== TILE_FURNACE) or $packet->slot < 0 or ($tile->class === TILE_CHEST and $packet->slot >= CHEST_SLOTS) or ($tile->class === TILE_FURNACE and $packet->slot >= FURNACE_SLOTS)){
						break;
					}
					$item = BlockAPI::getItem($packet->item->getID(), $packet->item->getMetadata(), $packet->item->count);

					$slot = $tile->getSlot($packet->slot);
					if($this->server->api->dhandle("player.container.slot", [
						"tile" => $tile,
						"slot" => $packet->slot,
						"slotdata" => $slot,
						"itemdata" => $item,
						"player" => $this,
					]) === false){
						$pk = new ContainerSetSlotPacket;
						$pk->windowid = $packet->windowid;
						$pk->slot = $packet->slot;
						$pk->item = $slot;
						$this->blockQueueDataPacket($pk);
						break;
					}

					if($tile->class === TILE_FURNACE and $packet->slot == 2){
						switch($slot->getID()){
							case IRON_INGOT:
								AchievementAPI::grantAchievement($this, "acquireIron");
								break;
						}
					}

					if($item->getID() !== AIR and $slot->getID() == $item->getID()){
						if($slot->count < $item->count){
							if($this->removeItem($item->getID(), $item->getMetadata(), $item->count - $slot->count, false) === false){
								break;
							}
						}elseif($slot->count > $item->count){
							$this->addItem($item->getID(), $item->getMetadata(), $slot->count - $item->count, false);
						}
					}else{
						if($this->removeItem($item->getID(), $item->getMetadata(), $item->count, false) === false){
							break;
						}
						$this->addItem($slot->getID(), $slot->getMetadata(), $slot->count, false);
					}

					$tile->setSlot($packet->slot, $item);
				}
				break;
			case ProtocolInfo::SEND_INVENTORY_PACKET:
				if($this->spawned === false){
					break;
				}
				break;
			case ProtocolInfo::ENTITY_DATA_PACKET:
				if($this->spawned === false or $this->blocked === true){
					break;
				}
				$this->craftingItems = [];
				$this->toCraft = [];
				$this->craftingType = CraftingRecipes::TYPE_INVENTORY;
				$t = $this->server->api->tile->get(new Position($packet->x, $packet->y, $packet->z, $this->level));
				if(($t instanceof Tile) and $t->class === TILE_SIGN){
					if($t->data["creator"] !== $this->username){
						$t->spawn($this);
					}else{
						$nbt = new NBT();
						$nbt->load($packet->namedtag);
						$d = array_shift($nbt->tree);
						if($d["id"] !== TILE_SIGN){
							$t->spawn($this);
						}else{
							
							$t->setText($d["Text1"], $d["Text2"], $d["Text3"], $d["Text4"]);
						}
					}
				}
				break;
			case ProtocolInfo::SET_ENTITY_LINK_PACKET:
				if($this->entity->riding != 0){
					$this->entity->setRiding(null);
				}
				break;
			case ProtocolInfo::PLAYER_INPUT_PACKET:
				$this->isJumping = $packet->isJumping;
				$this->isSneaking = $packet->isSneaking;
				$this->entity->moveForward = $packet->moveForward;
				$this->entity->moveStrafing = $packet->moveStrafe;
				
				if($this->entity->riding != 0){
					$e = $this->entity->level->entityList[$this->entity->riding] ?? false;
					if($e === false) {
						ConsoleAPI::warn("Player is riding on entity that doesnt exist in world! ({$this->iusername}, {$this->entity->riding})");
						$this->entity->setRiding(null);
						break;
					}
				}
				
				break;
			default:
				console("[DEBUG] Unhandled 0x" . dechex($packet->pid()) . " data packet for " . $this->username . " (" . $this->clientID . "): " . print_r($packet, true), true, true, 2);
				break;
		}
	}
	
	/**
	 * Get an Item which is currently held by player
	 * @return Item
	 */
	
	public function getHeldItem(){
		return $this->getSlot($this->slot);
	}
	
	public function stopSleep(){
		$this->isSleeping = false;
		$this->sleepingTime = 0;
		if($this->entity instanceof Entity){
			$this->entity->updateMetadata();
		}
	}

	public function checkCraftAchievements($id){
		switch($id){
			case WORKBENCH:
				AchievementAPI::grantAchievement($this, "buildWorkBench");
				break;
			case WOODEN_PICKAXE:
				AchievementAPI::grantAchievement($this, "buildPickaxe");
				break;
			case FURNACE:
				AchievementAPI::grantAchievement($this, "buildFurnace");
				break;
			case WOODEN_HOE:
				AchievementAPI::grantAchievement($this, "buildHoe");
				break;
			case BREAD:
				AchievementAPI::grantAchievement($this, "makeBread");
				break;
			case CAKE:
				AchievementAPI::grantAchievement($this, "bakeCake");
				break;
			case STONE_PICKAXE:
			case GOLDEN_PICKAXE:
			case IRON_PICKAXE:
			case DIAMOND_PICKAXE:
				AchievementAPI::grantAchievement($this, "buildBetterPickaxe");
				break;
			case WOODEN_SWORD:
				AchievementAPI::grantAchievement($this, "buildSword");
				break;
			case DIAMOND:
				AchievementAPI::grantAchievement($this, "diamond");
				break;
		}
	}
	
	public $craftingType = 0;
	public function tryCraft(){
		if(count($this->toCraft) <= 0 || count($this->craftingItems) <= 0) return;
		
		$results = [];
		foreach($this->toCraft as $i => $slotz){
			$results[$i] = [$i >> 16, $i & 0xffff, array_sum($slotz)];
		}
		$ingridients = [];
		foreach($this->craftingItems as $i => $slotz){
			$ingridients[$i] = [$i >> 16, $i & 0xffff, array_sum($slotz)];
		}

		
		$cc = CraftingRecipes::canCraft($results, $ingridients, $this->craftingType);
		if(!is_array($cc)){
			if(!$cc){
				$this->toCraft = [];
				$this->craftingItems = [];
				return false;
			}
			return;
		}
		
		if($this->server->api->dhandle("player.craft", ["player" => $this, "ingridients" => $this->craftingItems, "results" => $this->toCraft, "type" => $this->craftingType]) === false){
			$this->toCraft = [];
			$this->craftingItems = [];
			return false;
		}
		
		//ConsoleAPI::debug("crafted successfully");
		foreach($this->craftingItems as $i => $slotz){
			$id = $i >> 16;
			$meta = $i & 0xffff;
			foreach($slotz as $slot => $count){
				$slt = $this->getSlot($slot);
				if($slt->getID() == $id && $slt->getMetadata() == $meta){
					$slt->count -= $count;
					if($slt->count <= 0) $this->setSlot($slot, BlockAPI::getItem(0, 0, 0), false, addexpected: false);
				}else{
					ConsoleAPI::warn("{$slt->getID()} != $id && {$slt->getMetadata()} != $meta!!!");
				}
			}
		}
		
		foreach($this->toCraft as $i => $slotz){
			$id = $i >> 16;
			$meta = $i & 0xffff;
			$this->checkCraftAchievements($id);
			foreach($slotz as $slot => $count){
				if($slot < 0){ //drop item
					$item = BlockAPI::getItem($id, $meta, $count);
					$f1 = 0.3;
					$sX = -sin(($this->entity->yaw / 180) * M_PI) * cos(($this->entity->pitch / 180) * M_PI) * $f1;
					$sZ = cos(($this->entity->yaw / 180) * M_PI) * cos(($this->entity->pitch / 180) * M_PI) * $f1;
					$sY = -sin(($this->entity->pitch / 180) * M_PI) * $f1 + 0.1;
					$f1 = 0.02;
					$f3 = $this->entity->random->nextFloat() * M_PI * 2.0;
					$f1 *= $this->entity->random->nextFloat();
					$sX += cos($f3) * $f1;
					$sY += ($this->entity->random->nextFloat() - $this->entity->random->nextFloat()) * 0.1;
					$sZ += sin($f3) * $f1;
					$this->server->api->entity->dropRawPos($this->level, $this->entity->x, $this->entity->y - 0.3 + $this->entity->height - 0.12, $this->entity->z, $item, $sX, $sY, $sZ);
				}else{
					$slt = $this->getSlot($slot);
					if($slt->getID() == $id && $slt->getMetadata() == $meta){
						$slt->count += $count;
					}else if($slt->getID() == 0){
						$this->setSlot($slot, BlockAPI::getItem($id, $meta, $count), false, addexpected: false);
					}else{
						ConsoleAPI::warn("{$slt->getID()} != $id || 0 !!!");
					}
				}
			}
		}
		
		$this->toCraft = [];
		$this->craftingItems = [];
	}
	
	public function addCraftingResult($slot, $id, $meta, $count){
		$id &= 0xffff;
		$meta &= 0xffff;
		
		$index = ($id << 16) | $meta;
		
		if(!isset($this->toCraft[$index])) $this->toCraft[$index] = [];
		if(!isset($this->toCraft[$index][$slot])) $this->toCraft[$index][$slot] = 0;
		$this->toCraft[$index][$slot] += $count;
		
		//console("Result: $id, $meta, $count into $slot");
		if($this->tryCraft() === false){
			$this->sendInventory();
		}
	}
	
	public function addCraftingIngridient($slot, $id, $meta, $count){
		$id &= 0xffff;
		$meta &= 0xffff;
		
		$index = ($id << 16) | $meta;
		
		if(!isset($this->craftingItems[$index])) $this->craftingItems[$index] = [];
		if(!isset($this->craftingItems[$index][$slot])) $this->craftingItems[$index][$slot] = 0;
		$this->craftingItems[$index][$slot] += $count;
		//console("Ingridient: $id, $meta, $count into $slot");
		if($this->tryCraft() === false){
			$this->sendInventory();
		}
	}
	
	public function hasItem($type, $damage = false){
		foreach($this->inventory as $s => $item){
			if($item->getID() === $type and ($item->getMetadata() === $damage or $damage === false) and $item->count > 0){
				return $s;
			}
		}
		return false;
	}
	
	/**
	 * Removes a single item from whatever player is holding.
	 * @param boolean $send Force send inventory to player.
	 */
	public function consumeSingleItem($send = false){
		$it = $this->getHeldItem();
		if(($this->gamemode & 0x01) === 0x00) --$it->count;
		
		if($it->count <= 0){
			$this->setSlot($this->slot, BlockAPI::getItem(0, 0, 0), false);
		}
		
		if($send) $this->sendInventory();
	}
	
	public function removeItem($type, $damage, $count, $send = true, $addexpected = true){
		while($count > 0){
			$remove = 0;
			foreach($this->inventory as $s => $item){
				if($item->getID() === $type and $item->getMetadata() === $damage){
					$remove = min($count, $item->count);
					
					if($remove < $item->count){
						$item->count -= $remove;
						$exid = $item->getID();
						$exmeta = $item->getMetadata();
						$excnt = $item->count;
					}else{
						$this->inventory[$s] = BlockAPI::getItem(AIR, 0, 0);
						$exid = $exmeta = $excnt = 0;
					}
					if($send === true) $this->sendInventorySlot($s);
					
					if($addexpected) $this->addExpectedSetSlotPacket($s, $exid, $exmeta, $excnt);
					break;
				}
			}
			if($remove <= 0){
				return false;
			}
			$count -= $remove;
		}
		return true;
	}

	public function handlePacket(RakNetPacket $packet){
		if($this->connected === true){
			$time = microtime(true);
			$this->timeout = $time + 20;
			switch($packet->pid()){
				case RakNetInfo::NACK:
					foreach($packet->packets as $count){
						if(isset($this->packetAlwaysRecoverQueue[$count])){
							$this->resendQueue[$count] = $this->packetAlwaysRecoverQueue[$count];
						}else if(isset($this->recoveryQueue[$count])){
							$this->resendQueue[$count] =& $this->recoveryQueue[$count];
							$this->lag[] = $time - $this->recoveryQueue[$count]->sendtime;
							unset($this->recoveryQueue[$count]);
						}else{ //lost and wont be recovered
							++$this->packetStats[1];
						}
					}
					break;

				case RakNetInfo::ACK:
					//if(mt_rand(0, 99) > 50) break;
					foreach($packet->packets as $count){
						if(isset($this->packetAlwaysRecoverQueue[$count])){
							$this->lag[] = $time - $this->packetAlwaysRecoverQueue[$count]->sendtime;
							
							unset($this->packetAlwaysRecoverQueue[$count]);
							unset($this->chunkCount[$count]);
						}else if(isset($this->recoveryQueue[$count])){
							$this->lag[] = $time - $this->recoveryQueue[$count]->sendtime;
							unset($this->recoveryQueue[$count]);
							unset($this->resendQueue[$count]);
						}
						++$this->packetStats[0];
					}
					break;

				case RakNetInfo::DATA_PACKET_0:
				case RakNetInfo::DATA_PACKET_1:
				case RakNetInfo::DATA_PACKET_2:
				case RakNetInfo::DATA_PACKET_3:
				case RakNetInfo::DATA_PACKET_4:
				case RakNetInfo::DATA_PACKET_5:
				case RakNetInfo::DATA_PACKET_6:
				case RakNetInfo::DATA_PACKET_7:
				case RakNetInfo::DATA_PACKET_8:
				case RakNetInfo::DATA_PACKET_9:
				case RakNetInfo::DATA_PACKET_A:
				case RakNetInfo::DATA_PACKET_B:
				case RakNetInfo::DATA_PACKET_C:
				case RakNetInfo::DATA_PACKET_D:
				case RakNetInfo::DATA_PACKET_E:
				case RakNetInfo::DATA_PACKET_F:
					$this->ackQueue[] = $packet->seqNumber;
					$this->receiveQueue[$packet->seqNumber] = [];
					foreach($packet->data as $pk){
						$this->receiveQueue[$packet->seqNumber][] = $pk;
					}
					break;
			}
		}
	}
	
	/**
	 * @return string
	 */
	function __toString(){
		if($this->username != ""){
			return $this->username;
		}
		return $this->clientID;
	}
	
	
	/**
	 * @deprecated use Player::setInvisibleFor
	 * @param Player $player
	 */
	public function makeInvisibleForOnePlayer(Player $player){
		$this->setInvisibleFor($player, true);
	}
	
	/**
	 * @deprecated craft system changed
	 * @param array $craft
	 * @param array $recipe
	 * @param $type
	 *
	 * @return bool
	 */
	public function craftItems(array $craft, array $recipe, $type){
		return false;
	}
	
	/**
	 * @deprecated armor is damaged by Entity::hurtArmor and cant be damaged by singular pieces
	 */
	public function damageArmorPart($slot, $part){}
	
	/**
	 * @deprecated 16x16x16 chunk sending was removed
	 */
	public static $smallChunks = false;
}

class PlayerNull extends Player{
	public static $INSTANCE;
	public function __construct(){

	}
}
