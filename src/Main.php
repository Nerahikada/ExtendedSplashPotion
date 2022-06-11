<?php

declare(strict_types=1);

namespace Nerahikada\ExtendedSplashPotion;

use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onProjectileLaunch(ProjectileLaunchEvent $event) : void{
		$entity = $event->getEntity();
		if($entity instanceof \pocketmine\entity\projectile\SplashPotion){
			$entity->flagForDespawn();

			$splashPotion = new SplashPotion(
				$entity->getLocation(),
				$entity->getOwningEntity(),
				$entity->getPotionType(),
				$this->getConfig()->get("effect-range"),
				$this->getConfig()->get("distance-attenuation")
			);
			$splashPotion->setMotion($entity->getMotion());
			$splashPotion->spawnToAll();
		}
	}
}
