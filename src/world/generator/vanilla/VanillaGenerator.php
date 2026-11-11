<?php
define("EMPTY_MINI_CHUNK", str_repeat("\x00", 10240));
define("EMPTY_16x16_ARR", array_fill(0, 256, 0));
class VanillaGenerator implements LevelGenerator
{
	/**
	 * @var Level
	 */
	public $level;
	/**
	 * @var MersenneTwister
	 */
	private $rand;
	/**
	 * 
	 * @var NoiseGeneratorOctaves $upperInterpolationNoise
	 * @var NoiseGeneratorOctaves $lowerInterpolationNoise
	 * @var NoiseGeneratorOctaves $interpolationNoise
	 * @var NoiseGeneratorOctaves $beachNoise
	 * @var NoiseGeneratorOctaves $surfaceDepthNoise
	 * @var NoiseGeneratorOctaves $biomeNoise
	 * @var NoiseGeneratorOctaves $depthNoise
	 * @var NoiseGeneratorOctaves $treeNoise
	 */
	private $upperInterpolationNoise, $lowerInterpolationNoise, $interpolationNoise, $beachNoise, $surfaceDepthNoise, $biomeNoise, $depthNoise, $treeNoise;
	/**
	 * @var float[] $biomeNoises
	 * @var float[] $depthNoises
	 * @var float[] $interpolationNoises
	 * @var float[] $upperInterpolationNoises
	 * @var float[] $lowerInterpolationNoises
	 * @var Biome[] $biomes
	 * @var float[] $heights
	 * @var float[] $sandNoises
	 * @var float[] $gravelNoises
	 * @var float[] $surfaceDepthNoises
	 */
	private $biomeNoises, $depthNoises, $interpolationNoises, $upperInterpolationNoises, $lowerInterpolationNoises, $biomes, $heights, $sandNoises, $gravelNoises, $surfaceDepthNoises;
	/**
	 * @var BiomeSource
	 */
	private $biomeSource;
	
	private $heightMap = [];
	
	public function __construct(array $options = [])
	{
		
	}
	public function init(Level $level, Random $random)
	{
		$this->level = $level;
		$this->biomeSource = new BiomeSource($level);
		$this->rand = new MTRandom($level->getSeed());
		$this->upperInterpolationNoise = new NoiseGeneratorOctaves($this->rand, 16);
		$this->lowerInterpolationNoise = new NoiseGeneratorOctaves($this->rand, 16);
		$this->interpolationNoise = new NoiseGeneratorOctaves($this->rand, 8);
		$this->beachNoise = new NoiseGeneratorOctaves($this->rand, 4);
		$this->surfaceDepthNoise = new NoiseGeneratorOctaves($this->rand, 4);
		$this->biomeNoise = new NoiseGeneratorOctaves($this->rand, 10);
		$this->depthNoise = new NoiseGeneratorOctaves($this->rand, 16);
		$this->treeNoise = new NoiseGeneratorOctaves($this->rand, 8);
	}

	public function getSpawn()
	{
		return new Vector3(128, 64, 128);
	}

