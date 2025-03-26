<?php

declare(strict_types=1);

namespace Spritedev\scorehud;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use Spritedev\scorehud\scoreboard\ScoreboardManager;
use Spritedev\scorehud\task\ScoreUpdateTask;
use Spritedev\scorehud\provider\ProviderManager;

class Main extends PluginBase implements Listener {
    use SingletonTrait;
    
    /** @var ScoreboardManager */
    private ScoreboardManager $scoreboardManager;
    
    /** @var ProviderManager */
    private ProviderManager $providerManager;
    
    public function onLoad(): void {
        self::setInstance($this);
    }
    
    public function onEnable(): void {
        // Make sure the config is created
        $this->saveDefaultConfig();
        
        // Initialize provider manager with error handling
        try {
            $this->providerManager = new ProviderManager($this);
            $this->providerManager->registerDefaultProviders();
            
            // Initialize scoreboard manager
            $this->scoreboardManager = new ScoreboardManager($this);
            
            // Register events
            $this->getServer()->getPluginManager()->registerEvents($this, $this);
            
            // Start scoreboard update task
            $updateInterval = $this->getConfig()->get("update-interval", 20);
            $this->getScheduler()->scheduleRepeatingTask(new ScoreUpdateTask($this), $updateInterval);
            
            $this->getLogger()->info("§aScoreHud has been enabled!");
            
            // Create scoreboards for online players (in case of reload)
            foreach ($this->getServer()->getOnlinePlayers() as $player) {
                $this->getScoreboardManager()->createScoreboard($player);
            }
        } catch (\Throwable $e) {
            $this->getLogger()->critical("Failed to enable ScoreHud: " . $e->getMessage());
            $this->getLogger()->critical("Stack trace: " . $e->getTraceAsString());
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }
    
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "scorehud") {
            if (!isset($args[0])) {
                $sender->sendMessage("§e--- ScoreHud Commands ---");
                $sender->sendMessage("§e/scorehud reload §f- Reload the configuration");
                $sender->sendMessage("§e/scorehud toggle §f- Toggle your scoreboard on/off");
                return true;
            }
            
            switch ($args[0]) {
                case "reload":
                    if (!$sender->hasPermission("scorehud.command.reload")) {
                        $sender->sendMessage("§cYou don't have permission to use this command.");
                        return true;
                    }
                    $this->reloadConfig();
                    $sender->sendMessage("§aScoreHud configuration reloaded!");
                    $this->getScoreboardManager()->updateAllScoreboards();
                    return true;
                
                case "toggle":
                    if (!$sender->hasPermission("scorehud.command.toggle")) {
                        $sender->sendMessage("§cYou don't have permission to use this command.");
                        return true;
                    }
                    
                    if (!$sender instanceof Player) {
                        $sender->sendMessage("§cThis command can only be used in-game.");
                        return true;
                    }
                    
                    if ($this->getScoreboardManager()->hasScoreboard($sender)) {
                        $this->getScoreboardManager()->removeScoreboard($sender);
                        $sender->sendMessage("§aScoreboard turned off.");
                    } else {
                        $this->getScoreboardManager()->createScoreboard($sender);
                        $sender->sendMessage("§aScoreboard turned on.");
                    }
                    return true;
                
                default:
                    $sender->sendMessage("§cUnknown subcommand. Use /scorehud for help.");
                    return true;
            }
        }
        
        return false;
    }
    
    public function onDisable(): void {
        $this->getLogger()->info("§cScoreHud has been disabled!");
    }
    
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        try {
            $player = $event->getPlayer();
            $this->scoreboardManager->createScoreboard($player);
        } catch (\Throwable $e) {
            $this->getLogger()->error("Error creating scoreboard: " . $e->getMessage());
        }
    }
    
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        try {
            $player = $event->getPlayer();
            $this->scoreboardManager->removeScoreboard($player);
        } catch (\Throwable $e) {
            $this->getLogger()->error("Error removing scoreboard: " . $e->getMessage());
        }
    }
    
    /**
     * @return ScoreboardManager
     */
    public function getScoreboardManager(): ScoreboardManager {
        return $this->scoreboardManager;
    }
    
    /**
     * @return ProviderManager
     */
    public function getProviderManager(): ProviderManager {
        return $this->providerManager;
    }
}
