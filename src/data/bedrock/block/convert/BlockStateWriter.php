<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\data\bedrock\block\convert;

use pocketmine\block\utils\BellAttachmentType;
use pocketmine\block\utils\DripstoneThickness;
use pocketmine\block\utils\Orientation;
use pocketmine\block\utils\SlabType;
use pocketmine\block\utils\StructureType;
use pocketmine\block\utils\TurtleEggCount;
use pocketmine\block\utils\CrackedState;
use pocketmine\block\utils\VaultState;
use pocketmine\block\utils\WallConnectionType;
use pocketmine\data\bedrock\block\BlockLegacyMetadata;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateNames;
use pocketmine\data\bedrock\block\BlockStateSerializeException;
use pocketmine\data\bedrock\block\BlockStateStringValues as StringValues;
use pocketmine\math\Axis;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\utils\AssumptionFailedError;

final class BlockStateWriter{

	/**
	 * @var Tag[]
	 * @phpstan-var array<string, Tag>
	 */
	private array $states = [];

	public function __construct(
		private string $id
	){}

	public static function create(string $id) : self{
		return new self($id);
	}

	/** @return $this */
	public function writeBool(string $name, bool $value) : self{
		$this->states[$name] = new ByteTag($value ? 1 : 0);
		return $this;
	}

	/** @return $this */
	public function writeInt(string $name, int $value) : self{
		$this->states[$name] = new IntTag($value);
		return $this;
	}

	/** @return $this */
	public function writeString(string $name, string $value) : self{
		$this->states[$name] = new StringTag($value);
		return $this;
	}

	/** @return $this */
	public function writeFacingDirection(int $value) : self{
		$this->writeInt(BlockStateNames::FACING_DIRECTION, match($value){
			Facing::DOWN => 0,
			Facing::UP => 1,
			Facing::NORTH => 2,
			Facing::SOUTH => 3,
			Facing::WEST => 4,
			Facing::EAST => 5,
			default => throw new BlockStateSerializeException("Invalid Facing $value")
		});
		return $this;
	}

	/** @return $this */
	public function writeBlockFace(int $value) : self{
		$this->writeString(BlockStateNames::MC_BLOCK_FACE, match($value){
			Facing::DOWN => StringValues::MC_BLOCK_FACE_DOWN,
			Facing::UP => StringValues::MC_BLOCK_FACE_UP,
			Facing::NORTH => StringValues::MC_BLOCK_FACE_NORTH,
			Facing::SOUTH => StringValues::MC_BLOCK_FACE_SOUTH,
			Facing::WEST => StringValues::MC_BLOCK_FACE_WEST,
			Facing::EAST => StringValues::MC_BLOCK_FACE_EAST,
			default => throw new BlockStateSerializeException("Invalid Facing $value")
		});
		return $this;
	}

	/**
	 * @param int[] $faces
	 * @phpstan-param array<int, int> $faces
	 * @return $this
	 */
	public function writeFacingFlags(array $faces) : self{
		$result = 0;
		foreach($faces as $face){
			$result |= match($face){
				Facing::DOWN => BlockLegacyMetadata::MULTI_FACE_DIRECTION_FLAG_DOWN,
				Facing::UP => BlockLegacyMetadata::MULTI_FACE_DIRECTION_FLAG_UP,
				Facing::NORTH => BlockLegacyMetadata::MULTI_FACE_DIRECTION_FLAG_NORTH,
				Facing::SOUTH => BlockLegacyMetadata::MULTI_FACE_DIRECTION_FLAG_SOUTH,
				Facing::WEST => BlockLegacyMetadata::MULTI_FACE_DIRECTION_FLAG_WEST,
				Facing::EAST => BlockLegacyMetadata::MULTI_FACE_DIRECTION_FLAG_EAST,
				default => throw new AssumptionFailedError("Unhandled face $face")
			};
		}

		return $this->writeInt(BlockStateNames::MULTI_FACE_DIRECTION_BITS, $result);
	}

	/** @return $this */
	public function writeEndRodFacingDirection(int $value) : self{
		//end rods are stupid in bedrock and have everything except up/down the wrong way round
		return $this->writeFacingDirection(Facing::axis($value) !== Axis::Y ? Facing::opposite($value) : $value);
	}

	/** @return $this */
	public function writeHorizontalFacing(int $value) : self{
		if($value === Facing::UP || $value === Facing::DOWN){
			throw new BlockStateSerializeException("Y-axis facing is not allowed");
		}

		return $this->writeFacingDirection($value);
	}

	/** @return $this */
	public function writeWeirdoHorizontalFacing(int $value) : self{
		$this->writeInt(BlockStateNames::WEIRDO_DIRECTION, match($value){
			Facing::EAST => 0,
			Facing::WEST => 1,
			Facing::SOUTH => 2,
			Facing::NORTH => 3,
			default => throw new BlockStateSerializeException("Invalid horizontal facing $value")
		});
		return $this;
	}

