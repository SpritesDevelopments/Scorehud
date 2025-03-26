<?php

declare(strict_types=1);

namespace Spritedev\scorehud\provider;

use pocketmine\player\Player;
use Spritedev\scorehud\Main;
use Spritedev\scorehud\provider\providers\BedrockEconomyProvider;
use Spritedev\scorehud\provider\providers\TokensAPIProvider;
use Spritedev\scorehud\provider\providers\DefaultProvider;

class ProviderManager {

    /** @var Main */
    private Main $plugin;
    
    /** @var array */
    private array $providers = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }
    
    /**
     * Register default providers
     * 
     * @return void
     */
    public function registerDefaultProviders(): void {
        // Register default providers
        $this->registerProvider(new DefaultProvider($this->plugin));
        
        try {
            // Check for BedrockEconomy
            if ($this->plugin->getServer()->getPluginManager()->getPlugin("BedrockEconomy") !== null) {
                $this->registerProvider(new BedrockEconomyProvider($this->plugin));
                $this->plugin->getLogger()->info("§aBedrockEconomy provider registered successfully!");
            }
        } catch (\Throwable $e) {
            $this->plugin->getLogger()->error("Failed to register BedrockEconomy provider: " . $e->getMessage());
        }
        
        try {
            // Check for TokensAPI
            if ($this->plugin->getServer()->getPluginManager()->getPlugin("TokensAPI") !== null) {
                $this->registerProvider(new TokensAPIProvider($this->plugin));
                $this->plugin->getLogger()->info("§aTokensAPI provider registered successfully!");
            }
        } catch (\Throwable $e) {
            $this->plugin->getLogger()->error("Failed to register TokensAPI provider: " . $e->getMessage());
        }
    }
    
    /**
     * Register a provider
     * 
     * @param PlaceholderProvider $provider
     * @return void
     */
    public function registerProvider(PlaceholderProvider $provider): void {
        $this->providers[strtolower($provider->getName())] = $provider;
    }
    
    /**
     * Replace placeholders in text
     * 
     * @param string $text
     * @param Player $player
     * @return string
     */
    public function replacePlaceholders(string $text, Player $player): string {
        foreach ($this->providers as $provider) {
            try {
                $text = $provider->processPlaceholders($text, $player);
            } catch (\Throwable $e) {
                $this->plugin->getLogger()->error("Error in provider " . $provider->getName() . ": " . $e->getMessage());
            }
        }
        
        return $text;
    }
    
    /**
     * Get all registered providers
     * 
     * @return array
     */
    public function getProviders(): array {
        return $this->providers;
    }
}