	public function populateChunk($chunkX, $chunkZ)
	{
		$chunkXWorld = $chunkX * 16;
		$chunkZWorld = $chunkZ * 16;
		//HeavyTile::instaFall = 1; TODO instant sand/gravel fall
		$biome = $this->biomeSource->getBiome($chunkXWorld + 16, $chunkZWorld + 16);
		$this->rand->setSeed($this->level->getSeed());
		$i1 = (int)(((int)($this->rand->nextInt() / 2)) * 2 + 1); //why php integers are so big....???????????????????????????????????????
		$j1 = (int)(((int)($this->rand->nextInt() / 2)) * 2 + 1);
		$this->rand->setSeed((($chunkX * $i1 + $chunkZ * $j1) & 0xffffffff) ^ $this->level->getSeed());
		for($i2 = 0; $i2 < 10; ++$i2){
			ClayFeature::place($this->level, $this->rand, $chunkXWorld + $this->rand->nextInt(16), $this->rand->nextInt(128), $chunkZWorld + $this->rand->nextInt(16));
		}
		for ($i3 = 0; $i3 < 20; ++$i3) {
			OreFeature::place($this->level, $this->rand, $chunkXWorld + $this->rand->nextInt(16), $this->rand->nextInt(128), $chunkZWorld + $this->rand->nextInt(16), DIRT, 32);
		}
		for ($i4 = 0; $i4 < 10; ++$i4) {
			OreFeature::place($this->level, $this->rand, $chunkXWorld + $this->rand->nextInt(16), $this->rand->nextInt(128), $chunkZWorld + $this->rand->nextInt(16), GRAVEL, 32);
		}
		for ($i5 = 0; $i5 < 20; ++$i5) {
			OreFeature::place($this->level, $this->rand, $chunkXWorld + $this->rand->nextInt(16), $this->rand->nextInt(128), $chunkZWorld + $this->rand->nextInt(16), COAL_ORE, 16);
		}
		for($i6 = 0; $i6 < 20; ++$i6){
			OreFeature::place($this->level, $this->rand, $chunkXWorld + $this->rand->nextInt(16), $this->rand->nextInt(64), $chunkZWorld + $this->rand->nextInt(16), IRON_ORE, 8);
		}
		for($i7 = 0; $i7 < 2; ++$i7){
			OreFeature::place($this->level, $this->rand, $chunkXWorld + $this->rand->nextInt(16), $this->rand->nextInt(32), $chunkZWorld + $this->rand->nextInt(16), GOLD_ORE, 8);
		}
		for($i8 = 0; $i8 < 8; ++$i8){
			OreFeature::place($this->level, $this->rand, $chunkXWorld + $this->rand->nextInt(16), $this->rand->nextInt(16),  $chunkZWorld + $this->rand->nextInt(16), REDSTONE_ORE, 7);
		}
		
		OreFeature::place($this->level, $this->rand, $chunkXWorld + $this->rand->nextInt(16), $this->rand->nextInt(16),  $chunkZWorld + $this->rand->nextInt(16), DIAMOND_ORE, 7);
		OreFeature::place($this->level, $this->rand, $chunkXWorld + $this->rand->nextInt(16), $this->rand->nextInt(16) + $this->rand->nextInt(16),  $chunkZWorld + $this->rand->nextInt(16), LAPIS_ORE, 6);
		
		
		$sample = (int) (((($this->treeNoise->getValue($chunkXWorld * 0.5, $chunkZWorld * 0.5) / 8) + ($this->rand->nextFloat() * 4)) + 4) / 3);
		$treesAmount = $this->rand->nextInt(10) == 0;
		if($biome == Biome::$forest) $treesAmount += $sample + 2;
		elseif($biome == Biome::$rainForest) $treesAmount += $sample + 2;
		elseif($biome == Biome::$seasonalForest) $treesAmount += $sample + 1;
		elseif($biome == Biome::$taiga) $treesAmount += $sample + 1;
		elseif($biome == Biome::$desert) $treesAmount -= 20;
		elseif($biome == Biome::$tundra) $treesAmount -= 20;
		elseif($biome == Biome::$plains) $treesAmount -= 20;
		for($l8 = 0; $l8 < $treesAmount; ++$l8){
			$l12 = $chunkXWorld + $this->rand->nextInt(16) + 8;
			$j15 = $chunkZWorld + $this->rand->nextInt(16) + 8;
			/**
			 * @var Feature $tree
			 */
			$tree = $biome->getTreeFeature($this->rand);
			$tree->place($this->level, $this->rand, $l12, $this->getHeightValue($l12, $j15), $j15);
		}
		for($i9 = 0; $i9 < 2; ++$i9){
			$i13 = $chunkXWorld + $this->rand->nextInt(16) + 8;
			$k15 = $this->rand->nextInt(128);
			$l17 = $chunkZWorld + $this->rand->nextInt(16) + 8;
			Feature::$FLOWER_YELLOW->place($this->level, $this->rand, $i13, $k15, $l17);
		}
		if($this->rand->nextInt(2) == 0){
			$j9 = $chunkXWorld + $this->rand->nextInt(16) + 8;
			$j13 = $this->rand->nextInt(128);
			$l15 = $chunkZWorld + $this->rand->nextInt(16) + 8;
			Feature::$FLOWER_RED->place($this->level, $this->rand, $j9, $j13, $l15);
		}
		if($this->rand->nextInt(4) == 0){
			$j9 = $chunkXWorld + $this->rand->nextInt(16) + 8;
			$j13 = $this->rand->nextInt(128);
			$l15 = $chunkZWorld + $this->rand->nextInt(16) + 8;
			Feature::$MUSHROOM_BROWN->place($this->level, $this->rand, $j9, $j13, $l15);
		}
		if($this->rand->nextInt(8) == 0){
			$j9 = $chunkXWorld + $this->rand->nextInt(16) + 8;
			$j13 = $this->rand->nextInt(128);
			$l15 = $chunkZWorld + $this->rand->nextInt(16) + 8;
			Feature::$MUSHROOM_RED->place($this->level, $this->rand, $j9, $j13, $l15);
		}
		
	}
	public function generateChunk($chunkX, $chunkZ)
	{
		$this->rand->setSeed(341872712 * $chunkX + 132899541 * $chunkZ);
		$this->biomes = $this->biomeSource->getBiomeBlock($chunkX * 16, $chunkZ * 16, 16, 16);
		$chunkz = array_fill(0, 8, EMPTY_MINI_CHUNK); //[$blockY >> 4][($blockY & 0xf) + ($blockX << 5) + ($blockZ << 9)]. y&f+x&f+z&f
		$this->prepareHeights($chunkX, $chunkZ, $chunkz, $this->biomes, $this->biomeSource->temperatureNoises);
		$this->buildSurfaces($chunkX, $chunkZ, $chunkz, $this->biomes);
		$this->generateHeightmap($chunkX, $chunkZ, $chunkz);
		
		for($Y = 0; $Y < 8; ++$Y){
			$index = ($chunkZ << 4) + $chunkX;
			$this->level->level->chunks[$index][$Y] = $chunkz[$Y];
			$this->level->level->chunkChange[$index][$Y] = 10240;
			$this->level->level->locationTable[$index][0] |= 1 << $Y; //TODO mv out of loop
		}
		$this->level->level->chunkChange[$index][-1] = true;
		
	}
	
