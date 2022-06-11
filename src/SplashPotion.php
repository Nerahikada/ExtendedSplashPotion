<?php

declare(strict_types=1);

namespace Nerahikada\ExtendedSplashPotion;

use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\color\Color;
use pocketmine\entity\effect\InstantEffect;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\PotionType;
use pocketmine\world\particle\PotionSplashParticle;
use pocketmine\world\sound\PotionSplashSound;

class SplashPotion extends \pocketmine\entity\projectile\SplashPotion{

	public function __construct(
		Location $location,
		?Entity $shootingEntity,
		PotionType $potionType,
		private float $range = 4.0,
		private bool $attenuation = true
	){
		parent::__construct($location, $shootingEntity, $potionType);
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

	/**
	 * @see SplashPotion::onHit()
	 */
	protected function onHit(ProjectileHitEvent $event) : void{
		if($hasEffects = count($effects = $this->getPotionEffects()) > 0){
			$colors = [];
			foreach($effects as $effect){
				$level = $effect->getEffectLevel();
				for($i = 0; $i < $level; ++$i){
					$colors[] = $effect->getColor();
				}
			}
			$particle = new PotionSplashParticle(Color::mix(...$colors));
		}else{
			$particle = new PotionSplashParticle(PotionSplashParticle::DEFAULT_COLOR());
		}

		$this->getWorld()->addParticle($this->location, $particle);
		$this->broadcastSound(new PotionSplashSound());

		if($hasEffects){
			if(!$this->willLinger()){
				$bb = $this->boundingBox->expandedCopy($this->range + 0.125, $this->range / 2 + 0.125, $this->range + 0.125);
				foreach($this->getWorld()->getNearbyEntities($bb, $this) as $entity){
					if(!($entity instanceof Living && $entity->isAlive())) continue;

					$distanceSquared = $entity->getEyePos()->distanceSquared($this->location);
					if($distanceSquared > $this->range ** 2) continue;

					$distanceMultiplier = 1.0;
					if($this->attenuation){
						if(!($event instanceof ProjectileHitEntityEvent && $entity === $event->getEntityHit())){
							$distanceMultiplier = 1 - (sqrt($distanceSquared) / $this->range);
						}
					}

					foreach($this->getPotionEffects() as $effect){
						if(!$effect->getType() instanceof InstantEffect){
							$newDuration = (int) round($effect->getDuration() * 0.75 * $distanceMultiplier);
							if($newDuration < 20) continue;
							$effect->setDuration($newDuration);
							$entity->getEffects()->add($effect);
						}else{
							$effect->getType()->applyEffect($entity, $effect, $distanceMultiplier, $this);
						}
					}
				}
			}
		}elseif($event instanceof ProjectileHitBlockEvent && $this->getPotionType()->equals(PotionType::WATER())){
			$blockIn = $event->getBlockHit()->getSide($event->getRayTraceResult()->getHitFace());
			if($blockIn->getId() === BlockLegacyIds::FIRE){
				$this->getWorld()->setBlock($blockIn->getPosition(), VanillaBlocks::AIR());
			}
			foreach($blockIn->getHorizontalSides() as $horizontalSide){
				if($horizontalSide->getId() === BlockLegacyIds::FIRE){
					$this->getWorld()->setBlock($horizontalSide->getPosition(), VanillaBlocks::AIR());
				}
			}
		}
	}
}
