<?php
/**
 * @author Hadik <hadikcze@gmail.com>
 */
class BotHandler {	

	/** @var BotModel */
	private $botModel;
	
	/** @var BotCounter */
	private $botCounter;	
	
	/** @var array */
	private $bots = array();

	/** @var Timer */
	private $botCreatorTimer;
	
	/**
	 * Handler of bots, which create new bots, checking is bots are alive, and update every bot
	 * @param BotModel $botModel
	 */
	public function __construct(BotModel $botModel, BotCounter $botCounter) {
		$this->botModel = $botModel;
		$this->botCounter = $botCounter;
		$this->botCreatorTimer = new Timer(Settings::getInstance()->value['minTimeBetweenNewBot'], Settings::getInstance()->value['maxTimeBetweenNewBot'], false, Timer::START_ON_END);
		$this->botModel->removeAllBotsFromDb(); // Prevent from crash, etc.
		$this->botModel->removeAllBotsActionsFromDb(); // Prevent from crash, etc.
	}
	
	/**
	 * Update metod, which is call every run of cycle
	 */
	public function update(){
		$this->botCreator();
		foreach($this->bots as $botNumber => $bot){
			if($bot->isAlive()){
				$bot->update();
                $bot->terminate();

			} else {
				$this->removeBot($bot, $botNumber);
			}
		}		
	}
	
	/**
	 * Create bot if can
	 */
	private function botCreator(){
		$countOfBots = count($this->bots);
		if($this->botCounter->canJoinNewBot($countOfBots)){
			$this->botCreatorTimer->update();
		}
		if($this->botCreatorTimer->isEnd() && $this->botCounter->canJoinNewBot($countOfBots)){
			if($bot = $this->getBot()){
				$this->bots[] = $bot;
				$this->botCreatorTimer->reset();
			}
		}
	}	
	
	/**
	 * Get new random bot
	 * @return \Bot
	 */
	private function getBot(){
		$botData = $this->botModel->getBot($this->getBotsId());
		if($botData){
			return new Bot($this->botModel, $botData);
		}
		return null;
	}

	/**
	 * Get ids of all bots in array
	 * @return array
	 */
	private function getBotsId(){
		$ids = array(0);
		foreach($this->bots as $bot){
			$ids[] = $bot->getId();
		}
		return $ids;
	}

	/**
	 * Remove bot from main array and call disconnect
	 * @param int $bot
	 * @param int $botNumber
	 */
	private function removeBot($bot, $botNumber){
		$bot->disconnect();
		unset($this->bots[$botNumber]);		
	}
		
}
