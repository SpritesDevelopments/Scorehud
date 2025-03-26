<?php

declare(strict_types=1);

namespace Spritedev\scorehud\scoreboard;

use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use Spritedev\scorehud\Main;

class ScoreboardManager {

    /** @var Main */
    private Main $plugin;
    
    /** @var array */
    private array $scoreboards = [];
    
    /** @var array */
    private array $lines = [];
    
    /** @var array */
    private array $lastScores = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->loadLines();
    }
    
    /**
     * Load scoreboard lines from config
     */
    private function loadLines(): void {
        // Force reload lines from config
        $this->plugin->reloadConfig();
        $this->lines = $this->plugin->getConfig()->get("lines", []);
        $this->plugin->getLogger()->debug("Loaded " . count($this->lines) . " scoreboard lines from config");
    }

    /**
     * Create a scoreboard for a player
     * 
     * @param Player $player
     * @return void
     */
    public function createScoreboard(Player $player): void {
        $title = $this->plugin->getConfig()->get("title", "§e§lScoreHud");
        
        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = "sidebar";
        $pk->objectiveName = "objective";
        $pk->displayName = $title;
        $pk->criteriaName = "dummy";
        $pk->sortOrder = 0;
        
        $player->getNetworkSession()->sendDataPacket($pk);
        
        $this->scoreboards[$player->getName()] = true;
        $this->lastScores[$player->getName()] = [];
        $this->updateScoreboard($player, true);
    }
    
    /**
     * Update a player's scoreboard
     * 
     * @param Player $player
     * @param bool $force Force update even if content hasn't changed
     * @return void
     */
    public function updateScoreboard(Player $player, bool $force = false): void {
        if (!isset($this->scoreboards[$player->getName()])) return;
        
        $entries = [];
        $scoreTexts = [];
        $lineCount = count($this->lines);
        
        foreach ($this->lines as $index => $line) {
            $lineText = $this->processTags($line, $player);
            $scoreTexts[$lineCount - $index] = $lineText;
            
            $entry = new ScorePacketEntry();
            $entry->objectiveName = "objective";
            $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
            $entry->customName = $lineText;
            $entry->score = $lineCount - $index;
            $entry->scoreboardId = $lineCount - $index;
            $entries[] = $entry;
        }
        
        // Check if scores have changed before sending packet
        if (!$force && isset($this->lastScores[$player->getName()]) && $this->lastScores[$player->getName()] === $scoreTexts) {
            return; // No changes, don't send packet
        }
        
        // Remember last sent scores to avoid unnecessary updates
        $this->lastScores[$player->getName()] = $scoreTexts;
        
        // First remove old scores to prevent duplicates
        $removePacket = new SetScorePacket();
        $removePacket->type = SetScorePacket::TYPE_REMOVE;
        $removePacket->entries = $entries;
        $player->getNetworkSession()->sendDataPacket($removePacket);
        
        // Then set new scores
        $pk = new SetScorePacket();
        $pk->type = SetScorePacket::TYPE_CHANGE;
        $pk->entries = $entries;
        
        $player->getNetworkSession()->sendDataPacket($pk);
        $this->plugin->getLogger()->debug("Updated scoreboard for " . $player->getName());
    }
    
    /**
     * Remove a player's scoreboard
     * 
     * @param Player $player
     * @return void
     */
    public function removeScoreboard(Player $player): void {
        if (!isset($this->scoreboards[$player->getName()])) return;
        
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = "objective";
        
        $player->getNetworkSession()->sendDataPacket($pk);
        unset($this->scoreboards[$player->getName()]);
        unset($this->lastScores[$player->getName()]);
    }
    
    /**
     * Process tags/placeholders in text
     * 
     * @param string $text
     * @param Player $player
     * @return string
     */
    private function processTags(string $text, Player $player): string {
        return $this->plugin->getProviderManager()->replacePlaceholders($text, $player);
    }
    
    /**
     * Update scoreboards for all players
     * 
     * @return void
     */
    public function updateAllScoreboards(): void {
        // Reload lines in case config was changed
        $this->loadLines();
        
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            if (isset($this->scoreboards[$player->getName()])) {
                $this->updateScoreboard($player);
            }
        }
    }
    
    /**
     * Check if a player has a scoreboard
     * 
     * @param Player $player
     * @return bool
     */
    public function hasScoreboard(Player $player): bool {
        return isset($this->scoreboards[$player->getName()]);
    }
}
