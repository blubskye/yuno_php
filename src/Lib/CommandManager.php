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

namespace Yuno\Lib;

use Discord\Parts\Channel\Message;
use Discord\Parts\User\Member;
use Yuno\Yuno;
use Yuno\Commands\CommandInterface;

/**
 * Command manager - loads, parses, and executes commands
 */
class CommandManager
{
    private string $directory;
    private array $commands = [];
    private string $insufficientPermissionsMessage = "Insufficient permissions.";
    private array $masterUsers = [];
    private Prompt $prompt;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
        $this->prompt = Prompt::init();
    }

    /**
     * Initialize command manager by loading all commands
     */
    public function init(): void
    {
        if (!is_dir($this->directory)) {
            $this->prompt->warn("Commands directory not found: {$this->directory}");
            return;
        }

        $files = scandir($this->directory);
        $cmdLoaded = 0;

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (!str_ends_with($file, '.php')) {
                continue;
            }

            // Skip interfaces and base classes
            if (str_contains($file, 'Interface') || str_contains($file, 'Base')) {
                continue;
            }

            try {
                $this->loadCommand($file);
                $cmdLoaded++;
            } catch (\Exception $e) {
                $this->prompt->error("Failed to load command {$file}", $e);
            }
        }

        $this->prompt->info("A total of {$cmdLoaded} command(s) has been loaded.");
    }

    /**
     * Load a command from file
     */
    private function loadCommand(string $file): void
    {
        $className = 'Yuno\\Commands\\' . pathinfo($file, PATHINFO_FILENAME);

        if (!class_exists($className)) {
            return;
        }

        $command = new $className();

        if (!$command instanceof CommandInterface) {
            return;
        }

        $about = $command->getAbout();
        $commandName = $about['command'] ?? strtolower(pathinfo($file, PATHINFO_FILENAME));

        // Check for duplicates
        if ($this->commandExists($commandName)) {
            throw new \RuntimeException("Command {$commandName} already exists.");
        }

        $this->commands[$commandName] = $command;

        // Register aliases
        $aliases = $about['aliases'] ?? [];
        if (is_string($aliases)) {
            $aliases = [$aliases];
        }

        foreach ($aliases as $alias) {
            $this->commands[$alias] = $command;
        }

        $this->prompt->info("Command {$commandName} has been loaded.");
    }

    /**
     * Handle config loaded event
     */
    public function configLoaded(Yuno $yuno, Config $config): void
    {
        $insufficientMsg = $config->get('chat.insufficient-permissions');
        $masterUsers = $config->get('commands.master-users');

        if (is_string($insufficientMsg)) {
            $this->insufficientPermissionsMessage = $insufficientMsg;
        }

        if (is_string($masterUsers)) {
            $masterUsers = [$masterUsers];
        }

        if (is_array($masterUsers)) {
            $this->masterUsers = $masterUsers;
        }
    }

    /**
     * Parse a command string
     */
    public function parse(string $command): array
    {
        $spacePos = strpos($command, ' ');
        $mainCommand = $spacePos !== false
            ? strtolower(substr($command, 0, $spacePos))
            : strtolower($command);

        $argsStr = $spacePos !== false
            ? trim(substr($command, $spacePos))
            : '';

        // Parse arguments, respecting quoted strings
        $args = [];
        if ($argsStr !== '') {
            preg_match_all('/[^\s"]+|"([^"]*)"/', $argsStr, $matches);
            foreach ($matches[0] as $i => $match) {
                // Remove surrounding quotes
                if (str_starts_with($match, '"') && str_ends_with($match, '"')) {
                    $args[] = substr($match, 1, -1);
                } else {
                    $args[] = $match;
                }
            }
        }

        return [
            'command' => $mainCommand,
            'args' => $args
        ];
    }

    /**
     * Check if a command exists
     */
    public function commandExists(string $command): bool
    {
        return isset($this->commands[$command]);
    }

    /**
     * Check if user is a master user
     */
    public function isUserMaster(string $userId): bool
    {
        return in_array($userId, $this->masterUsers);
    }

    /**
     * Convert v12 SCREAMING_SNAKE_CASE permission to v14 style
     */
    private function convertPermissionName(string $permission): string
    {
        // If not screaming snake case, return as-is
        if (!str_contains($permission, '_')) {
            return $permission;
        }

        // Convert SCREAMING_SNAKE_CASE to PascalCase
        return str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $permission))));
    }

    /**
     * Check if member has required permissions
     */
    private function hasPermissions(Member $member, array|string|null $permissions): bool
    {
        if ($permissions === null || (is_array($permissions) && empty($permissions))) {
            return true;
        }

        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        foreach ($permissions as $perm) {
            $permName = $this->convertPermissionName($perm);

            // Map to Discord.php permission names
            $permMap = [
                'BanMembers' => 'ban_members',
                'KickMembers' => 'kick_members',
                'ManageMessages' => 'manage_messages',
                'ManageChannels' => 'manage_channels',
                'ManageGuild' => 'manage_guild',
                'Administrator' => 'administrator',
                'ModerateMembers' => 'moderate_members',
            ];

            $discordPerm = $permMap[$permName] ?? strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $permName));

            $rolePerms = $member->getPermissions();
            if (!isset($rolePerms[$discordPerm]) || !$rolePerms[$discordPerm]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if command works in DMs
     */
    public function isDMCommand(string $command): bool
    {
        $parsed = $this->parse($command);
        $cmdName = $parsed['command'];

        if (!$this->commandExists($cmdName)) {
            return false;
        }

        $about = $this->commands[$cmdName]->getAbout();

        return ($about['isDMPossible'] ?? false) === true && ($about['discord'] ?? true) === true;
    }

    /**
     * Execute a DM command
     */
    public function executeDM(Yuno $yuno, $author, string $command, Message $message): void
    {
        $parsed = $this->parse($command);
        $cmdName = $parsed['command'];

        if (!$this->commandExists($cmdName)) {
            return;
        }

        $cmd = $this->commands[$cmdName];
        $about = $cmd->getAbout();

        if (($about['isDMPossible'] ?? false) !== true || ($about['discord'] ?? true) !== true) {
            return;
        }

        if (($about['onlyMasterUsers'] ?? false) === true && !$this->isUserMaster($author->id)) {
            return;
        }

        $cmd->run($yuno, $author, $parsed['args'], $message);
    }

    /**
     * Execute a command
     */
    public function execute(Yuno $yuno, ?Member $source, string $commandStr, ?Message $message = null): void
    {
        if ($commandStr === '' && $source === null) {
            $this->prompt->info("Please at least, write something.");
            return;
        }

        $parsed = $this->parse($commandStr);
        $cmdName = $parsed['command'];

        if (!$this->commandExists($cmdName)) {
            if ($source === null) {
                $this->prompt->error("Command {$cmdName} doesn't exist!");
            }
            return;
        }

        $cmd = $this->commands[$cmdName];
        $about = $cmd->getAbout();

        // Check terminal access
        if (($about['terminal'] ?? true) === false && $source === null) {
            $this->prompt->error("The command {$cmdName} isn't accessible through terminal. Please use Discord's chat.");
            return;
        }

        // Check Discord access
        if (($about['discord'] ?? true) === false && $source !== null) {
            return;
        }

        // Check master users only
        if (($about['onlyMasterUsers'] ?? false) === true && $source !== null) {
            if (!$this->isUserMaster($source->id)) {
                return;
            }
        }

        // Check permissions
        if ($source === null ||
            $this->isUserMaster($source->id) ||
            $this->hasPermissions($source, $about['requiredPermissions'] ?? null)) {

            try {
                if ($source === null && method_exists($cmd, 'runTerminal')) {
                    $cmd->runTerminal($yuno, $parsed['args']);
                } else {
                    $cmd->run($yuno, $source, $parsed['args'], $message);
                }
            } catch (\Exception $e) {
                $this->prompt->error("Error executing command {$cmdName}", $e);
                throw $e;
            }
        } else {
            // Insufficient permissions
            if (($about['dangerous'] ?? false) === true && $message !== null) {
                // Auto-ban for dangerous command attempts
                $message->member->ban(86400, "User tried to execute a command for which they are underprivileged.");
            } elseif ($message !== null) {
                $msg = str_replace('${author}', "<@!{$source->id}>", $this->insufficientPermissionsMessage);
                $message->channel->sendMessage($msg);
            }
        }
    }

    /**
     * Get all commands
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get unique commands (no aliases)
     */
    public function getUniqueCommands(): array
    {
        $unique = [];
        $seen = [];

        foreach ($this->commands as $name => $command) {
            $about = $command->getAbout();
            $mainName = $about['command'] ?? $name;

            if (!in_array($mainName, $seen)) {
                $unique[$mainName] = $command;
                $seen[] = $mainName;
            }
        }

        return $unique;
    }
}
