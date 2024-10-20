<?php

namespace pocketmine\world\generator\overworld\decorator\types;

use pocketmine\block\DoublePlant;

final class DoublePlantDecoration{

	public function __construct(
		readonly public DoublePlant $block,
		readonly public int $weight
	){}
}