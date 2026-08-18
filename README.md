<div align="center">

# 🏰 Factions

A modular, asynchronous Factions core plugin designed for **PocketMine-MP 5.x** with multi-language support, in-memory LRU caching, and cuboid subclaims.

[![CI](https://github.com/Jorgebyte/Factions/actions/workflows/ci.yml/badge.svg)](https://github.com/Jorgebyte/Factions/actions)
[![PocketMine-MP](https://img.shields.io/badge/PocketMine--MP-5.0+-orange.svg?style=flat-square)](https://pmmp.io/)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%20%7C%208.3%20%7C%208.4-blue.svg?style=flat-square)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)
[![Translations](https://img.shields.io/badge/Translations-28%20Locales-9cf.svg?style=flat-square)](resources/languages)
[![Discord](https://img.shields.io/badge/Discord-Join%20Community-5865F2?style=flat-square&logo=discord&logoColor=white)](https://discord.jorgebyte.com)

</div>

---

> ### ⚠️ Project Status (Work in Progress)
> This plugin is currently in **active development and testing**. Some advanced mechanics, edge cases, and balance formulas are still being refined. It is provided as an open-source project for testing, contribution, and feedback.

---

## 🌟 Key Features

* **⚡ Fully Asynchronous Architecture**: Uses [`libasynql`](https://github.com/poggit/libasynql) and [`await-generator`](https://github.com/SOF3/await-generator) to ensure zero main-thread lag during database operations (SQLite / MySQL support).
* **🧠 Multi-Tier In-Memory Cache**: Low, medium, and critical priority memory caching policies with automatic warmup and memory threshold eviction.
* **🛡️ Claim & Subclaim System**:
  * Chunk-based territory claiming with cost and limit configurations.
  * **Cuboid Subclaims**: Create restricted sub-areas with minimum role requirements (e.g. Leader/Co-Leader vaults).
  * **Anti-Desync Mitigation**: Mitigates client-server desync when unauthorized players interact with claimed blocks.
* **⚡ Power & Raid System**:
  * Dynamic faction power scaling with member count, kills, and deaths.
  * **Power Freeze**: When power reaches zero, the faction's land becomes raidable and frozen for a configurable duration before regenerating.
  * Overclaim vulnerability when a faction's claims exceed its active power.
* **🤝 Diplomatic Relations**: Alliance requests, shared friendly fire rules, and neutral relations.
* **👁️ Particle Chunk Visualizer**: Visualizes chunk boundaries in real time with dynamic tick auto-scaling for performance.
* **🌐 28 Native Languages**: Full built-in localization matching standard Minecraft Bedrock locales (`en_US`, `es_ES`, `es_MX`, `de_DE`, `fr_FR`, `ja_JP`, `zh_CN`, `ru_RU`, etc.).
* **🔌 Developer API (`FactionsAPI`)**: A clean singleton API for third-party integrations (Scoreboards, Rank Systems, Custom Addons).

---

## 🌐 Supported Languages

Factions automatically detects the player's client language and supports 28 locales:

| Locales | Languages |
| :--- | :--- |
| `en_US`, `en_GB` | English (US & UK) |
| `es_ES`, `es_MX` | Español (España & México) |
| `de_DE`, `it_IT` | Deutsch, Italiano |
| `fr_FR`, `fr_CA` | Français (France & Canada) |
| `pt_BR`, `pt_PT` | Português (Brasil & Portugal) |
| `ru_RU`, `uk_UA`, `pl_PL` | Русский, Українська, Polski |
| `cs_CZ`, `sk_SK`, `bg_BG` | Čeština, Slovenčina, Български |
| `zh_CN`, `zh_TW`, `ja_JP`, `ko_KR` | 中文 (简体/繁體), 日本語, 한국어 |
| `nl_NL`, `da_DK`, `fi_FI`, `nb_NO`, `sv_SE` | Nederlands, Dansk, Suomi, Norsk, Svenska |
| `hu_HU`, `id_ID`, `el_GR`, `tr_TR` | Magyar, Bahasa Indonesia, Ελληνικά, Türkçe |

---

## 📋 Commands

| Command | Permission | Description |
| :--- | :--- | :--- |
| `/f create <name>` | `factions.cmd.create` | Create a new faction |
| `/f disband` | `factions.cmd.disband` | Disband your faction (Leader only) |
| `/f info [faction]` | `factions.cmd.info` | View faction information and status |
| `/f invite <player>` | `factions.cmd.invite` | Invite a player to your faction |
| `/f accept / /f deny` | `factions.cmd.accept` | Accept or deny pending invites |
| `/f leave` | `factions.cmd.leave` | Leave your current faction |
| `/f kick <player>` | `factions.cmd.kick` | Kick a member from the faction |
| `/f promote / /f demote` | `factions.cmd.promote` | Manage member roles |
| `/f leader <player>` | `factions.cmd.leader` | Transfer leadership |
| `/f claim / /f unclaim` | `factions.cmd.claim` | Claim or unclaim land |
| `/f sethome / /f home` | `factions.cmd.home` | Set or teleport to faction home |
| `/f ally <send\|accept\|remove>` | `factions.cmd.ally` | Manage alliances |
| `/f subclaim <create\|remove\|info>` | `factions.cmd.subclaim` | Manage cuboid subclaims |
| `/f map` / `/f chunk` | `factions.cmd.map` | Visual map & particle boundary |
| `/f top <power\|kills\|money>` | `factions.cmd.top` | View top factions |
| `/f admin <power\|freeze>` | `factions.admin` | Administrator management commands |

---

## 💻 Developer API

Hook into `FactionsAPI` from any PocketMine plugin:

```php
use Jorgebyte\Factions\api\FactionsAPI;
use SOFe\AwaitGenerator\Await;

$api = FactionsAPI::getInstance();

// 1. Get player's faction from cache (synchronous)
$faction = $api->getPlayerFactionCached($player);
if ($faction !== null) {
    $player->sendMessage("Your faction: " . $faction->getName());
}

// 2. Load faction asynchronously
Await::f2c(function() use ($api, $player): \Generator {
    $faction = yield from $api->loadPlayerFaction($player->getXuid());
    if ($faction !== null) {
        $power = $faction->power;
        $isRaidable = $faction->isRaidable();
    }
});

// 3. Check if a position is claimed
$claim = $api->getClaimAt($player->getPosition());
if ($claim !== null) {
    $ownerFaction = $api->getFactionAt($player->getPosition());
}
```

---

## 📦 Dependencies & Virions

### Required Virions
These libraries are bundled during compilation with `build.php`:
* **[Commando](https://github.com/CortexPE/Commando)** (`cortexpe/commando`): Advanced command arguments & subcommands parsing.
* **[libasynql](https://github.com/poggit/libasynql)** (`poggit/libasynql`): Asynchronous multi-threaded SQLite3 and MySQL database connector.
* **[await-generator](https://github.com/SOF3/await-generator)** (`sofe/await-generator`): Non-blocking asynchronous coroutine execution.
* **[libPiggyEconomy](https://github.com/DaPigGuy/libPiggyEconomy)** (`dapigguy/libpiggyeconomy`): Universal multi-economy provider integration (BedrockEconomy, EconomyAPI).
* **[Languages](https://github.com/IvanCraft623/Languages)** (`ivancraft623/languages`): Player locale detection and language management.

### Optional Integrations (Soft Dependencies)
* **[RankSystem](https://github.com/IvanCraft623/RankSystem)**: Auto-registers `{faction}` tag in player chat formats and nametags.
* **[ScoreHud](https://github.com/Ifera/ScoreHud)**: Provides custom scorehud tags (`{factions.name}`, `{factions.power}`, etc.).

---

## 🛠️ Building from Source

To compile `Factions.phar` directly:

```bash
# Clone the repository
git clone https://github.com/Jorgebyte/Factions.git

# Install composer dependencies
composer install --no-dev

# Build the Phar
php -d phar.readonly=0 build.php
```

---

## 👤 Author

* **Jorgebyte**
  * Website: [jorgebyte.com](https://jorgebyte.com)
  * Discord: [discord.jorgebyte.com](https://discord.jorgebyte.com)
  * Instagram: [@jorgebyte_](https://instagram.com/jorgebyte_)

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

