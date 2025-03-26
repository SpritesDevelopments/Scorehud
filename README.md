# ScoreHud

A powerful scoreboard plugin for PocketMine-MP servers with support for BedrockEconomy, TokensAPI, and many other features.

## Features

- 📊 Dynamic scoreboard with real-time updates
- 💰 BedrockEconomy integration
- 🪙 TokensAPI integration
- 🔌 Easy to extend with custom placeholders
- 🧩 Simple configuration
- 🛠️ Reload configuration without restart
- 👥 Player-specific scoreboards

## Placeholders

ScoreHud comes with many built-in placeholders:

### Default
- `{name}` - Player name
- `{display_name}` - Player display name
- `{health}` - Player health
- `{max_health}` - Player max health
- `{x}` - Player X coordinate
- `{y}` - Player Y coordinate
- `{z}` - Player Z coordinate
- `{world}` - World name
- `{online}` - Number of online players
- `{max_online}` - Maximum player count
- `{tps}` - Server TPS

### BedrockEconomy (requires BedrockEconomy plugin)
- `{money}` - Player's money balance

### TokensAPI (requires TokensAPI plugin)
- `{tokens}` - Player's token balance

## Commands

- `/scorehud reload` - Reload the configuration
- `/scorehud toggle` - Toggle your scoreboard on/off

## Permissions

- `scorehud.command` - Allows using ScoreHud commands (default: op)

## Configuration

```yaml
# The title of the scoreboard
title: "§e§lMy Server"

# The update interval in ticks (20 ticks = 1 second)
update-interval: 20

# Scoreboard lines
lines:
  - "§r"
  - "§6Player: §f{name}"
  - "§6Online: §f{online}/{max_online}"
  - "§r"
  - "§6Money: §f{money}"
  - "§6Tokens: §f{tokens}"
  - "§r"
  - "§6Health: §f{health}/{max_health}"
  - "§6Location:"
  - "§f - World: {world}"
  - "§f - X: {x} Y: {y} Z: {z}"
  - "§r"
  - "§eplay.myserver.com"
```

## For Developers

Extend ScoreHud with your own placeholder provider:

```php
class MyCustomProvider implements PlaceholderProvider {
    public function getName(): string {
        return "CustomProvider";
    }
    
    public function processPlaceholders(string $text, Player $player): string {
        // Replace {custom_placeholder} with your value
        return str_replace("{custom_placeholder}", "my value", $text);
    }
}

// Register your provider
$plugin->getProviderManager()->registerProvider(new MyCustomProvider($plugin));
```

## License

This plugin is licensed under the MIT License. See the LICENSE file for more information.
