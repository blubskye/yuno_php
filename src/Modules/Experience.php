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

namespace Yuno\Modules;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use Yuno\Yuno;
use Yuno\Lib\Config;
use Yuno\DatabaseCommands;
use Yuno\Util;

/**
 * Experience module - tracks XP per message
 */
class Experience implements ModuleInterface
{
    private int $expPerMsg = 20;
    private array $xpEnabledGuilds = [];
    private ?Yuno $yuno = null;
    private bool $eventRegistered = false;

    public function getModuleName(): string
    {
        return 'experience';
    }

    public function init(Yuno $yuno, bool $hotReloaded = false): void
    {
        $this->yuno = $yuno;
    }

    public function configLoaded(Yuno $yuno, Config $config): void
    {
        $expPerMsg = $config->get('chat.exppermsg');

        if (is_numeric($expPerMsg)) {
            $this->expPerMsg = (int)$expPerMsg;
        }
    }

    /**
     * Called when Discord is connected
     */
    public function discordConnected(Yuno $yuno): void
    {
        $this->yuno = $yuno;

        // Load XP-enabled guilds
        $this->xpEnabledGuilds = DatabaseCommands::getGuildsWhereExpIsEnabled($yuno->database);

        if (!$this->eventRegistered) {
            $this->eventRegistered = true;

            $yuno->discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use ($yuno) {
                $this->handleMessage($message, $yuno);
            });
        }
    }

    /**
     * Handle incoming messages for XP tracking
     */
    private function handleMessage(Message $message, Yuno $yuno): void
    {
        // Ignore bot messages
        if ($message->author->bot) {
            return;
        }

        // Ignore DMs
        if ($message->guild === null) {
            return;
        }

        // Check if XP is enabled for this guild
        if (!in_array($message->guild->id, $this->xpEnabledGuilds)) {
            return;
        }

        // Get current XP data
        $xpData = DatabaseCommands::getXPData($yuno->database, $message->guild->id, $message->author->id);

        // Add XP
        $newXp = $xpData['xp'] + $this->expPerMsg;
        $newLevel = $xpData['level'];

        // Check for level up
        $xpNeededForNextLevel = Util::xpForLevel($newLevel);

        while ($newXp >= $xpNeededForNextLevel) {
            $newXp -= $xpNeededForNextLevel;
            $newLevel++;
            $xpNeededForNextLevel = Util::xpForLevel($newLevel);

            // Level up notification
            if ($newLevel > $xpData['level']) {
                $this->onLevelUp($message, $yuno, $newLevel);
            }
        }

        // Save new XP data
        DatabaseCommands::setXPData($yuno->database, $message->guild->id, $message->author->id, $newXp, $newLevel);

        // Check for role rewards
        $this->checkRoleRewards($message, $yuno, $newLevel);
    }

    /**
     * Handle level up
     */
    private function onLevelUp(Message $message, Yuno $yuno, int $newLevel): void
    {
        // Optionally send level up message
        // $message->channel->sendMessage(":tada: {$message->author} reached level **{$newLevel}**!");
    }

    /**
     * Check and apply role rewards
     */
    private function checkRoleRewards(Message $message, Yuno $yuno, int $level): void
    {
        $levelRoleMap = DatabaseCommands::getLevelRoleMap($yuno->database, $message->guild->id);

        if ($levelRoleMap === null) {
            return;
        }

        foreach ($levelRoleMap as $reqLevel => $roleId) {
            $reqLevel = (int)$reqLevel;

            if ($level >= $reqLevel) {
                // Check if user already has the role
                if (!$message->member->roles->has($roleId)) {
                    $role = $message->guild->roles->get('id', $roleId);

                    if ($role !== null) {
                        $message->member->addRole($role)->then(
                            function () use ($yuno, $role) {
                                $yuno->prompt->debug("Added role {$role->name} to user.");
                            },
                            function (\Exception $e) use ($yuno) {
                                $yuno->prompt->error("Failed to add role", $e);
                            }
                        );
                    }
                }
            }
        }
    }

    /**
     * Reload XP-enabled guilds list
     */
    public function reloadXpGuilds(): void
    {
        if ($this->yuno !== null) {
            $this->xpEnabledGuilds = DatabaseCommands::getGuildsWhereExpIsEnabled($this->yuno->database);
        }
    }

    public function beforeShutdown(Yuno $yuno): void
    {
        $this->eventRegistered = false;
    }
}