	public function populateLevel()
	{}
	public function setHeightValue($x, $z, $hv){
		if($x > 255 || $x < 0 || $z > 255 || $z < 0) return;
		$cX = $x >> 4;
		$cZ = $z >> 4;
		$bX = $x & 0xf;
		$bZ = $z & 0xf;
		$this->heightMap[$cX + ($cZ * 16)][$bX + ($bZ * 16)] = $hv;
	}
	public function getHeightValue($x, $z){
		if($x > 255 || $x < 0 || $z > 255 || $z < 0) return 0;
		$cX = $x >> 4;
		$cZ = $z >> 4;
		$bX = $x & 0xf;
		$bZ = $z & 0xf;
		return $this->heightMap[$cX + ($cZ * 16)][$bX + ($bZ * 16)];
	}
	public function generateHeightmap($x, $z, &$chunkz){
		$heightmapCPtr = EMPTY_16x16_ARR;
		for($blockX = 0; $blockX < 16; ++$blockX){
			for($blockZ = 0; $blockZ < 16; ++$blockZ){
				for($Y = 7; $Y >= 0; --$Y){
					for($cY = 15; $cY >= 0; --$cY){
						$blockY = $Y*16 + $cY;
						$blockID = ord($chunkz[$Y][$cY + $blockX*40 + $blockZ*640]);
						if($blockID > 0) {
							$heightmapCPtr[$blockX + ($blockZ * 16)] = $blockY + 1 ;
							break 2;
						}
					}
				}
			}
		}
		$this->heightMap[$x + ($z * 16)] = $heightmapCPtr;
	}
	
