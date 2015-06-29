<?php

namespace MineBros\character;

use pocketmine\Player;
use pocketmine\math\Vector3;

abstract class BaseCharacter implements Character{

  const CLASS_B   = 0b1;
  const CLASS_A   = 0b10;
  const CLASS_S   = 0b100;
  const CLASS_PLUS  = 0b1000;
  const TRIGR_TOUCH = 0b10000;
  const TRIGR_PONLY = 0b100000;
  const TRIGR_PASIV = 0b1000000;
  const TRIGR_CUSTM = 0b10000000; //Not now, should be implemented in future
  const EV_CAUSE_TASK = 15;

  public static $owner;

  public function init(){

  }

  public function getOptions(){
    return 0;
  }

  public function getName(){
    return get_called_class();
  }

  public function getDescription(){
    return '';
  }

  public function onTouchAnything(Player $who, $targetIsPlayer = false, Vector3 $pos, $targetPlayer = NULL){

  }

  public function onHarmPlayer(Player $victim, Player $damager){

  }

  public function onPassiveTick(Player $who, $currentTick){

  }

  final private function getProgressiveExecutionTask(){
    return \MineBros\Main::$pet;
  }

  final public function getCooldown(){
    $r = 0;
    if($this->getOptions() & self::CLASS_B){
      $r = 18;
      if($this->getOptions() & self::CLASS_PLUS) $r += 7;
    } else if($this->getOptions() & self::CLASS_A){
      $r = 35;
      if($this->getOptions() & self::CLASS_PLUS) $r += 10;
    } else if($this->getOptions() & self::CLASS_S){
      $r = 52;
      if($this->getOptions() & self::CLASS_PLUS) $r += 13;
    }
    return $r;
  }

  final public function startCooldown(Player $player, $force = false){
    if(self::$owner->characterLoader->nameDict[$player->getName()] !== $this->getName()){
      throw new \Exception("잘못된 플레이어의 쿨타임 처리 요청");
      return;
    }
    if($force or !isset(self::$owner->characterLoader->cooldown[$player->getName()])) self::$owner->characterLoader->cooldown[$player->getName()] = $this->getCooldown();
  }

}
