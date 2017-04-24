<?php
namespace MagicalPE;

use pocketmine\Player;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

use pocketmine\event\player\PlayerMoveEvent;

use pocketmine\event\player\PlayerToggleSneakEvent;

use pocketmine\level\Level;
use pocketmine\block\Block;
use pocketmine\math\Vector3;

use pocketmine\level\particle;
use pocketmine\event;


const MP_MAX = 10;

class MagicalPE extends PluginBase implements Listener{
	public $dat = [];
	
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		return;
	}

	public function onSneak(event\player\PlayerToggleSneakEvent $event){
		$player=$event->getPlayer();
		$uid = $player->getId();

		//$sneak = $event->isSneaking();
		if(isset($this->dat[$uid]) == false){
			$this->dat[$uid] = new MagicData();
		}
		$pldat = $this->dat[$uid];

		$dtime = time() - $pldat->lastTime;
		
		if( $dtime > 1){
			$pldat->sneak_n = 0;
		}
		$pldat->healMP($dtime/5.0); //時間でMP回復

		$pldat->sneak_n += 1;
		$pldat->lastTime = time();

		if($pldat->sneak_n > 1){ // しゃがみ２回で発動
			$pldat->sneak_n = 0;
			$item = $player->getInventory()->getItemInHand();
			$itemid = $item->getId(); //手に持ってるものによって魔法が変わる
			//$this->getLogger()->info( "handitem: ". $itemid );
			switch($itemid){
				case 280: //stick ヒール
				    $usemp = 4;
					$point = 10;

					if($this->checkMP($player, $pldat, $usemp) == false) return;

					$level = $player->getLevel();
					$level->addParticle( new particle\MobSpawnParticle($player->getPosition(),1,1) );
					$healevent = new event\entity\EntityRegainHealthEvent($player, $point, event\entity\EntityRegainHealthEvent::CAUSE_MAGIC );
					$player->heal($point, $healevent);

					$message = "ヒールを唱えた!HP". $point ."回復."; 
					break;

				default:
					$player->sendMessage("MP[". floor($pldat->mp) ."/". MP_MAX ."]");
					return;
			}
			$message = $item->getName(). "をつかって" . $message . " MP[". floor($pldat->mp) ."/". MP_MAX ."]";


			$player->sendMessage($message);
		}

		return;
	}

	public function checkMP($pl, $pldat, $usemp){
		if($pldat->mp >= $usemp){
			$pldat->mp -= $usemp;
			return true;
		}
		$pl->sendMessage("MPが足りない! MP[". floor($pldat->mp) ."/". MP_MAX ."]");
		return false;
	}

	/*
	// 左，右などの移動コマンド入力で魔法を唱える？案
	public function PlayerMove(event\player\PlayerMoveEvent $event){
		$player=$event->getPlayer();
		$uid = $player->getId();
		$item = $player->getInventory()->getItemInHand();

		$v = $player->getMotion();
		$this->getLogger()->info( "move ". $v->x ."/". $v->y ."/". $v->z );

		$player->getDirectionVector();
		
		return;
	}*/
}

class MagicData{
	public $mp = MP_MAX;
	public $lastTime = 0;
	public $sneak_n = 0;
	public function healMP($n){
		if($n < 0) return;

		$this->mp += $n;
		if($this->mp > MP_MAX) $this->mp = MP_MAX;
		return;
	}
}