	/**Vanilla Functions Implementation starts here**/
	/**
	 * Decorate terrain with grass, water & other stuff. Uses ZXY placement format
	 * @param int $chunkX
	 * @param int $chunkZ
	 * @param array $chunks
	 * @param Biome[] $biomes
	 */
	public function buildSurfaces($chunkX, $chunkZ, &$chunks, $biomes){
		$this->sandNoises = $this->beachNoise->generateNoiseOctaves($chunkX * 16, $chunkZ * 16, 0, 16, 16, 1, 0.03125, 0.03125, 1);
		$this->gravelNoises = $this->beachNoise->generateNoiseOctaves($chunkX * 16, 109.01, $chunkZ * 16, 16, 1, 16, 0.03125, 1, 0.03125);
		$this->surfaceDepthNoises = $this->surfaceDepthNoise->generateNoiseOctaves($chunkX * 16, $chunkZ * 16, 0, 16, 16, 1, 0.0625, 0.0625, 0.0625);
		//TODO blockZ is actually blockX here?
		for($blockX = 0; $blockX < 16; ++$blockX){
			for($blockZ = 0; $blockZ < 16; ++$blockZ){
				/** @var Biome $biome **/
				$biome = $biomes[$blockX + ($blockZ * 16)];
				$z = ($this->sandNoises[$blockX + ($blockZ * 16)] + ($this->rand->nextFloat() * 0.2)) > 0;
				$z2 = ($this->gravelNoises[$blockX + ($blockZ * 16)] + ($this->rand->nextFloat() * 0.2)) > 3;
				$nextFloat = (int)(($this->surfaceDepthNoises[$blockX + ($blockZ * 16)] / 3) + 3 + ($this->rand->nextFloat() * 0.25));
				$i = -1;
				$b = $biome->topBlock;
				$b2 = $biome->fillerBlock;
				for($blockY = 127; $blockY >= 0; --$blockY){
					if($blockY <= $this->rand->nextInt(5)){
						$chunks[$blockY >> 4][($blockY & 0xf) + $blockZ*40 + $blockX*640] = "\x07";
					}else{
						$b3 = ord($chunks[$blockY >> 4][($blockY & 0xf) + $blockZ*40 + $blockX*640]);
						if($b3 == 0){
							$i = -1;
						}elseif($b3 == STONE){
							if($i == -1){
								if($nextFloat > 0){
									if($blockY >= 60 && $blockY <= 65){
										if($z){
											$b2 = $b = SAND;
										}elseif($z2){
											$b = 0;
											$b2 = GRAVEL;
										}else{
											$b = $biome->topBlock;
											$b2 = $biome->fillerBlock;
										}
									}
								}else{
									$b = 0;
									$b2 = STONE;
								}
								
								$b = $blockY < 64 && $b == 0 ? STILL_WATER : $b;
								$i = $nextFloat;
								$chunks[$blockY >> 4][($blockY & 0xf) + ($blockZ*40) + ($blockX*640)] = $blockY >= 63 ? chr($b) : chr($b2);
							}elseif($i > 0){
								--$i;
								$chunks[$blockY >> 4][($blockY & 0xf) + ($blockZ*40) + ($blockX*640)] = chr($b2);
								if($i == 0 && $b2 == SAND){
									$i = $this->rand->nextInt(4);
									$b2 = SANDSTONE;
								}
							}
						}
					}
				}
			}
		}
	}
	
	
	public function prepareHeights($chunkX, $chunkZ, &$chunks, $biomes, $temperatures){
		$this->heights = $this->getHeights($chunkX * 4, 0, $chunkZ * 4, 5, 17, 5);
		//if($chunkX == 15 && $chunkZ == 15) var_dump($this->heights);
		for($unkX = 0; $unkX < 4; ++$unkX){
			for($unkZ = 0; $unkZ < 4; ++$unkZ){
				for($unkY = 0; $unkY < 16; ++$unkY){
					$f = $this->heights[(((($unkX) * 5) + $unkZ) * 17) + $unkY];
					$f2 = $this->heights[(((($unkX) * 5) + $unkZ + 1) * 17) + $unkY];
					$f3 = $this->heights[(((($unkX + 1) * 5) + $unkZ) * 17) + $unkY];
					$f4 = $this->heights[(((($unkX + 1) * 5) + $unkZ + 1) * 17) + $unkY];
					
					$f5 = ($this->heights[(((($unkX) * 5) + ($unkZ)) * 17) + ($unkY + 1)] - $f) * 0.125;
					$f6 = ($this->heights[(((($unkX) * 5) + ($unkZ + 1)) * 17) + ($unkY + 1)] - $f2) * 0.125;
					$f7 = ($this->heights[(((($unkX + 1) * 5) + ($unkZ)) * 17) + ($unkY + 1)] - $f3) * 0.125;
					$f8 = ($this->heights[(((($unkX + 1) * 5) + ($unkZ + 1)) * 17) + ($unkY + 1)] - $f4) * 0.125;
					
					for($unkYY = 0; $unkYY < 8; ++$unkYY){
						$f9 = $f;
						$f10 = $f2;
						$f11 = ($f3 - $f) * 0.25;
						$f12 = ($f4 - $f2) * 0.25;
						for($unkXX = 0; $unkXX < 4; ++$unkXX){
							$f13 = $f9;
							$f14 = ($f10 - $f9) * 0.25;
							for($unkZZ = 0; $unkZZ < 4; ++$unkZZ){
								$d15 = $temperatures[((($unkX * 4) + $unkXX) * 16) + ($unkZ * 4) + $unkZZ];
								$i3 = $f13 > 0; //true -> STONE, false -> AIR
								
								if(!$i3 && ($unkY * 8) + $unkYY < 64){
									$i3 = $d15 < 0.5 && (($unkY * 8) + $unkYY) >= 63 ? ICE : STILL_WATER;
								}
								
								$fx = ($unkXX + ($unkX * 4));
								$fy = (($unkY * 8) + $unkYY);
								$fz = ($unkZZ + ($unkZ * 4));
								$chunks[$fy >> 4][($fy & 0xf) + $fx*40 + $fz*640] = chr($i3);
								$f13 += $f14;
							}
							$f9 += $f11;
							$f10 += $f12;
						}
						$f += $f5;
						$f2 += $f6;
						$f3 += $f7;
						$f4 += $f8;
					}
				}
			}
		}
	}
	
