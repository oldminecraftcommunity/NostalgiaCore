<?php

class CraftingRecipes{
	const TYPE_INVENTORY = 0;
	const TYPE_CRAFTIGTABLE = 1;
	const TYPE_STONECUTTER = 2;
	
	private static $small = [ // Probably means craftable on crafting bench and in inventory. Name it better!
							   // Building
		"CLAY:?x4=>CLAY_BLOCK:0x1",
		"WOODEN_PLANKS:?x4=>WORKBENCH:0x1",
		"GLOWSTONE_DUST:?x4=>GLOWSTONE_BLOCK:0x1",
		"PUMPKIN:?x1,TORCH:?x1=>LIT_PUMPKIN:0x1",
		"SNOWBALL:?x4=>SNOW_BLOCK:0x1",
		"WOODEN_PLANKS:?x2=>STICK:0x4",
		"COBBLESTONE:?x4=>STONECUTTER:0x1",
		"WOOD:0x1=>WOODEN_PLANKS:0x4",
		"WOOD:1x1=>WOODEN_PLANKS:1x4",
		"WOOD:2x1=>WOODEN_PLANKS:2x4",
		// "WOOD:3x1=>WOODEN_PLANKS:3x4",
		"WOOL:0x1,DYE:0x1=>WOOL:15x1",
		"WOOL:0x1,DYE:1x1=>WOOL:14x1",
		"WOOL:0x1,DYE:2x1=>WOOL:13x1",
		"WOOL:0x1,DYE:3x1=>WOOL:12x1",
		"WOOL:0x1,DYE:4x1=>WOOL:11x1",
		"WOOL:0x1,DYE:5x1=>WOOL:10x1",
		"WOOL:0x1,DYE:6x1=>WOOL:9x1",
		"WOOL:0x1,DYE:7x1=>WOOL:8x1",
		"WOOL:0x1,DYE:8x1=>WOOL:7x1",
		"WOOL:0x1,DYE:9x1=>WOOL:6x1",
		"WOOL:0x1,DYE:10x1=>WOOL:5x1",
		"WOOL:0x1,DYE:11x1=>WOOL:4x1",
		"WOOL:0x1,DYE:12x1=>WOOL:3x1",
		"WOOL:0x1,DYE:13x1=>WOOL:2x1",
		"WOOL:0x1,DYE:14x1=>WOOL:1x1",
		"STRING:?x4=>WOOL:0x1",

		// Tools
		"IRON_INGOT:?x1,FLINT:?x1=>FLINT_STEEL:0x1",
		"IRON_INGOT:?x2=>SHEARS:0x1",
		"COAL:0x1,STICK:?x1=>TORCH:0x4",
		"COAL:1x1,STICK:?x1=>TORCH:0x4",

		// Food & protection
		"MELON_SLICE:?x1=>MELON_SEEDS:0x1",
		"PUMPKIN:?x1=>PUMPKIN_SEEDS:0x4",
		"PUMPKIN:?x1,EGG:?x1,SUGAR:?x1=>PUMPKIN_PIE:0x1",
		"BROWN_MUSHROOM:?x1,RED_MUSHROOM:?x1,BOWL:?x1=>MUSHROOM_STEW:0x1",
		"SUGARCANE:?x1=>SUGAR:0x1",
		"MELON_SLICE:?x1=>MELON_SEEDS:0x1",
		"HAY_BALE:?x1=>WHEAT:0x9",

		// Items
		"DIAMOND_BLOCK:?x1=>DIAMOND:0x9",
		"GOLD_BLOCK:?x1=>GOLD_INGOT:0x9",
		"IRON_BLOCK:?x1=>IRON_INGOT:0x9",
		"LAPIS_BLOCK:?x1=>DYE:4x9", // Lapis Lazuli
		"DANDELION:?x1=>DYE:11x2", // Dandelion Yellow
		"BONE:?x1=>DYE:15x3", // Bone Meal
		"DYE:0x1,DYE:14x1=>DYE:3x2", // Cocoa Beans
		"DYE:0x1,DYE:1x1,DYE:11x1=>DYE:3x3", // Cocoa Beans
		"DYE:1x1,DYE:15x1=>DYE:9x2", // Pink Dye
		"DYE:1x1,DYE:11x1=>DYE:14x2", // Orange Dye
		"DYE:2x1,DYE:15x1=>DYE:10x2", // Lime Dye
		"DYE:4x1,DYE:15x1=>DYE:12x2", // Light Blue Dye
		"DYE:2x1,DYE:4x1=>DYE:6x2", // Cyan Dye
		"DYE:1x1,DYE:4x1=>DYE:5x2", // Purple Dye
		"DYE:1x1,DYE:4x1,DYE:15x1=>DYE:13x3", // Magenta Dye
		"BEETROOT:?x1=>DYE:1x2", // Rose Red
		"DYE:15x1,DYE:1x2,DYE:4x1=>DYE:13x4", // Magenta Dye
		"DYE:5x1,DYE:9x1=>DYE:13x2", // Magenta Dye
		"DYE:0x1,DYE:15x1=>DYE:8x2", // Gray Dye
		"DYE:0x1,DYE:15x2=>DYE:7x3", // Light Gray Dye
		"DYE:0x1,DYE:8x1=>DYE:7x2", // Light Gray Dye
		"WOOL:14x2=>CARPET:14x3"
	];

