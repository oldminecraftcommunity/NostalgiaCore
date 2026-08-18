<?php

class ChestTileEntity extends Tile
{
	public function getSpawnPacket(){
		$nbt = new NBT();
		$nbt->write(chr(NBT::TAG_COMPOUND) . "\x00\x00");
		
		$nbt->write(chr(NBT::TAG_INT));
		$nbt->writeTAG_String("x");
		$nbt->writeTAG_Int((int) $this->x);
		
		$nbt->write(chr(NBT::TAG_INT));
		$nbt->writeTAG_String("y");
		$nbt->writeTAG_Int((int) $this->y);
		
		$nbt->write(chr(NBT::TAG_INT));
		$nbt->writeTAG_String("z");
		$nbt->writeTAG_Int((int) $this->z);
		
		if($this->isPaired()){
			$nbt->write(chr(NBT::TAG_INT));
			$nbt->writeTAG_String("pairx");
			$nbt->writeTAG_Int((int) $this->data["pairx"]);
			
			$nbt->write(chr(NBT::TAG_INT));
			$nbt->writeTAG_String("pairz");
			$nbt->writeTAG_Int((int) $this->data["pairz"]);
		}
		
		$nbt->write(chr(NBT::TAG_END));
		
		$pk = new EntityDataPacket;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->namedtag = $nbt->binary;
		return $pk;
	}
	
	public function openInventory(Player $player){
		$player->windowCnt++;
		$player->windowCnt = $id = max(2, $player->windowCnt % 99);
		if(($pair = $this->getPair()) !== false){
			if(($pair->x + ($pair->z << 13)) > ($this->x + ($this->z << 13))){ //Order them correctly
				$player->windows[$id] = [
					$pair,
					$this
				];
			}else{
				$player->windows[$id] = [
					$this,
					$pair
				];
			}
		}else{
			$player->windows[$id] = $this;
		}
		
		$pk = new ContainerOpenPacket;
		$pk->windowid = $id;
		$pk->type = WINDOW_CHEST;
		$pk->slots = is_array($player->windows[$id]) ? CHEST_SLOTS << 1 : CHEST_SLOTS;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$player->blockQueueDataPacket($pk);
		$slots = [];
		
		if(is_array($player->windows[$id])){
			$all = $this->server->api->player->getAll($this->level);
			foreach($player->windows[$id] as $ob){
				$pk = new TileEventPacket;
				$pk->x = $ob->x;
				$pk->y = $ob->y;
				$pk->z = $ob->z;
				$pk->case1 = 1;
				$pk->case2 = 2;
				foreach($this->level->players as $pl){
					$pl->blockQueueDataPacket(clone $pk);
				}
				for($s = 0; $s < CHEST_SLOTS; ++$s){
					$slot = $ob->getSlot($s);
					if($slot->getID() > AIR and $slot->count > 0){
						$slots[] = $slot;
					}else{
						$slots[] = BlockAPI::getItem(AIR, 0, 0);
					}
				}
			}
		}else{
			$pk = new TileEventPacket;
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->case1 = 1;
			$pk->case2 = 2;
			foreach($this->level->players as $pl){
				$pl->blockQueueDataPacket(clone $pk);
			}
			
			for($s = 0; $s < CHEST_SLOTS; ++$s){
				$slot = $this->getSlot($s);
				if($slot->getID() > AIR and $slot->count > 0){
					$slots[] = $slot;
				}else{
					$slots[] = BlockAPI::getItem(AIR, 0, 0);
				}
			}
		}
		
		$pk = new ContainerSetContentPacket;
		$pk->windowid = $id;
		$pk->slots = $slots;
		$player->blockQueueDataPacket($pk);
		return true;
	}
}

