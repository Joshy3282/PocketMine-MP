<?php

namespace pocketmine\world\generator\overworld\decorator;

use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\object\Cactus;
use pocketmine\world\generator\populator\Decorator;

class CactusDecorator extends Decorator{

	public function decorate(ChunkManager $world, Random $random, int $chunk_x, int $chunk_z, Chunk $chunk) : void{
		$source_x = $chunk_x << Chunk::COORD_BIT_SIZE;
		$source_z = $chunk_z << Chunk::COORD_BIT_SIZE;
		$x = $random->nextBoundedInt(16);
		$z = $random->nextBoundedInt(16);
		$sourceY = $random->nextBoundedInt($chunk->getHighestBlockAt($x, $z) << 1);

		for($l = 0; $l < 10; ++$l){
			$i = $source_x + $random->nextBoundedInt(8) - $random->nextBoundedInt(8);
			$k = $source_z + $random->nextBoundedInt(8) - $random->nextBoundedInt(8);
			$j = $sourceY + $random->nextBoundedInt(4) - $random->nextBoundedInt(4);
			(new Cactus())->generate($world, $random, $x + $i, $j, $z + $k);
		}
	}
}