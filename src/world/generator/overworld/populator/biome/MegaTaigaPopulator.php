<?php

namespace pocketmine\world\generator\overworld\populator\biome;

use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\object\tree\MegaPineTree;
use pocketmine\world\generator\object\tree\MegaSpruceTree;
use pocketmine\world\generator\object\tree\RedwoodTree;
use pocketmine\world\generator\object\tree\TallRedwoodTree;
use pocketmine\world\generator\overworld\biome\BiomeIds;
use pocketmine\world\generator\overworld\decorator\StoneBoulderDecorator;
use pocketmine\world\generator\overworld\decorator\types\TreeDecoration;

class MegaTaigaPopulator extends TaigaPopulator{

	/** @var TreeDecoration[] */
	protected static array $TREES;

	protected static function initTrees() : void{
		self::$TREES = [
			new TreeDecoration(RedwoodTree::class, 52),
			new TreeDecoration(TallRedwoodTree::class, 26),
			new TreeDecoration(MegaPineTree::class, 36),
			new TreeDecoration(MegaSpruceTree::class, 3)
		];
	}

	public function getBiomes() : ?array{
		return [BiomeIds::MEGA_TAIGA, BiomeIds::MEGA_TAIGA_HILLS];
	}

	protected StoneBoulderDecorator $stone_boulder_decorator;

	public function __construct(){
		parent::__construct();
		$this->stone_boulder_decorator = new StoneBoulderDecorator();
	}

	protected function initPopulators() : void{
		$this->tree_decorator->setTrees(...self::$TREES);
		$this->tall_grass_decorator->setAmount(7);
		$this->dead_bush_decorator->setAmount(0);
		$this->taiga_brown_mushroom_decorator->setAmount(3);
		$this->taiga_red_mushroom_decorator->setAmount(3);
	}

	protected function populateOnGround(ChunkManager $world, Random $random, int $chunk_x, int $chunk_z, Chunk $chunk) : void{
		$this->stone_boulder_decorator->populate($world, $random, $chunk_x, $chunk_z, $chunk);
		parent::populateOnGround($world, $random, $chunk_x, $chunk_z, $chunk);
	}
}

MegaTaigaPopulator::init();