	private static $big = [ // Probably means only craftable on crafting bench. Name it better!
							 // Building
		"WOOL:?x3,WOODEN_PLANKS:?x3=>BED:0x1",
		"WOODEN_PLANKS:?x8=>CHEST:0x1",
		"STICK:?x6=>FENCE:0x2",
		"STICK:?x4,WOODEN_PLANKS:?x2=>FENCE_GATE:0x1",
		"COBBLESTONE:?x8=>FURNACE:0x1",
		"GLASS:?x6=>GLASS_PANE:0x16",
		"STICK:?x7=>LADDER:0x2",
		"DIAMOND:?x3,IRON_INGOT:?x6=>NETHER_REACTOR:0x1",
		"WOODEN_PLANKS:?x6=>TRAPDOOR:0x2",
		"WOODEN_PLANKS:?x6=>WOODEN_DOOR:0x1",
		"WOODEN_PLANKS:0x6=>WOODEN_STAIRS:0x4",
		"WOODEN_PLANKS:0x3=>WOOD_SLAB:0x6",
		"WOODEN_PLANKS:1x6=>SPRUCE_WOOD_STAIRS:0x4",
		"WOODEN_PLANKS:1x3=>WOOD_SLAB:1x6",
		"WOODEN_PLANKS:2x6=>BIRCH_WOOD_STAIRS:0x4",
		"WOODEN_PLANKS:2x3=>WOOD_SLAB:2x6",
		// "WOODEN_PLANKS:3x6=>JUNGLE_WOOD_STAIRS:0x4",
		// "WOODEN_PLANKS:3x3=>WOOD_SLAB:3x6",

		// Tools
		"STICK:?x1,FEATHER:?x1,FLINT:?x1=>ARROW:0x4",
		"STICK:?x3,STRING:?x3=>BOW:0x1",
		"IRON_INGOT:?x3=>BUCKET:0x1",
		"GOLD_INGOT:?x4,REDSTONE_DUST:?x1=>CLOCK:0x1",
		"IRON_INGOT:?x4,REDSTONE_DUST:?x1=>COMPASS:0x1",
		"DIAMOND:?x3,STICK:?x2=>DIAMOND_AXE:0x1",
		"DIAMOND:?x2,STICK:?x2=>DIAMOND_HOE:0x1",
		"DIAMOND:?x3,STICK:?x2=>DIAMOND_PICKAXE:0x1",
		"DIAMOND:?x1,STICK:?x2=>DIAMOND_SHOVEL:0x1",
		"DIAMOND:?x2,STICK:?x1=>DIAMOND_SWORD:0x1",
		"GOLD_INGOT:?x3,STICK:?x2=>GOLDEN_AXE:0x1",
		"GOLD_INGOT:?x2,STICK:?x2=>GOLDEN_HOE:0x1",
		"GOLD_INGOT:?x3,STICK:?x2=>GOLDEN_PICKAXE:0x1",
		"GOLD_INGOT:?x1,STICK:?x2=>GOLDEN_SHOVEL:0x1",
		"GOLD_INGOT:?x2,STICK:?x1=>GOLDEN_SWORD:0x1",
		"IRON_INGOT:?x3,STICK:?x2=>IRON_AXE:0x1",
		"IRON_INGOT:?x2,STICK:?x2=>IRON_HOE:0x1",
		"IRON_INGOT:?x3,STICK:?x2=>IRON_PICKAXE:0x1",
		"IRON_INGOT:?x1,STICK:?x2=>IRON_SHOVEL:0x1",
		"IRON_INGOT:?x2,STICK:?x1=>IRON_SWORD:0x1",
		"COBBLESTONE:?x3,STICK:?x2=>STONE_AXE:0x1",
		"COBBLESTONE:?x2,STICK:?x2=>STONE_HOE:0x1",
		"COBBLESTONE:?x3,STICK:?x2=>STONE_PICKAXE:0x1",
		"COBBLESTONE:?x1,STICK:?x2=>STONE_SHOVEL:0x1",
		"COBBLESTONE:?x2,STICK:?x1=>STONE_SWORD:0x1",
		"SAND:?x4,GUNPOWDER:?x5=>TNT:0x1",
		"WOODEN_PLANKS:?x3,STICK:?x2=>WOODEN_AXE:0x1",
		"WOODEN_PLANKS:?x2,STICK:?x2=>WOODEN_HOE:0x1",
		"WOODEN_PLANKS:?x3,STICK:?x2=>WOODEN_PICKAXE:0x1",
		"WOODEN_PLANKS:?x1,STICK:?x2=>WOODEN_SHOVEL:0x1",
		"WOODEN_PLANKS:?x2,STICK:?x1=>WOODEN_SWORD:0x1",
		"IRON_INGOT:?x6,STICK:?x1=>RAIL:0x16",
		"GOLD_INGOT:?x6,STICK:?x1,REDSTONE:?x1=>POWERED_RAIL:0x6",

		// Food & protection
		"BEETROOT:?x4,BOWL:?x1=>BEETROOT_SOUP:0x1",
		"WOODEN_PLANKS:?x3=>BOWL:0x4",
		"WHEAT:?x3=>BREAD:0x1",
		"WHEAT:?x3,BUCKET:1x3,EGG:?x1,SUGAR:?x2=>CAKE:0x1,BUCKET:0x3",
		"DIAMOND:?x4=>DIAMOND_BOOTS:0x1",
		"DIAMOND:?x8=>DIAMOND_CHESTPLATE:0x1",
		"DIAMOND:?x5=>DIAMOND_HELMET:0x1",
		"DIAMOND:?x7=>DIAMOND_LEGGINGS:0x1",
		"GOLD_INGOT:?x4=>GOLDEN_BOOTS:0x1",
		"GOLD_INGOT:?x8=>GOLDEN_CHESTPLATE:0x1",
		"GOLD_INGOT:?x5=>GOLDEN_HELMET:0x1",
		"GOLD_INGOT:?x7=>GOLDEN_LEGGINGS:0x1",
		"IRON_INGOT:?x4=>IRON_BOOTS:0x1",
		"IRON_INGOT:?x8=>IRON_CHESTPLATE:0x1",
		"IRON_INGOT:?x5=>IRON_HELMET:0x1",
		"IRON_INGOT:?x7=>IRON_LEGGINGS:0x1",
		"LEATHER:?x4=>LEATHER_BOOTS:0x1",
		"LEATHER:?x8=>LEATHER_TUNIC:0x1",
		"LEATHER:?x5=>LEATHER_CAP:0x1",
		"LEATHER:?x7=>LEATHER_PANTS:0x1",
		// "FIRE:?x4=>CHAIN_BOOTS:0x1",
		// "FIRE:?x8=>CHAIN_CHESTPLATE:0x1",
		// "FIRE:?x5=>CHAIN_HELMET:0x1",
		// "FIRE:?x7=>CHAIN_LEGGINGS:0x1",

		// Items
		"DIAMOND:?x9=>DIAMOND_BLOCK:0x1",
		"GOLD_INGOT:?x9=>GOLD_BLOCK:0x1",
		"IRON_INGOT:?x9=>IRON_BLOCK:0x1",
		"IRON_INGOT:?x5=>MINECART:0x1",
		"WHEAT:?x9=>HAY_BALE:0x1",
		"PAPER:?x3=>BOOK:0x1",
		"WOODEN_PLANKS:?x6,BOOK:?x3=>BOOKSHELF:0x1",
		"DYE:4x9=>LAPIS_BLOCK:0x1",
		"WOOL:?x1,STICK:?x8=>PAINTING:0x1",
		"SUGARCANE:?x3=>PAPER:0x3",
		"WOODEN_PLANKS:?x6,STICK:?x1=>SIGN:0x1",
		"IRON_INGOT:?x6=>IRON_BARS:0x16",
		"COAL:0x9=>COAL_BLOCK:0x1",
		"COAL_BLOCK:?x1=>COAL:0x9",
		"MELON_SLICE:?x9=>MELON_BLOCK:0x1"
	];

