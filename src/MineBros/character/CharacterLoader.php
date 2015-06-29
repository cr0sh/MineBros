<?php

namespace MineBros\character;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;
use MineBros\Main;

class CharacterLoader implements Listener {

  public $nameDict = array();
  public $passiveTickSubscribers = array();

  private $characters = array();
  private $owner;
  private $cooldown = array();

  public function __construct(Main $owner){
    $this->owner = $owner;
  }

  public function chooseRandomCharacter(Player $forWhom, $notify = false){
    $keys = array_keys($this->characters);
    $character = $this->characters[$keys[mt_rand(0, count($this->characters)-1)]];
    $this->nameDict[$forWhom->getName()] = $character->getName();
    if($notify) $forWhom->sendMessage(\pocketmine\utils\TextFormat::YELLOW.'[MineBros] '.\pocketmine\utils\TextFormat::WHITE.'능력이 설정되었습니다. /mi help로 확인해보세요.');
    return $character;
  }

  public function chooseCharacter(Player $forWhom, $name){
    if(!isset($this->characters[$name])) return false;
    $this->nameDict[$forWhom->getName()] = $this->characters[$name]->getName();
  }

  public function reset(){
    $this->nameDict = array();
  }

  public function registerCharacter(Character $character){
    if(isset($this->characters[$character->getName()])){
      $this->owner->getLogger()->warning("[MineBros] Oops: 스킬 이름이 중복됩니다");
      return false;
    }
    $this->characters[$character->getName()] = $character;
    if($character->getOptions() & BaseCharacter::TRIGR_PASIV) $this->passiveTickSubscribers[] = $character->getName();
    if($character instanceof Listener) $this->owner->getServer()->getPluginManager()->registerEvents($character, $this->owner);
    $character->init();
  }

  public function loadFromDirectory($path){
    $this->owner->getLogger()->info($path . ' 폴더로부터 스킬을 등록합니다');
    $count = 0;
    $this->owner->getServer()->getLoader()->addPath($path);
    foreach(scandir($path) as $p){
      if(substr($p, -4, 4) === '.php' and $p !== 'BaseCharacter.php' and $p !== 'CharacterLoader.php' and $p !== 'Character.php'){
        if(class_exists($classname = substr($p, 0, -4))){
          $obj = new $classname();
          $this->owner->getLogger()->notice("스킬을 등록합니다: ".$classname);
          $this->registerCharacter($obj);
          $count++;
        }
      }
    }
    if($count === 0) $this->owner->getLogger()->notice($path.' 폴더가 비어 아무것도 등록하지 않습니다');
  }

  public function onBlockTouch(PlayerInteractEvent $ev){
    if(isset($this->cooldown[$ev->getPlayer()->getName()])) {
      $ev->getPlayer()->sendMessage(Main::HEAD_MBROS.'아직 스킬을 사용할 수 없습니다. 남은 재사용 대기시간: '.$this->cooldown[$ev->getPlayer()->getName()].'초');
      return;
    }
    if($ev->getPlayer()->getInventory()->getItemInHand()->getId() !== 265) return; //Iron ingot
    if(!isset($this->nameDict[$ev->getPlayer()->getName()])) return;
    if($this->nameDict[$ev->getPlayer()->getName()]->getOptions() & BaseCharacter::TRIGR_TOUCH){
      $ev->setCancelled();
      $this->nameDict[$ev->getEntity()->getName()]->onTouchAnything($ev->getPlayer(), false, $ev->getTouchVector());
    }
  }

  public function onPlayerTouch(EntityDamageByEntityEvent $ev){
    if(isset($this->cooldown[$ev->getPlayer()->getName()])) {
      $ev->getPlayer()->sendMessage(Main::HEAD_MBROS.'아직 스킬을 사용할 수 없습니다. 남은 재사용 대기시간: '.$this->cooldown[$ev->getPlayer()->getName()].'초');
      return;
    }
    if(!($ev->getEntity() instanceof Player and $ev->getDamager() instanceof Player)
      or $ev->getPlayer()->getInventory()->getItemInHand()->getId() !== 265
      or !isset($this->nameDict[$ev->getEntity()->getName()])) return;
    if($this->nameDict[$ev->getEntity()->getName()]->getOptions() & BaseCharacter::TRIGR_TOUCH){
      $ev->setCancelled();
      $entity = $ev->getEntity();
      $this->nameDict[$ev->getEntity()->getName()]->onTouchAnything($ev->getEntity(), true, new Vector3($entity->x, $entity->y, $entity->z), $ev->getEntity());
    }
    if($this->nameDict[$ev->getEntity()->getName()]->getOptions() & BaseCharacter::TRIGR_PONLY){
      $ev->setCancelled();
      $this->nameDict[$ev->getEntity()->getName()]->onHarmPlayer($ev->getEntity(), $ev->getDamager(), $ev->getCause());
    }
  }

  public function onPassiveTick($currentTick){
    foreach($this->passiveTickSubscribers as $s){
      foreach(array_keys($this->nameDict, $s) as $a){
        $player = $this->owner->getServer()->getPlayerExact($a);
        if($player === NULL) continue;
          else $this->characters[$s]->onPassiveTick($player, $currentTick);
      }
    }
  }

}
