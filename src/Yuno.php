<?php
/*
    Yuno Gasai. A Discord.JS based bot, with multiple features.
    Copyright (C) 2018 Maeeen <maeeennn@gmail.com>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see https://www.gnu.org/licenses/.
*/

namespace Yuno;

use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Yuno\Lib\Prompt;
use Yuno\Lib\ConfigManager;
use Yuno\Lib\Config;
use Yuno\Lib\CommandManager;

/**
 * Main Yuno Gasai 2 Class - PHP Port
 */
class Yuno
{
    public const DEFAULT_CONFIG_FILE = 'config.json';
    public const DEFAULT_CONFIG = 'DEFAULT_CONFIG.json';

    public Prompt $prompt;
    public ?Discord $discord = null;
    public ?Discord $dC = null; // Alias for discord
    public ?Config $config = null;
    public ?Database $database = null;
    public ?CommandManager $commandMan = null;
    public array $modules = [];

    public string $version;
    public int $intVersion;

    private ?string $customToken = null;
    private ?string $customConfigFile = null;

    public function __construct()
    {
        $this->prompt = Prompt::init();

        // Get version from composer.json
        $package = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
        $this->version = $package['version'] ?? '2.5.0';
        $this->intVersion = (int)str_replace('.', '', $this->version);

        $this->database = new Database();

        $this->prompt->info("Yuno {$this->version} initialised.");
    }

    /**
     * Show CLI help
     */
    public function showCLIHelp(): void
    {
        $this->prompt->showHelp([
            [
                'argument' => '--help',
                'aliases' => ['-h'],
                'description' => 'Shows this help message.'
            ],
            [
                'argument' => '--token=[token]',
                'description' => 'Starts the bot with a new token. The bot will save the token.'
            ],
            [
                'argument' => '--custom-config=[file]',
                'description' => 'Load a custom config file.'
            ],
            [
                'argument' => '--no-colors',
                'aliases' => ['-nc'],
                'description' => 'Logs without any color.'
            ],
        ]);
    }

