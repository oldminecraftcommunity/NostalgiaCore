<?php

class RakNetPacket extends Packet{

	public $packetID;
	public $seqNumber;
	public $sendtime;
	public $incrementAllowedAck = true;
	
	public function __construct($packetID){
		$this->packetID = (int) $packetID;
	}

	public function pid(){
		return $this->packetID;
	}

	public function __destruct(){
	}
}