	/** @return $this */
	public function writeLegacyHorizontalFacing(int $value) : self{
		$this->writeInt(BlockStateNames::DIRECTION, match($value){
			Facing::SOUTH => 0,
			Facing::WEST => 1,
			Facing::NORTH => 2,
			Facing::EAST => 3,
			default => throw new BlockStateSerializeException("Invalid horizontal facing $value")
		});
		return $this;
	}

	/**
	 * This is for trapdoors, because Mojang botched the conversion in 1.13
	 * @return $this
	 */
	public function write5MinusHorizontalFacing(int $value) : self{
		return $this->writeInt(BlockStateNames::DIRECTION, match($value){
			Facing::EAST => 0,
			Facing::WEST => 1,
			Facing::SOUTH => 2,
			Facing::NORTH => 3,
			default => throw new BlockStateSerializeException("Invalid horizontal facing $value")
		});
	}

	/**
	 * Used by pumpkins as of 1.20.0.23 beta
	 * @return $this
	 */
	public function writeCardinalHorizontalFacing(int $value) : self{
		return $this->writeString(BlockStateNames::MC_CARDINAL_DIRECTION, match($value){
			Facing::SOUTH => StringValues::MC_CARDINAL_DIRECTION_SOUTH,
			Facing::WEST => StringValues::MC_CARDINAL_DIRECTION_WEST,
			Facing::NORTH => StringValues::MC_CARDINAL_DIRECTION_NORTH,
			Facing::EAST => StringValues::MC_CARDINAL_DIRECTION_EAST,
			default => throw new BlockStateSerializeException("Invalid horizontal facing $value")
		});
	}

	/** @return $this */
	public function writeCoralFacing(int $value) : self{
		$this->writeInt(BlockStateNames::CORAL_DIRECTION, match($value){
			Facing::WEST => 0,
			Facing::EAST => 1,
			Facing::NORTH => 2,
			Facing::SOUTH => 3,
			default => throw new BlockStateSerializeException("Invalid horizontal facing $value")
		});
		return $this;
	}

	/** @return $this */
	public function writeFacingWithoutDown(int $value) : self{
		if($value === Facing::DOWN){
			throw new BlockStateSerializeException("Invalid facing DOWN");
		}
		$this->writeFacingDirection($value);
		return $this;
	}

	/** @return $this */
	public function writeFacingWithoutUp(int $value) : self{
		if($value === Facing::UP){
			throw new BlockStateSerializeException("Invalid facing UP");
		}
		$this->writeFacingDirection($value);
		return $this;
	}

	/** @return $this */
	public function writePillarAxis(int $axis) : self{
		$this->writeString(BlockStateNames::PILLAR_AXIS, match($axis){
			Axis::X => StringValues::PILLAR_AXIS_X,
			Axis::Y => StringValues::PILLAR_AXIS_Y,
			Axis::Z => StringValues::PILLAR_AXIS_Z,
			default => throw new BlockStateSerializeException("Invalid axis $axis")
		});
		return $this;
	}

	/** @return $this */
	public function writeSlabPosition(SlabType $slabType) : self{
		$this->writeString(BlockStateNames::MC_VERTICAL_HALF, match($slabType){
			SlabType::TOP => StringValues::MC_VERTICAL_HALF_TOP,
			SlabType::BOTTOM => StringValues::MC_VERTICAL_HALF_BOTTOM,
			default => throw new BlockStateSerializeException("Invalid slab type " . $slabType->name)
		});
		return $this;
	}

	/** @return $this */
	public function writeTorchFacing(int $facing) : self{
		//TODO: horizontal directions are flipped (MCPE bug: https://bugs.mojang.com/browse/MCPE-152036)
		$this->writeString(BlockStateNames::TORCH_FACING_DIRECTION, match($facing){
			Facing::UP => StringValues::TORCH_FACING_DIRECTION_TOP,
			Facing::SOUTH => StringValues::TORCH_FACING_DIRECTION_NORTH,
			Facing::NORTH => StringValues::TORCH_FACING_DIRECTION_SOUTH,
			Facing::EAST => StringValues::TORCH_FACING_DIRECTION_WEST,
			Facing::WEST => StringValues::TORCH_FACING_DIRECTION_EAST,
			default => throw new BlockStateSerializeException("Invalid Torch facing $facing")
		});
		return $this;
	}

	/** @return $this */
	public function writeBellAttachmentType(BellAttachmentType $attachmentType) : self{
		$this->writeString(BlockStateNames::ATTACHMENT, match($attachmentType){
			BellAttachmentType::FLOOR => StringValues::ATTACHMENT_STANDING,
			BellAttachmentType::CEILING => StringValues::ATTACHMENT_HANGING,
			BellAttachmentType::ONE_WALL => StringValues::ATTACHMENT_SIDE,
			BellAttachmentType::TWO_WALLS => StringValues::ATTACHMENT_MULTIPLE,
		});
		return $this;
	}

