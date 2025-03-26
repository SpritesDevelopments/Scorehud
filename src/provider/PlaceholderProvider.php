<?php

declare(strict_types=1);

namespace Spritedev\scorehud\provider;

use pocketmine\player\Player;

interface PlaceholderProvider {

    /**
     * Get the provider name
     * 
     * @return string
     */
    public function getName(): string;
    
    /**
     * Process placeholders in text
     * 
     * @param string $text
     * @param Player $player
     * @return string
     */
    public function processPlaceholders(string $text, Player $player): string;
}
