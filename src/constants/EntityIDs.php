<?php

//Entities
define("ENTITY_PLAYER", 1);

define("ENTITY_MOB", 2);
	define("MOB_CHICKEN", 10);
	define("MOB_COW", 11);
	define("MOB_PIG", 12);
	define("MOB_SHEEP", 13);

	define("MOB_ZOMBIE", 32);
	define("MOB_CREEPER", 33);
	define("MOB_SKELETON", 34);
	define("MOB_SPIDER", 35);
	define("MOB_PIGMAN", 36);

define("ENTITY_OBJECT", 3);
	define("OBJECT_TRIPOD_CAMERA", 62);
	define("OBJECT_PRIMEDTNT", 65);
	define("OBJECT_ARROW", 80);
	define("OBJECT_SNOWBALL", 81);
	define("OBJECT_EGG", 82);
	define("OBJECT_PAINTING", 83);
	define("OBJECT_MINECART", 84);

define("ENTITY_ITEM", 4);
	define("ENTITY_ITEM_TYPE", 64);
	
define("ENTITY_FALLING", 5);
	define("FALLING_SAND", 66);
	
//TileEntities
define("TILE_SIGN", "Sign");
define("TILE_CHEST", "Chest");
	define("CHEST_SLOTS", 27);
define("TILE_FURNACE", "Furnace");
	define("FURNACE_SLOTS", 3);
define("TILE_NETHER_REACTOR", "NetherReactor");

const CORRECT_ENTITY_CLASSES = [
	ENTITY_PLAYER => true,
	ENTITY_MOB => [
		MOB_CHICKEN => true,
		MOB_COW => true,
		MOB_PIG => true,
		MOB_SHEEP => true,
			
		MOB_ZOMBIE => true,
		MOB_SKELETON => true,
		MOB_SPIDER => true,
		MOB_CREEPER => true,
		MOB_PIGMAN => true
	],
	ENTITY_OBJECT => [
		OBJECT_TRIPOD_CAMERA => true,
		OBJECT_PRIMEDTNT => true,
		OBJECT_ARROW => true,
		OBJECT_SNOWBALL => true,
		OBJECT_EGG => true,
		OBJECT_PAINTING => true,
		OBJECT_MINECART => true,
	],
	ENTITY_ITEM => true,
	ENTITY_FALLING => [
		FALLING_SAND => true
	]
	
];