	public function getHeights($chunkX, $chunkY, $chunkZ, $scaleX, $scaleY, $scaleZ){
		$heights = array_fill(0, $scaleX*$scaleY*$scaleZ, 0);
		$rainNoises = $this->biomeSource->rainfallNoises;
		$tempNoises = $this->biomeSource->temperatureNoises;
		$this->biomeNoises = $this->biomeNoise->generateNoiseOctaves($chunkX, $chunkZ, $scaleX, $scaleZ, 1.121, 1.121, 0.5);
		$this->depthNoises = $this->depthNoise->generateNoiseOctaves($chunkX, $chunkZ, $scaleX, $scaleZ, 200, 200, 0.5);
		$this->interpolationNoises = $this->interpolationNoise->generateNoiseOctaves($chunkX, $chunkY, $chunkZ, $scaleX, $scaleY, $scaleZ, 8.5552, 4.2776, 8.5552);
		$this->upperInterpolationNoises = $this->upperInterpolationNoise->generateNoiseOctaves($chunkX, $chunkY, $chunkZ, $scaleX, $scaleY, $scaleZ, 684.41, 684.41, 684.41);
		$this->lowerInterpolationNoises = $this->lowerInterpolationNoise->generateNoiseOctaves($chunkX, $chunkY, $chunkZ, $scaleX, $scaleY, $scaleZ, 684.41, 684.41, 684.41);
		$k1 = $l1 = 0;
		$i2 = ((int)(16 / $scaleX));
		for($j2 = 0; $j2 < $scaleX; ++$j2){
			$k2 = (int)($j2 * $i2 + $i2 / 2);
			for($l2 = 0; $l2 < $scaleZ; ++$l2){
				$i3 = (int)($l2 * $i2 + $i2 / 2);
				$d2 = $tempNoises[$k2 * 16 + $i3];
				$d3 = $rainNoises[$k2 * 16 + $i3] * $d2;
				$d4 = 1 - $d3;
				$d4 *= $d4;
				$d4 *= $d4;
				$d4 = 1 - $d4;
				
				$d5 = (($this->biomeNoises[$l1] + 256) / 512);
				$d5 *= $d4;
				if($d5 > 1) $d5 = 1;
				$d6 = $this->depthNoises[$l1] / 8000;
				if($d6 < 0) $d6 = -$d6 * 0.3;
				
				$d6 = $d6 * 3 - 2;
				if($d6 < 0){
					$d6 /= 2;
					if($d6 < -1) $d6 = -1;
					$d6 /= 1.4;
					$d6 /= 2;
					$d5 = 0;
				}else{
					if($d6 > 1) $d6 = 1;
					$d6 /= 8;
				}
				
				if($d5 < 0) $d5 = 0;
				
				$d5 += 0.5;
				$d6 = ($d6 * $scaleY) / 16;
				$d7 = ($scaleY / 2 + $d6 * 4);
				++$l1;
				for($j3 = 0; $j3 < $scaleY; ++$j3){
					$d8 = 0;
					$d9 = (((float)$j3 - $d7) * 12) / $d5;
					if($d9 < 0) $d9 *= 4;
					$d10 = $this->upperInterpolationNoises[$k1] / 512;
					$d11 = $this->lowerInterpolationNoises[$k1] / 512;
					$d12 = ($this->interpolationNoises[$k1] / 10 + 1) / 2;
					
					if($d12 < 0) $d8 = $d10;
					elseif($d12 > 1) $d8 = $d11;
					else $d8 = $d10 + ($d11 - $d10) * $d12;
					
					$d8 -= $d9;
					if($j3 > $scaleY - 4){
						$d13 = ($j3 - ($scaleY - 4)) / 3;
						$d8 = $d8 * (1 - $d13) + -10 * $d13;
					}
					$heights[$k1] = $d8;
					++$k1;
				}
			}
		}
		
		return $heights;
	}

}

