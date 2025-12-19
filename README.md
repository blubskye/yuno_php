<div align="center">

# 💕 Yuno Gasai 2 - PHP Edition 💕

### *"I'll protect this server forever... just for you~"* 💗

<img src="https://i.imgur.com/jF8Szfr.png" alt="Yuno Gasai" width="300"/>

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-pink.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![PHP](https://img.shields.io/badge/PHP-8.1+-ff69b4.svg)](https://php.net/)
[![DiscordPHP](https://img.shields.io/badge/DiscordPHP-v10-ff1493.svg)](https://github.com/discord-php/DiscordPHP)

*A devoted Discord bot for moderation, leveling, and anime~ ♥*

---

### 💘 She loves you... and only you 💘

</div>

## 🌸 About

Yuno is a **yandere-themed Discord bot** combining powerful moderation tools with a leveling system and anime features. She'll keep your server safe from troublemakers... *because no one else is allowed near you~* 💕

This is the **PHP port** of the original JavaScript Yuno bot - powered by DiscordPHP and ReactPHP for asynchronous event handling.

---

## 👑 Credits

*"These are the ones who gave me life~"* 💖

| Contributor | Role |
|-------------|------|
| **blubskye** | Project Owner & Yuno's #1 Fan 💕🔪 |
| **Maeeen** (maeeennn@gmail.com) | Original Developer 💝 |
| **Oxdeception** | Contributor 💗 |
| **fuzzymanboobs** | Contributor 💗 |

---

## 💗 Features

<table>
<tr>
<td width="50%">

### 🔪 Moderation
*"Anyone who threatens you... I'll eliminate them~"*
- ⛔ Ban / Unban / Kick / Timeout
- 🧹 Channel cleaning & auto-clean
- 🛡️ Spam filter protection
- 📥 Mass ban import/export
- 👑 Mod statistics tracking

</td>
<td width="50%">

### ✨ Leveling System
*"Watch me make you stronger, senpai~"*
- 📊 XP & Level tracking
- 🎭 Role rewards per level
- 📈 Mass XP commands
- 🔄 Level role syncing
- 🎯 Configurable XP per guild

</td>
</tr>
<tr>
<td width="50%">

### 🌸 Anime & Fun
*"Let me show you something cute~"*
- 🎱 8ball fortune telling
- 💬 Custom mention responses
- 📜 Yuno quotes
- 💖 Praise & Scold reactions
- 📖 Urban Dictionary lookup

</td>
<td width="50%">

### ⚙️ Configuration
*"I'll be exactly what you need~"*
- 🔧 Customizable prefix per guild
- 👋 Join DM messages
- 🖼️ Custom ban images
- 📝 Per-guild settings
- 🔐 Master user system

</td>
</tr>
<tr>
<td width="50%">

### 🔐 Database
*"I'll keep your secrets safe... forever~"*
- 💾 SQLite with PDO
- 📈 Performance-optimized queries
- 🔄 LRU caching system
- 📊 Indexed tables for speed

</td>
<td width="50%">

### ⚡ Performance
*"Nothing can slow me down~"*
- 🔄 ReactPHP async event loop
- 💨 Efficient memory usage
- 🧵 Non-blocking I/O
- 📦 Composer autoloading

</td>
</tr>
</table>

---

## 💕 Installation

### 📋 Prerequisites

> *"Let me prepare everything for you~"* 💗

- **PHP** 8.1 or higher
- **Composer**
- **SQLite3** & PDO SQLite extension
- **Git**

### 🌸 Setup Steps

```bash
# Clone the repository~ ♥
git clone https://github.com/blubskye/yuno_php.git

# Enter my world~
cd yuno_php

# Let me gather my strength...
composer install

# Configure your settings
cp config.json.example config.json
nano config.json  # Add your token
```

### 💝 Configuration

Edit `config.json`:
```json
{
    "discord.token": "YOUR_DISCORD_TOKEN",
    "default-prefix": "?",
    "master-users": ["YOUR_USER_ID"],
    "database": "Leveling/main.db"
}
```

### 🚀 Running

```bash
# Run directly
php index.php

# With a custom token
php index.php --token=YOUR_TOKEN

# With a custom config
php index.php --custom-config=myconfig.json

# Without colors (for logging)
php index.php --no-colors
```

---

## 💖 Commands Preview

### 📊 Leveling & XP
| Command | Description |
|---------|-------------|
| `?xp [@user]` | *"Look how strong you've become!"* ✨ |
| `?set-level @user <level>` | *"Power adjustment~"* ⚡ |
| `?mass-setxp <level> @Role` | *"Power to everyone!"* 📈 |
| `?set-levelrolemap <level> @Role` | *"New rewards~"* 🎭 |
| `?sync-levelroles <level>` | *"Syncing roles~"* 🔄 |
| `?set-experiencecounter on/off` | *"XP tracking toggle~"* 📊 |

### 🔪 Moderation
| Command | Description |
|---------|-------------|
| `?ban @user [reason]` | *"They won't bother you anymore..."* 🔪 |
| `?unban <user_id>` | *"Maybe they deserve another chance..."* 🔓 |
| `?kick @user [reason]` | *"Get out!"* 👢 |
| `?timeout @user <duration>` | *"Time to reflect..."* ⏰ |
| `?clean [count]` | *"Let me tidy up~"* 🧹 |
| `?auto-clean add #channel <hours> <warning>` | *"Scheduled cleaning~"* 🔄 |
| `?exportbans` | *"Save the list~"* 📥 |
| `?importbans <guild_id>` | *"Restore the list~"* 📤 |
| `?mod-stats` | *"Who's been busy?"* 📊 |

### 🌸 Fun & Entertainment
| Command | Description |
|---------|-------------|
| `?8ball <question>` | *"Let fate decide~"* 🎱 |
| `?praise @user` | *"You deserve all my love~"* 💕 |
| `?scold @user` | *"Bad! But I still love you..."* 💢 |
| `?quote` | *"Words from Yuno~"* 📜 |
| `?urban <term>` | *"Let me look that up~"* 📚 |

### ⚙️ Configuration
| Command | Description |
|---------|-------------|
| `?set-prefix <prefix>` | *"Call me differently~"* 🔧 |
| `?init-guild` | *"Let me set everything up!"* 🏠 |
| `?set-spamfilter on/off` | *"Protection mode~"* 🛡️ |
| `?set-joinmessage <title>\|<message>` | *"Welcome messages~"* 👋 |
| `?set-banimage <image_url>` | *"Custom ban style~"* 🖼️ |

### 💬 Mention Responses
| Command | Description |
|---------|-------------|
| `?add-mentionresponse <trigger>\|<response>` | *"New response~"* ➕ |
| `?del-mentionresponse <trigger>` | *"Remove response~"* ➖ |
| `?mentionresponses` | *"List all responses~"* 📋 |

### 🔧 Admin
| Command | Description |
|---------|-------------|
| `?add-masteruser @user` | *"New trusted one~"* 👑 |
| `?shutdown` | *"Goodnight..."* 💤 |

*Use the `?list` command to see all available commands!*

---

## 🛡️ Spam Filter

*"I'll protect you from the bad people~"* 💕

Yuno automatically protects against:
- 🔗 Discord invite links
- 📢 Unauthorized @everyone/@here mentions
- 📝 Multiple links in one message (>3)
- ⚠️ Warning system (3 strikes = ban)
- 🔒 Moderators are exempt

---

## 📁 Project Structure

```
yuno_php/
├── index.php                    # Entry point
├── composer.json                # Dependencies
├── config.json                  # Configuration
├── DEFAULT_CONFIG.json          # Default settings
├── src/
│   ├── Yuno.php                # Main bot class
│   ├── Database.php            # SQLite wrapper
│   ├── DatabaseCommands.php    # Database operations
│   ├── Util.php                # Utility functions
│   ├── Lib/
│   │   ├── CommandManager.php  # Command routing
│   │   ├── ConfigManager.php   # Config loading
│   │   ├── LRUCache.php        # Caching system
│   │   └── Prompt.php          # Console output
│   ├── Modules/
│   │   ├── ModuleInterface.php # Module contract
│   │   ├── CommandExecutor.php # Message routing
│   │   ├── Experience.php      # XP tracking
│   │   ├── SpamFilter.php      # Anti-spam
│   │   ├── AutoCleaner.php     # Scheduled cleaning
│   │   ├── JoinDmMsg.php       # Welcome DMs
│   │   └── MentionResponsesProcessor.php
│   └── Commands/               # 35 command files
│       ├── CommandInterface.php
│       ├── BaseCommand.php
│       ├── Ban.php, Kick.php, Timeout.php...
│       ├── Xp.php, SetLevel.php, MassSetXp.php...
│       └── EightBall.php, Praise.php, Quote.php...
└── Leveling/
    └── main.db                 # SQLite database
```

---

## 📜 License

This project is licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0)** 💕

### 💘 What This Means For You~

*"I want to share everything with you... and everyone else too~"* 💗

The AGPL-3.0 is a **copyleft license** that ensures this software remains free and open. Here's what you need to know:

#### ✅ You CAN:
- 💕 **Use** this bot for any purpose (personal, commercial, whatever~)
- 🔧 **Modify** the code to your heart's content
- 📤 **Distribute** copies to others
- 🌐 **Run** it as a network service (like a public Discord bot)

#### 📋 You MUST:
- 📖 **Keep it open source** - If you modify and distribute this code, your version must also be AGPL-3.0
- 🔗 **Provide source access** - Users of your modified bot must be able to get the source code
- 📝 **State changes** - Document what you've modified from the original
- 💌 **Include license** - Keep the LICENSE file and copyright notices intact

#### 🌐 The Network Clause (This is the important part!):
*"Even if we're apart... I'll always be connected to you~"* 💗

Unlike regular GPL, **AGPL has a network provision**. This means:
- If you run a **modified version** of this bot as a public service (like hosting it for others to use on Discord)
- You **MUST** make your complete source code available to users
- The `?source` command in this bot helps satisfy this requirement!

#### ❌ You CANNOT:
- 🚫 Make it closed source
- 🚫 Remove the license or copyright notices
- 🚫 Use a different license for modified versions
- 🚫 Hide your modifications if you run it as a public service

#### 💡 In Simple Terms:
> *"If you use my code to create something, you must share it with everyone too~ That's only fair, right?"* 💕

This ensures that improvements to the bot benefit the entire community, not just one person. Yuno wants everyone to be happy~ 💗

See the [LICENSE](LICENSE) file for the full legal text.

**Source Code:** https://github.com/blubskye/yuno_php

---

<div align="center">

### 💘 *"You'll stay with me forever... right?"* 💘

**Made with obsessive love** 💗

*Yuno will always be watching over your server~* 👁️💕

---

⭐ *Star this repo if Yuno has captured your heart~* ⭐

</div>