	/** @return $this */
	public function writeTurtleEggCount(TurtleEggCount $count) : self {
		$this->writeString(BlockStateNames::TURTLE_EGG_COUNT, match ($count) {
			TurtleEggCount::ONE_EGG => StringValues::TURTLE_EGG_COUNT_ONE_EGG,
			TurtleEggCount::TWO_EGG => StringValues::TURTLE_EGG_COUNT_TWO_EGG,
			TurtleEggCount::THREE_EGG => StringValues::TURTLE_EGG_COUNT_THREE_EGG,
			TurtleEggCount::FOUR_EGG => StringValues::TURTLE_EGG_COUNT_FOUR_EGG,
		});
		return $this;
	}

	/** @return $this */
	public function writeCrackedState(CrackedState $crackedState) : self {
		$this->writeString(BlockStateNames::CRACKED_STATE, match ($crackedState) {
			CrackedState::NO_CRACKS => StringValues::CRACKED_STATE_NO_CRACKS,
			CrackedState::CRACKED => StringValues::CRACKED_STATE_CRACKED,
			CrackedState::MAX_CRACKED => StringValues::CRACKED_STATE_MAX_CRACKED,
		});
		return $this;
	}

	/** @return $this */
	public function writeVaultState(VaultState $vaultState) : self {
		$this->writeString(BlockStateNames::VAULT_STATE, match ($vaultState) {
			VaultState::ACTIVE => StringValues::VAULT_STATE_ACTIVE,
			VaultState::EJECTING => StringValues::VAULT_STATE_EJECTING,
			VaultState::INACTIVE => StringValues::VAULT_STATE_INACTIVE,
			VaultState::UNLOCKING => StringValues::VAULT_STATE_UNLOCKING,
		});
		return $this;
	}

	/** @return $this */
	public function writePointedDripstoneThickness(DripstoneThickness $thickness) : self {
		$this->writeString(BlockStateNames::DRIPSTONE_THICKNESS, match ($thickness) {
			DripstoneThickness::MERGE => StringValues::DRIPSTONE_THICKNESS_MERGE,
			DripstoneThickness::TIP => StringValues::DRIPSTONE_THICKNESS_TIP,
			DripstoneThickness::FRUSTUM => StringValues::DRIPSTONE_THICKNESS_FRUSTUM,
			DripstoneThickness::MIDDLE => StringValues::DRIPSTONE_THICKNESS_MIDDLE,
			DripstoneThickness::BASE => StringValues::DRIPSTONE_THICKNESS_BASE,
		});
		return $this;
	}

	/** @return $this */
	public function writeStructureBlockType(StructureType $type) : self {
		$this->writeString(BlockStateNames::STRUCTURE_BLOCK_TYPE, match ($type) {
			StructureType::CORNER => StringValues::STRUCTURE_BLOCK_TYPE_CORNER,
			StructureType::DATA => StringValues::STRUCTURE_BLOCK_TYPE_DATA,
			StructureType::EXPORT => StringValues::STRUCTURE_BLOCK_TYPE_EXPORT,
			StructureType::INVALID => StringValues::STRUCTURE_BLOCK_TYPE_INVALID,
			StructureType::LOAD => StringValues::STRUCTURE_BLOCK_TYPE_LOAD,
			StructureType::SAVE => StringValues::STRUCTURE_BLOCK_TYPE_SAVE,
		});
		return $this;
	}

	/** @return $this */
	public function writeOrientation(Orientation $orientation) : self{
		$this->writeString(BlockStateNames::ORIENTATION, match ($orientation) {
			Orientation::DOWN_EAST => StringValues::ORIENTATION_DOWN_EAST,
			Orientation::DOWN_NORTH => StringValues::ORIENTATION_DOWN_NORTH,
			Orientation::DOWN_SOUTH => StringValues::ORIENTATION_DOWN_SOUTH,
			Orientation::DOWN_WEST => StringValues::ORIENTATION_DOWN_WEST,
			Orientation::EAST_UP => StringValues::ORIENTATION_EAST_UP,
			Orientation::NORTH_UP => StringValues::ORIENTATION_NORTH_UP,
			Orientation::SOUTH_UP => StringValues::ORIENTATION_SOUTH_UP,
			Orientation::UP_EAST => StringValues::ORIENTATION_UP_EAST,
			Orientation::UP_NORTH => StringValues::ORIENTATION_UP_NORTH,
			Orientation::UP_SOUTH => StringValues::ORIENTATION_UP_SOUTH,
			Orientation::UP_WEST => StringValues::ORIENTATION_UP_WEST,
			Orientation::WEST_UP => StringValues::ORIENTATION_WEST_UP,
		});
		return $this;
	}

	/** @return $this */
	public function writeWallConnectionType(string $name, ?WallConnectionType $wallConnectionType) : self{
		$this->writeString($name, match($wallConnectionType){
			null => StringValues::WALL_CONNECTION_TYPE_EAST_NONE,
			WallConnectionType::SHORT => StringValues::WALL_CONNECTION_TYPE_EAST_SHORT,
			WallConnectionType::TALL => StringValues::WALL_CONNECTION_TYPE_EAST_TALL,
		});
		return $this;
	}

	public function getBlockStateData() : BlockStateData{
		return BlockStateData::current($this->id, $this->states);
	}
}