	private static $stone = [
		"QUARTZ:?x4=>QUARTZ_BLOCK:0x1",
		"BRICKS_BLOCK:?x6=>BRICK_STAIRS:0x4",
		"BRICK:?x4=>BRICKS_BLOCK:0x1",
		"BRICKS_BLOCK:?x3=>SLAB:4x6",
		"SLAB:6x2=>QUARTZ_BLOCK:1x1",
		"COBBLESTONE:?x3=>SLAB:3x6",
		"COBBLESTONE:0x6=>STONE_WALL:0x6",
		"MOSSY_STONE:0x6=>STONE_WALL:1x6",
		"NETHER_BRICK:?x4=>NETHER_BRICKS:0x1",
		"NETHER_BRICKS:?x6=>NETHER_BRICKS_STAIRS:0x4",
		"QUARTZ_BLOCK:?x2=>QUARTZ_BLOCK:2x2",
		"QUARTZ_BLOCK:?x3=>SLAB:6x6",
		"SANDSTONE:0x6=>SANDSTONE_STAIRS:0x4",
		"SAND:?x4=>SANDSTONE:0x1",
		"SANDSTONE:0x4=>SANDSTONE:2x4",
		"SLAB:1x2=>SANDSTONE:1x1",
		"SANDSTONE:0x3=>SLAB:1x6",
		"STONE_BRICK:?x6=>STONE_BRICK_STAIRS:0x4",
		"STONE:?x4=>STONE_BRICK:0x4",
		"STONE_BRICKS:?x3=>SLAB:5x6",
		"STONE:?x3=>SLAB:0x6",
		"COBBLESTONE:?x6=>COBBLESTONE_STAIRS:0x4",
	];

