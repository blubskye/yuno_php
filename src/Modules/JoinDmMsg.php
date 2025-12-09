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
use Discord\Parts\User\Member;
use Discord\Parts\Embed\Embed;
use Discord\Builders\MessageBuilder;
use Discord\WebSockets\Event;
use Yuno\Yuno;
use Yuno\Lib\Config;
use Yuno\DatabaseCommands;

/**
 * JoinDmMsg module - sends DM to new members
 */
class JoinDmMsg implements ModuleInterface
{
    private array $joinMessages = [];
    private array $joinTitles = [];
    private ?Yuno $yuno = null;
    private bool $eventRegistered = false;

    public function getModuleName(): string
    {
        return 'join-dm-msg';
    }

    public function init(Yuno $yuno, bool $hotReloaded = false): void
    {
        $this->yuno = $yuno;
    }

    public function configLoaded(Yuno $yuno, Config $config): void
    {
    }

    /**
     * Called when Discord is connected
     */
    public function discordConnected(Yuno $yuno): void
    {
        $this->yuno = $yuno;

        // Load join DM messages from database
        $this->joinMessages = DatabaseCommands::getJoinDMMessages($yuno->database);
        $this->joinTitles = DatabaseCommands::getJoinDMMessagesTitles($yuno->database);

        if (!$this->eventRegistered) {
            $this->eventRegistered = true;

            $yuno->discord->on(Event::GUILD_MEMBER_ADD, function (Member $member, Discord $discord) use ($yuno) {
                $this->handleMemberJoin($member, $yuno);
            });
        }
    }

    /**
     * Reload join messages
     */
    public function reloadJoinMessages(): void
    {
        if ($this->yuno !== null) {
            $this->joinMessages = DatabaseCommands::getJoinDMMessages($this->yuno->database);
            $this->joinTitles = DatabaseCommands::getJoinDMMessagesTitles($this->yuno->database);
        }
    }

    /**
     * Handle a new member joining
     */
    private function handleMemberJoin(Member $member, Yuno $yuno): void
    {
        $guildId = $member->guild_id;
        $message = $this->joinMessages[$guildId] ?? null;
        $title = $this->joinTitles[$guildId] ?? null;

        $send = false;
        $embed = new Embed($yuno->discord);
        $embed->setColor(0xff7ab3);

        if (is_string($title) && $title !== 'null' && !empty($title)) {
            $embed->setTitle($title);
            $send = true;
        }

        if (is_string($message) && $message !== 'null' && !empty($message)) {
            $embed->setDescription($message);
            $send = true;
        }

        if ($send) {
            $builder = MessageBuilder::new()->addEmbed($embed);

            // Send DM to new member
            $member->user->sendMessage($builder)->then(
                null,
                function (\Exception $e) use ($yuno, $member) {
                    // Code 50007 means cannot send DM to user (DMs disabled)
                    if (strpos($e->getMessage(), '50007') !== false) {
                        $yuno->prompt->warn("Failed to send join DM to {$member->user->username}: DMs disabled");
                    } else {
                        $yuno->prompt->error("Failed to send join DM to {$member->user->username}", $e);
                    }
                }
            );
        }
    }

    public function beforeShutdown(Yuno $yuno): void
    {
        $this->eventRegistered = false;
    }
}
