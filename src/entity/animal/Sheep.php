<?php

class Sheep extends Animal{
	const TYPE = MOB_SHEEP;
	function __construct(Level $level, $eid, $class, $type = 0, $data = []){
		parent::__construct($level, $eid, $class, $type, $data);
		$this->setHealth(isset($this->data["Health"]) ? $this->data["Health"] : 8, "generic");
		$this->setName("Sheep");
		$this->setSize($this->isBaby() ? 0.45 : 0.9, $this->isBaby() ? 0.675 : 1.3);
		$this->update();
	}
	
	public function getDrops(){
		return $this->isBaby() ? parent::getDrops() : [
			[WOOL, $this->data["Color"] & 0x0F, 1],
		];
	}
	
	public function isFood($id){
		return $id === WHEAT;
	}
	
	public function getMetadata(){
		$d = parent::getMetadata();
		if(!isset($this->data["Sheared"])){
			$this->data["Sheared"] = 0;
		}
		if(!isset($this->data["Color"])){ //Make a new random color if it is not in data
			$this->data["Color"] = $this->sheepColor();
		}
		$d[16]["value"] = ($this->data["Sheared"] << 4) | ($this->data["Color"] & 0x0F); //dark manipulations are happening here...
		return $d;
	}
	
	public function sheepColor(){
		$pink = 0.1558;
		$brown = 2.85;
		$lightgrayBlackGray = 14.25;
		$chance = Utils::randomFloat() * 100;
		switch($chance){
			case($chance <= $pink):
				$color = 6;
				break;
			case($chance > $pink and $chance <= ($brown+$pink)):
				$color = 12;
				break;
			case($chance > ($brown+$pink) and $chance <= ($lightgrayBlackGray+$brown+$pink)):
				$rand = mt_rand(1, 3);
				if($rand == 1) $color = 15;
				elseif($rand == 2) $color = 7;
				else $color = 8;
				break;
			default:
				$color = 0;
				break;
		}
		return $color;
	}
}