	private static $craftingTableRecipes = [];
	private static $inventoryRecipes = [];
	private static $stoneCutterRecipes = [];
	
	private static $craftingTablePossibleResults = [];
	private static $inventoryPossibleResults = [];
	private static $stoneCutterPossibleResults = [];
	
	
	
	public static function init(){
		
		foreach(CraftingRecipes::$small as $recipe){
			CraftingRecipes::addRecipe($recipe, self::TYPE_INVENTORY);
			CraftingRecipes::addRecipe($recipe, self::TYPE_CRAFTIGTABLE);
		}
		
		foreach(CraftingRecipes::$big as $recipe){
			CraftingRecipes::addRecipe($recipe, self::TYPE_CRAFTIGTABLE);
		}
		
		foreach(CraftingRecipes::$stone as $recipe){
			CraftingRecipes::addRecipe($recipe, self::TYPE_STONECUTTER);
		}
	}

	public static function fromString($str){
		[$idm, $cnt] = explode("x", $str);
		[$id, $meta] = explode(":", $idm);
		$id = BlockAPI::blockIDFromString($id);
		
		return [$id, ($meta == "?") ? "?" : ((int)$meta), (int)$cnt];
	}
	
	public static function addRecipe($recipe, $type){
		[$ingridients, $results] = explode("=>", $recipe);
		$results_arr = []; //indexed by id, full [id, meta, cnt]
		$results_idmeta = []; //indexed by id, "id:meta" only
		foreach(explode(",", $results) as $res){
			[$id, $meta, $cnt] = self::fromString($res);
			if($meta === "?") throw new RuntimeException("Unknown metadata in result when trying to add $recipe (type: $type)");
			$results_arr[$id] = [$id, $meta, $cnt];
			$results_idmeta[$id] = "{$id}:{$meta}";
		}
		ksort($results_arr);
		ksort($results_idmeta);
		$result_index = implode(",", $results_idmeta);
		
		$ingridients_arr = [];
		foreach(explode(",", $ingridients) as $resultstr){
			[$id, $meta, $cnt] = self::fromString($resultstr);
			$ingridients_arr[] = [$id, $meta, $cnt]; 
		}
		
		switch($type){
			case self::TYPE_CRAFTIGTABLE:
				$arr = &self::$craftingTableRecipes;
				$arr_c = &self::$craftingTablePossibleResults;
				break;
			case self::TYPE_INVENTORY:
				$arr = &self::$inventoryRecipes;
				$arr_c = &self::$inventoryPossibleResults;
				break;
			case self::TYPE_STONECUTTER:
				$arr = &self::$stoneCutterRecipes;
				$arr_c = &self::$stoneCutterPossibleResults;
				break;
			default:
				throw new RuntimeException("Unknown type: {$type}");
		}
			
		if(!isset($arr[$result_index])) $arr[$result_index] = [];
		$arr[$result_index][] = ["in" => $ingridients_arr, "out" => array_values($results_arr)];

		foreach($results_idmeta as $resitem){
			if(!isset($arr_c[$resitem])){
				$arr_c[$resitem] = [];
			}
			foreach($results_idmeta as $resitem2){
				$arr_c[$resitem][$resitem2] = true;
			}
		}

	}