    /**
     * Parse CLI arguments and launch the bot
     */
    public function parseArguments(array $argv): void
    {
        // Remove first two elements (php and script name)
        $args = array_slice($argv, 1);

        // Check for help flag
        if (in_array('--help', $args) || in_array('-h', $args)) {
            $this->showCLIHelp();
            return;
        }

        // Show ASCII banner
        $this->prompt->showBanner($this->version);

        // Parse arguments
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--token=')) {
                $this->customToken = substr($arg, 8);
            } elseif (str_starts_with($arg, '--custom-config=')) {
                $this->customConfigFile = substr($arg, 16);
            } elseif ($arg === '--no-colors' || $arg === '-nc') {
                $this->prompt->colors = false;
            }
        }

        $this->launch();
    }

    /**
     * Read configuration file
     */
    public function readConfig(string $file): void
    {
        $configManager = ConfigManager::init();

        // Load defaults
        $defaults = [];
        if (file_exists(self::DEFAULT_CONFIG)) {
            $defaults = json_decode(file_get_contents(self::DEFAULT_CONFIG), true) ?? [];
        }

        try {
            $this->config = $configManager->readConfigSync($file)->defaults($defaults);
        } catch (\Exception $e) {
            // If config file doesn't exist, create from defaults
            if (!file_exists($file) && !empty($defaults)) {
                file_put_contents($file, json_encode($defaults, JSON_PRETTY_PRINT));
                $this->config = $configManager->readConfigSync($file)->defaults($defaults);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Load all modules
     */
    private function loadModules(): void
    {
        $modulesDir = __DIR__ . '/Modules';

        if (!is_dir($modulesDir)) {
            return;
        }

        $files = scandir($modulesDir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (!str_ends_with($file, '.php')) {
                continue;
            }

            // Skip interfaces
            if (str_contains($file, 'Interface')) {
                continue;
            }

            $className = 'Yuno\\Modules\\' . pathinfo($file, PATHINFO_FILENAME);

            if (!class_exists($className)) {
                continue;
            }

            try {
                $module = new $className();

                if (method_exists($module, 'init')) {
                    $module->init($this);
                }

                $this->modules[] = $module;

                $moduleName = method_exists($module, 'getModuleName')
                    ? $module->getModuleName()
                    : $className;

                $this->prompt->success("Module {$moduleName} successfully loaded.");
            } catch (\Exception $e) {
                $this->prompt->error("Failed to load module {$file}", $e);
            }
        }
    }

    /**
     * Trigger config loaded events for modules
     */
    private function triggerConfigEvents(): void
    {
        // Trigger for prompt
        $this->prompt->configLoaded($this, $this->config);

        // Trigger for command manager
        if ($this->commandMan !== null) {
            $this->commandMan->configLoaded($this, $this->config);
        }

        // Trigger for modules
        foreach ($this->modules as $module) {
            if (method_exists($module, 'configLoaded')) {
                $module->configLoaded($this, $this->config);
            }
        }
    }

    /**
     * Trigger shutdown events for modules
     */
    private function triggerShutdownEvents(): void
    {
        foreach ($this->modules as $module) {
            if (method_exists($module, 'beforeShutdown')) {
                $module->beforeShutdown($this, $this->config);
            }
        }
    }

    /**
     * Get a module by name
     */
    public function getModule(string $name): ?object
    {
        foreach ($this->modules as $module) {
            if (method_exists($module, 'getModuleName') && $module->getModuleName() === $name) {
                return $module;
            }
        }
        return null;
    }

    /**
     * Refresh a module (calls beforeShutdown then init with hotReloaded=true)
     */
    public function _refreshMod(string $name): void
    {
        $module = $this->getModule($name);
        if ($module !== null) {
            if (method_exists($module, 'beforeShutdown')) {
                $module->beforeShutdown($this);
            }
            if (method_exists($module, 'init')) {
                $module->init($this, true);
            }
            if (method_exists($module, 'discordConnected') && $this->discord !== null) {
                $module->discordConnected($this);
            }
        }
    }

    /**
     * Shutdown the bot
     */
    public function shutdown(int $reason = 0, ?\Throwable $e = null): void
    {
        $reasonStr = match ($reason) {
            1 => "User (via terminal: CTRL+C) asked to.",
            2 => "Shutdown command.",
            3 => "Database upgrade cancelled.",
            -1 => "Fatal exception.",
            default => "Unknown."
        };

        $this->prompt->info("Shutting down... Reason: {$reasonStr}");

        // Trigger shutdown events
        $this->triggerShutdownEvents();

        // Save config
        if ($this->config !== null) {
            $this->prompt->info("Saving config...");
            try {
                $this->config->save();
                $this->prompt->success("Config saved!");
            } catch (\Exception $e) {
                $this->prompt->error("Error while saving config", $e);
            }
        }

        // Close database
        if ($this->database !== null && $this->database->isOpen()) {
            $this->prompt->info("Closing database...");
            try {
                $this->database->close();
                $this->prompt->success("Database closed!");
            } catch (\Exception $e) {
                $this->prompt->error("Error while closing database", $e);
            }
        }

        // Disconnect from Discord
        if ($this->discord !== null) {
            $this->prompt->info("Disconnecting from Discord...");
            try {
                $this->discord->close();
                $this->prompt->success("Successfully disconnected from Discord.");
            } catch (\Exception $e) {
                $this->prompt->error("Error while disconnecting from Discord", $e);
            }
        }

        exit(0);
    }

    /**
     * Launch the bot
     */
    public function launch(): void
    {
        // Load config
        if ($this->config === null) {
            $configFile = $this->customConfigFile ?? self::DEFAULT_CONFIG_FILE;
            $this->readConfig($configFile);
        }

        $this->prompt->success("Config loaded.");

        $this->prompt->info("Loading modules...");
        $this->loadModules();

        $this->triggerConfigEvents();

        // Initialize database
        $dbPath = $this->config->get('database');
        $dbPragmas = $this->config->get('database.pragmas');

        $newDb = !file_exists($dbPath);
        if ($newDb) {
            $this->prompt->warn("Database {$dbPath} doesn't exist. Creating a new one...");
        }

        $dbOptions = [];
        if (is_array($dbPragmas)) {
            $dbOptions['pragmas'] = [
                'walMode' => $dbPragmas['walMode'] ?? false,
                'performanceMode' => $dbPragmas['performanceMode'] ?? false,
                'memoryTemp' => $dbPragmas['memoryTemp'] ?? false,
                'cacheSize' => $dbPragmas['cacheSize'] ?? null,
                'mmapSize' => $dbPragmas['mmapSize'] ?? null,
            ];
        }

        try {
            $this->database->open($dbPath, $dbOptions);
            DatabaseCommands::initDB($this->database, $this, $newDb);
            $this->prompt->success("SQLite database opened.");

            if ($dbPragmas && ($dbPragmas['walMode'] ?? false || $dbPragmas['performanceMode'] ?? false)) {
                $this->prompt->info("Database optimizations applied.");
            }
        } catch (\Exception $e) {
            $this->prompt->error("Cannot open database.", $e);
            $this->shutdown(-1);
            return;
        }

        // Connect to Discord
        $this->prompt->info("Connecting to Discord...");

        $token = $this->customToken ?? $this->config->get('discord.token');

        if ($this->customToken !== null) {
            $this->config->set('discord.token', $this->customToken);
            $this->config->save();
        }

        if (empty($token) || $token === '<empty>') {
            $this->prompt->error("No Discord token provided. Please set discord.token in config.json or use --token=YOUR_TOKEN");
            $this->shutdown(-1);
            return;
        }

        try {
            $this->discord = new Discord([
                'token' => $token,
                'intents' => Intents::getDefaultIntents() |
                    Intents::GUILD_MEMBERS |
                    Intents::MESSAGE_CONTENT |
                    Intents::DIRECT_MESSAGES |
                    Intents::GUILD_BANS,
                'loadAllMembers' => false,
            ]);

            $this->dC = $this->discord;

            // Initialize command manager after Discord is created
            $this->commandMan = new CommandManager(__DIR__ . '/Commands');
            $this->commandMan->init();
            $this->commandMan->configLoaded($this, $this->config);

            // Register ready event
            $this->discord->on('ready', function (Discord $discord) {
                $this->prompt->success("Successfully connected to Discord as {$discord->user->username}#{$discord->user->discriminator}");
                $this->prompt->info("Bot launched.");

                // Notify modules of Discord connection
                foreach ($this->modules as $module) {
                    if (method_exists($module, 'discordConnected')) {
                        $module->discordConnected($this);
                    }
                }
            });

            // Handle errors
            $this->discord->on('error', function (\Exception $e) {
                $this->prompt->error("Discord.PHP threw an error", $e);
            });

            // Handle SIGINT (Ctrl+C)
            if (function_exists('pcntl_signal')) {
                pcntl_signal(SIGINT, function () {
                    $this->shutdown(1);
                });
            }

            // Start the Discord bot
            $this->discord->run();

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'invalid token') || str_contains($e->getMessage(), 'Incorrect login')) {
                $this->prompt->error("Error while connecting to Discord. Incorrect token.");
            } else {
                $this->prompt->error("Error connecting to Discord", $e);
            }
            $this->shutdown(-1);
        }
    }
}
