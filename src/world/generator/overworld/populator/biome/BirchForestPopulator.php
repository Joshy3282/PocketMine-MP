<?php

namespace pocketmine\world\generator\overworld\populator\biome;

use pocketmine\world\generator\object\tree\BirchTree;
use pocketmine\world\generator\overworld\biome\BiomeIds;
use pocketmine\world\generator\overworld\decorator\types\TreeDecoration;

class BirchForestPopulator extends ForestPopulator{

	private const BIOMES = [BiomeIds::BIRCH_FOREST, BiomeIds::BIRCH_FOREST_HILLS];

	/** @var TreeDecoration[] */
	protected static array $TREES;

	protected static function initTrees() : void{
		self::$TREES = [
			new TreeDecoration(BirchTree::class, 1)
		];
	}

	protected function initPopulators() : void{
		parent::initPopulators();
		$this->tree_decorator->setTrees(...self::$TREES);
	}

	public function getBiomes() : ?array{
		return self::BIOMES;
	}
}

BirchForestPopulator::init();
