<?php

use MineBros\Main;
use MineBros\character\BaseCharacter;
use pocketmine\Player;
use pocketmine\math\Vector3;

class Thunder extends BaseCharacter {

  protected $amplify = array();
  protected $cnt = array();

  final public function getOptions(){
     return BaseCharacter::CLASS_B | BaseCharacter::TRIGR_PONLY;
  }

  final public function getDescription(){
    return <<<EOF
스킬 사용: 플레이어를 돌로 감쌉니다.
주의: 대상 근처 지형이 돌로 강제 변환될 수 있습니다.
(발동 조건: 아무 플레이어나 터치)
EOF;
  }

  final public function getName(){
    return '석화';
  }

  public function onHarmPlayer(Player $victim, Player $damager){
    $victim->teleport($vec = new Vector3((int) $victim->x, (int) $victim->y, (int) $victim->z));
    ($lv = $victim->getLevel())->setBlockIdAt(($x = $victim->x) + 1, ($y = $victim->y), ($z = $victim->z));
    $lv->setBlockIdAt($x + 1, $y + 1, $z);
    $lv->setBlockIdAt($x - 1, $y, $z);
    $lv->setBlockIdAt($x - 1, $y + 1, $z);
    $lv->setBlockIdAt($x, $y, $z + 1);
    $lv->setBlockIdAt($x, ++$y, $z + 1);
    $lv->setBlockIdAt($x, ++$y, $z);
    $lv->setBlockIdAt($x, $y - 3, $z);
    $this->startCooldown($damager);
  }
}