	/**
	 * Checks can craft some item
	 * 
	 * @param array $craftItems items that will be crafted
	 * @param array $recipeItems items that will be consumed
	 * @param int $type craft type (CraftingRecipes::TYPE_INVENTORY, CraftingRecipes::TYPE_CRAFTIGTABLE, CraftingRecipes::TYPE_STONECUTTER)
	 * @return array|true|false recipe that will be used or bool. If returned false, crafting will be aborted. If returned true crafting wont be aborted
	 */
	public static function canCraft(array $craftItems, array $recipeItems, $type){
		$craftIndexArr = [];
		$craftCount = [];
		foreach($craftItems as $it){
			$k = "{$it[0]}:{$it[1]}";
			$craftIndexArr[$it[0]] = $k;
			$craftCount[$k] = $it[2];
		}
		ksort($craftIndexArr);
		$craftIndex = implode(",", $craftIndexArr);
		switch($type){
			case self::TYPE_CRAFTIGTABLE:
				$arr = &self::$craftingTableRecipes;
				$arr_c = &self::$craftingTablePossibleResults;
				break;
			case self::TYPE_INVENTORY:
				$arr = &self::$inventoryRecipes;
				$arr_c = &self::$inventoryPossibleResults;
				break;
			case self::TYPE_STONECUTTER:
				$arr = &self::$stoneCutterRecipes;
				$arr_c = &self::$stoneCutterPossibleResults;
				break;
			default:
				ConsoleAPI::error("Tried crafting recipe with unknown type {$type}!");
				return false;
		}

		if(!isset($arr[$craftIndex])) { //recipe for those ingridients was not found
			$allcombos = $arr_c[current($craftIndexArr)] ?? [];
			foreach($craftIndexArr as $res){
				if(!isset($allcombos[$res])){
					ConsoleAPI::info("Recipe with $craftIndex is not found. Crafting aborted. $res");
					return false;  //recipe not found and wont be found
				}
			}
			ConsoleAPI::info("Recipe with $craftIndex is not found but it might be found later. Crafting continued.");
			return true; //recipe might be found next time
		}

		foreach($arr[$craftIndex] as $recipe){
			$ingridients = $recipe["in"];
			$outputs = $recipe["out"];

			$multiplier = null;
			$ok = true;
			foreach($outputs as $out){
				$k = "{$out[0]}:{$out[1]}";
				if(!isset($craftCount[$k]) || $out[2] <= 0 || $craftCount[$k] % $out[2] !== 0 || ($craftCount[$k] / $out[2]) < 1){
					$ok = false;
					break;
				}
				$m = $craftCount[$k] / $out[2];
				if($multiplier === null) $multiplier = $m;
				elseif($multiplier !== $m) $ok = false;
			}
			if(!$ok) continue;

			foreach($ingridients as $item){
				if($item[1] === "?"){ // any metadata is allowed
					$needcnt = $item[2] * $multiplier;
					foreach($recipeItems as $idmeta => $it){
						$id = $idmeta >> 16;
						if($id != $item[0]) continue;
						$needcnt -= $it[2];
					}
					if($needcnt != 0) {
						goto skip_recipe;
					}
				}else{
					$exceptedIndex = ($item[0] << 16) | ($item[1] & 0xffff);
					if(!isset($recipeItems[$exceptedIndex])) {
						goto skip_recipe; //dont check count if no idmeta pair is in ingridients
					}
					$it = $recipeItems[$exceptedIndex];
					$need = $item[2] * $multiplier;
					if($it[0] != $item[0] || $it[1] != $item[1] || $it[2] != $need) {
						goto skip_recipe;
					}
				}
			}
			//recipe is correct
			return [$craftItems, $ingridients];

			skip_recipe:
		}
		return true;
	}

}
