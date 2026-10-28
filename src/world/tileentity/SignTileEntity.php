<?php

class SignTileEntity extends Tile
{
	public function getSpawnPacket(){
		$nbt = new NBT();
		$nbt->write(chr(NBT::TAG_COMPOUND) . "\x00\x00");
		
		$nbt->write(chr(NBT::TAG_STRING));
		$nbt->writeTAG_String("Text1");
		$nbt->writeTAG_String(mb_substr($this->data["Text1"], 0, 15));
		
		$nbt->write(chr(NBT::TAG_STRING));
		$nbt->writeTAG_String("Text2");
		$nbt->writeTAG_String(mb_substr($this->data["Text2"], 0, 15));
		
		$nbt->write(chr(NBT::TAG_STRING));
		$nbt->writeTAG_String("Text3");
		$nbt->writeTAG_String(mb_substr($this->data["Text3"], 0, 15));
		
		$nbt->write(chr(NBT::TAG_STRING));
		$nbt->writeTAG_String("Text4");
		$nbt->writeTAG_String(mb_substr($this->data["Text4"], 0, 15));
		
		$nbt->write(chr(NBT::TAG_INT));
		$nbt->writeTAG_String("x");
		$nbt->writeTAG_Int((int) $this->x);
		
		$nbt->write(chr(NBT::TAG_INT));
		$nbt->writeTAG_String("y");
		$nbt->writeTAG_Int((int) $this->y);
		
		$nbt->write(chr(NBT::TAG_INT));
		$nbt->writeTAG_String("z");
		$nbt->writeTAG_Int((int) $this->z);
		
		$nbt->write(chr(NBT::TAG_END));
		
		$pk = new EntityDataPacket;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->namedtag = $nbt->binary;
		return $pk;
	}
}

