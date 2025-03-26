<?php

declare(strict_types=1);

namespace Spritedev\scorehud\task;

use pocketmine\scheduler\Task;
use Spritedev\scorehud\Main;

class ScoreUpdateTask extends Task {

    /** @var Main */
    private Main $plugin;
    
    /** @var int */
    private int $ticks = 0;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }
    
    public function onRun(): void {
        try {
            $this->ticks++;
            
            // Always update scoreboards
            $this->plugin->getScoreboardManager()->updateAllScoreboards();
            
            // Every 300 ticks (15 seconds), refresh economy data for all players
            if ($this->ticks >= 300) {
                $this->ticks = 0;
                $this->refreshEconomyData();
            }
        } catch (\Throwable $e) {
            $this->plugin->getLogger()->error("Error in update task: " . $e->getMessage());
        }
    }
    
    private function refreshEconomyData(): void {
        $providerManager = $this->plugin->getProviderManager();
        $providers = $providerManager->getProviders();
        
        if (isset($providers["bedrockeconomy"]) && $providers["bedrockeconomy"] instanceof \Spritedev\scorehud\provider\providers\BedrockEconomyProvider) {
            foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                $economyAPI = \cooldogedev\BedrockEconomy\api\BedrockEconomyAPI::getInstance();
                $economyAPI->getPlayerBalance(
                    $player->getName(),
                    function(int $balance) use ($player, $providers): void {
                        $providers["bedrockeconomy"]->updateCachedBalance($player->getName(), $balance);
                    }
                );
            }
        }
    }
}
