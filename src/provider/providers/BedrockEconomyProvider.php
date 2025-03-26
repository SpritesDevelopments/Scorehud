<?php

declare(strict_types=1);

namespace Spritedev\scorehud\provider\providers;

use pocketmine\player\Player;
use Spritedev\scorehud\Main;
use Spritedev\scorehud\provider\PlaceholderProvider;
use cooldogedev\BedrockEconomy\BedrockEconomy;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\api\version\BEAPIVersion;

class BedrockEconomyProvider implements PlaceholderProvider {

    /** @var Main */
    private Main $plugin;
    
    /** @var array */
    private array $cachedBalances = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        
        // Register for balance update events
        $this->registerEconomyEvents();
        
        // Pre-load balances for online players
        foreach ($plugin->getServer()->getOnlinePlayers() as $player) {
            $this->fetchPlayerBalance($player);
        }
    }
    
    public function getName(): string {
        return "BedrockEconomy";
    }
    
    /**
     * Process placeholders related to BedrockEconomy
     * 
     * @param string $text
     * @param Player $player
     * @return string
     */
    public function processPlaceholders(string $text, Player $player): string {
        // Check for BedrockEconomy placeholders
        if (strpos($text, "{money}") !== false) {
            // Try to get from cache first
            if (isset($this->cachedBalances[$player->getName()])) {
                $balance = $this->cachedBalances[$player->getName()];
                $text = str_replace("{money}", number_format($balance), $text);
            } else {
                // If not in cache, fetch it now (will be available in next update)
                $this->fetchPlayerBalance($player);
                $text = str_replace("{money}", "Loading...", $text);
            }
        }
        
        return $text;
    }
    
    /**
     * Fetch player balance and cache it
     * 
     * @param Player $player
     * @return void
     */
    private function fetchPlayerBalance(Player $player): void {
        try {
            // Use direct database query for faster response (synchronous)
            $database = $this->getBedrockEconomyDatabase();
            if ($database !== null) {
                $balance = $database->getPlayerBalance($player->getName());
                if (is_int($balance)) {
                    $this->cachedBalances[$player->getName()] = $balance;
                    $this->plugin->getLogger()->debug("Updated balance for " . $player->getName() . ": " . $balance);
                    return;
                }
            }
            
            // Fallback to async API if direct access fails
            $economyAPI = BedrockEconomyAPI::getInstance();
            $economyAPI->getPlayerBalance(
                $player->getName(),
                function(int $balance) use ($player): void {
                    $this->cachedBalances[$player->getName()] = $balance;
                    $this->plugin->getLogger()->debug("Updated balance via API for " . $player->getName() . ": " . $balance);
                }
            );
        } catch (\Throwable $e) {
            $this->plugin->getLogger()->error("Failed to fetch balance for " . $player->getName() . ": " . $e->getMessage());
            $this->cachedBalances[$player->getName()] = 0;
        }
    }
    
    /**
     * Try to get direct database instance from BedrockEconomy
     * 
     * @return mixed|null
     */
    private function getBedrockEconomyDatabase() {
        try {
            $bedrockEconomy = BedrockEconomy::getInstance();
            $reflection = new \ReflectionClass($bedrockEconomy);
            
            // Try to access database property
            if ($reflection->hasProperty("database")) {
                $databaseProperty = $reflection->getProperty("database");
                $databaseProperty->setAccessible(true);
                return $databaseProperty->getValue($bedrockEconomy);
            }
            
            // Alternative method for newer versions
            if (method_exists($bedrockEconomy, "getDatabase")) {
                return $bedrockEconomy->getDatabase();
            }
        } catch (\Throwable $e) {
            $this->plugin->getLogger()->debug("Could not access BedrockEconomy database directly: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Register economy events to keep balances up to date
     * 
     * @return void
     */
    private function registerEconomyEvents(): void {
        $plugin = $this->plugin->getServer()->getPluginManager()->getPlugin("BedrockEconomy");
        if ($plugin === null) return;
        
        // Create a balance listener that will update our cached balances
        $balanceListener = new BedrockEconomyBalanceListener($this->plugin, $this);
        $this->plugin->getServer()->getPluginManager()->registerEvents($balanceListener, $this->plugin);
    }
    
    /**
     * Update a player's cached balance
     * 
     * @param string $playerName
     * @param int $balance
     * @return void
     */
    public function updateCachedBalance(string $playerName, int $balance): void {
        $this->cachedBalances[$playerName] = $balance;
        $this->plugin->getLogger()->debug("Cached balance updated for $playerName: $balance");
    }
    
    /**
     * Get a player's cached balance
     * 
     * @param string $playerName
     * @return int|null
     */
    public function getCachedBalance(string $playerName): ?int {
        return $this->cachedBalances[$playerName] ?? null;
    }
}
