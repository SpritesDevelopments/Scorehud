<?php

declare(strict_types=1);

namespace Spritedev\scorehud\provider\providers;

use pocketmine\player\Player;
use Spritedev\scorehud\Main;
use Spritedev\scorehud\provider\PlaceholderProvider;

class TokensAPIProvider implements PlaceholderProvider {

    /** @var Main */
    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }
    
    public function getName(): string {
        return "TokensAPI";
    }
    
    public function processPlaceholders(string $text, Player $player): string {
        // Check for TokensAPI placeholder
        if (strpos($text, "{tokens}") !== false) {
            $tokensAPI = $this->plugin->getServer()->getPluginManager()->getPlugin("TokensAPI");
            if ($tokensAPI !== null) {
                $tokens = $tokensAPI->getTokens($player);
                $text = str_replace("{tokens}", number_format($tokens), $text);
            }
        }
        
        return $text;
    }
}
