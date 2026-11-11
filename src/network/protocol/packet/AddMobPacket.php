<?php

class AddMobPacket extends RakNetDataPacket{
	public $eid;
	public $type;
	public $x;
	public $y;
	public $z;
	public $pitch;
	public $yaw;
	public $metadata;
	
	public function pid(){
		return ProtocolInfo::ADD_MOB_PACKET;
	}
	
	public function decode(){

	}
	
	public function encode(){
		$this->reset();
		$this->putInt($this->eid);
		$this->putInt($this->type);
		$this->putFloat($this->x);
		$this->putFloat($this->y);
		$this->putFloat($this->z);
		$this->putByte(Utils::rotDegreesToChar($this->yaw));
		$this->putByte(Utils::rotDegreesToChar($this->pitch));
		$this->put(Utils::writeMetadata($this->metadata));
	}
	
	public function eidsToLocal(Player $p){
		if(!$this->localEids){
			$this->localEids = true;
			$this->eid = $p->global2localEID[$this->eid] ?? false;
			if($this->eid === false) return false;
		}
		return true;
	}
}