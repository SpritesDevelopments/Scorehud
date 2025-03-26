<?php

declare(strict_types=1);

namespace Spritedev\scorehud\provider\providers;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use Spritedev\scorehud\Main;

class BedrockEconomyBalanceListener implements Listener {
    
    /** @var Main */
    private Main $plugin;
    
    /** @var BedrockEconomyProvider */
    private BedrockEconomyProvider $provider;
    
    /** @var array */
    private array $lastCheckTime = [];
    
    public function __construct(Main $plugin, BedrockEconomyProvider $provider) {
        $this->plugin = $plugin;
        $this->provider = $provider;
    }
    
    /**
     * @param PlayerJoinEvent $event
     * @priority MONITOR
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        try {
            $economyAPI = BedrockEconomyAPI::getInstance();
            $economyAPI->getPlayerBalance(
                $player->getName(),
                function(int $balance) use ($player): void {
                    $this->provider->updateCachedBalance($player->getName(), $balance);
                    
                    // Force scoreboard update after balance is loaded
                    $this->plugin->getScoreboardManager()->updateScoreboard($player, true);
                }
            );
        } catch (\Throwable $e) {
            $this->plugin->getLogger()->error("Failed to fetch balance on join for " . $player->getName() . ": " . $e->getMessage());
        }
    }
    
    /**
     * @param DataPacketReceiveEvent $event
     * @priority MONITOR
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
        $player = $event->getOrigin()->getPlayer();
        if ($player === null) return;
        
        $packet = $event->getPacket();
        if ($packet instanceof InventoryTransactionPacket) {
            // This packet is sent for various transactions, so let's use it as a trigger
            // to refresh economy data periodically (max once every 5 seconds per player)
            
            $now = time();
            if (!isset($this->lastCheckTime[$player->getName()]) || $now - $this->lastCheckTime[$player->getName()] >= 5) {
                $this->lastCheckTime[$player->getName()] = $now;
                
                // Schedule a delayed task to update balance with a slight delay
                // to avoid doing this too frequently
                $this->plugin->getScheduler()->scheduleDelayedTask(new class($player, $this->provider, $this->plugin) extends \pocketmine\scheduler\Task {
                    private $player;
                    private $provider;
                    private $plugin;
                    
                    public function __construct($player, $provider, $plugin) {
                        $this->player = $player;
                        $this->provider = $provider;
                        $this->plugin = $plugin;
                    }
                    
                    public function onRun(): void {
                        if ($this->player->isOnline()) {
                            try {
                                $economyAPI = BedrockEconomyAPI::getInstance();
                                $economyAPI->getPlayerBalance(
                                    $this->player->getName(),
                                    function(int $balance): void {
                                        $this->provider->updateCachedBalance($this->player->getName(), $balance);
                                        $this->plugin->getScoreboardManager()->updateScoreboard($this->player, true);
                                    }
                                );
                            } catch (\Throwable $e) {
                                // Silently fail
                            }
                        }
                    }
                }, 20);
            }
        }
    }
}
