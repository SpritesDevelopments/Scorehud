<?php

declare(strict_types=1);

namespace Spritedev\scorehud\provider\providers;

use pocketmine\player\Player;
use Spritedev\scorehud\Main;
use Spritedev\scorehud\provider\PlaceholderProvider;

class DefaultProvider implements PlaceholderProvider {

    /** @var Main */
    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }
    
    public function getName(): string {
        return "Default";
    }
    
    public function processPlaceholders(string $text, Player $player): string {
        // Player data
        $text = str_replace("{name}", $player->getName(), $text);
        $text = str_replace("{display_name}", $player->getDisplayName(), $text);
        $text = str_replace("{health}", (string)round($player->getHealth(), 1), $text);
        $text = str_replace("{max_health}", (string)$player->getMaxHealth(), $text);
        
        // Position data
        $pos = $player->getPosition();
        $text = str_replace("{x}", (string)round($pos->x, 1), $text);
        $text = str_replace("{y}", (string)round($pos->y, 1), $text);
        $text = str_replace("{z}", (string)round($pos->z, 1), $text);
        $text = str_replace("{world}", $player->getWorld()->getFolderName(), $text);
        
        // Server data
        $text = str_replace("{online}", (string)count($this->plugin->getServer()->getOnlinePlayers()), $text);
        $text = str_replace("{max_online}", (string)$this->plugin->getServer()->getMaxPlayers(), $text);
        $text = str_replace("{tps}", (string)$this->plugin->getServer()->getTicksPerSecond(), $text);
        
        return $text;
    }
}
