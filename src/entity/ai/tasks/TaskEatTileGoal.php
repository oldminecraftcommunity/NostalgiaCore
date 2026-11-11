<?php

class TaskEatTileGoal extends TaskBase
{
	public function onStart(EntityAI $ai)
	{
		$this->selfCounter = 40;
		foreach($ai->entity->level->players as $player){
			if($player->hasEntity($ai->entity)) $player->entityQueueDataPacket(new EntityEventPacket($ai->entity->eid, EntityEventPacket::ENTITY_ANIM_10));
		}
	}

	public function onEnd(EntityAI $ai)
	{
		
	}

	public function onUpdate(EntityAI $ai)
	{
		if($ai->isStarted("TaskPanic")){
			$this->reset();
			return false;
		}		

		if (--$this->selfCounter == 4)
		{
			$id = $ai->entity->level->level->getBlockID((int)$ai->entity->x, (int)$ai->entity->y, (int)$ai->entity->z);
			$idb = $ai->entity->level->level->getBlockID((int)$ai->entity->x, (int)$ai->entity->y - 1, (int)$ai->entity->z);
			if($id === TALL_GRASS){
				$ai->entity->level->fastSetBlockUpdate((int)$ai->entity->x, (int)$ai->entity->y, (int)$ai->entity->z, AIR, 0);
				$ai->entity->eatGrass();
				
			}elseif($idb === GRASS){
				$ai->entity->level->fastSetBlockUpdate((int)$ai->entity->x, (int)$ai->entity->y - 1, (int)$ai->entity->z, DIRT, 0);
				$ai->entity->eatGrass();
			}
		}
	}

	public function canBeExecuted(EntityAI $ai)
	{
		if($ai->isStarted("TaskRandomWalk") || $ai->isStarted("TaskEatTileGoal")) return false;
		if(mt_rand(0, ($ai->entity instanceof Ageable && $ai->entity->isBaby()) ? 50 : 1000) == 0){
			$idm = $ai->entity->level->level->getBlock((int)$ai->entity->x, (int)$ai->entity->y, (int)$ai->entity->z);
			$idb = $ai->entity->level->level->getBlockID((int)$ai->entity->x, (int)$ai->entity->y - 1, (int)$ai->entity->z);
			return ($idm[0] === TALL_GRASS && $idm[1] === 1) || $idb === GRASS;
		}
		return false;
	}

}

