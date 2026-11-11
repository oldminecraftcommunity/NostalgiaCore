<?php

class AddPlayerPacket extends RakNetDataPacket{
	public $clientID;
	public $username;
	public $eid;
	public $x;
	public $y;
	public $z;
	public $pitch;
	public $yaw;
	public $itemID;
	public $itemAuxValue;
	/**
	 *@deprecated use $itemID instead
	 */
	public $unknown1;
	/**
	 *@deprecated use $itemAuxValue instead
	 */
	public $unknown2;
	/**
	 *@var array
	 */
	public $metadata;
	
	public function pid(){
		return ProtocolInfo::ADD_PLAYER_PACKET;
	}
	
	public function decode(){

	}
	
	public function encode(){
		$this->reset();
		$this->putLong($this->clientID);
		$this->putString($this->username);
		$this->putInt($this->eid);
		$this->putFloat($this->x);
		$this->putFloat($this->y);
		$this->putFloat($this->z);
		$this->putByte(Utils::rotDegreesToChar($this->yaw));
		$this->putByte(Utils::rotDegreesToChar($this->pitch));
		$this->putShort($this->itemID);
		$this->putShort($this->itemAuxValue); //Example: bow shooting power
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