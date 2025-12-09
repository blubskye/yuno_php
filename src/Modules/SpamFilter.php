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

/**
 * SpamFilter module - detects and handles spam
 */
class SpamFilter implements ModuleInterface
{
    private int $maxWarnings = 3;
    private array $spamEnabledGuilds = [];
    private array $warnings = []; // userId => warning count
    private ?Yuno $yuno = null;
    private bool $eventRegistered = false;

    // Discord invite regex
    private const DISCORD_INVITE_REGEX = '/(discord\.(gg|io|me|li)|discordapp\.com\/invite)\/[a-zA-Z0-9]+/i';

    // Multiple links regex
    private const MULTI_LINK_REGEX = '/(https?:\/\/[^\s]+)/i';

    public function getModuleName(): string
    {
        return 'spam-filter';
    }

    public function init(Yuno $yuno, bool $hotReloaded = false): void
    {
        $this->yuno = $yuno;
    }

    public function configLoaded(Yuno $yuno, Config $config): void
    {
        $maxWarnings = $config->get('spam.max-warnings');

        if (is_numeric($maxWarnings)) {
            $this->maxWarnings = (int)$maxWarnings;
        }
    }

    /**
     * Called when Discord is connected
     */
    public function discordConnected(Yuno $yuno): void
    {
        $this->yuno = $yuno;

        // Load spam-enabled guilds
        $this->spamEnabledGuilds = DatabaseCommands::getSpamFilterEnabled($yuno->database);

        if (!$this->eventRegistered) {
            $this->eventRegistered = true;

            $yuno->discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use ($yuno) {
                $this->handleMessage($message, $yuno);
            });
        }
    }

    /**
     * Reload spam filter guilds
     */
    public function reloadSpamFilter(): void
    {
        if ($this->yuno !== null) {
            $this->spamEnabledGuilds = DatabaseCommands::getSpamFilterEnabled($this->yuno->database);
        }
    }

    /**
     * Handle incoming messages for spam detection
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

        // Check if spam filter is enabled for this guild
        if (!($this->spamEnabledGuilds[$message->guild->id] ?? false)) {
            return;
        }

        // Check if user is a master user (exempt from spam filter)
        if ($yuno->commandMan->isUserMaster($message->author->id)) {
            return;
        }

        // Check if member has manage messages permission (exempt)
        $member = $message->member;
        if ($member !== null) {
            $perms = $member->getPermissions();
            if ($perms['manage_messages'] ?? false) {
                return;
            }
        }

        $content = $message->content;
        $isSpam = false;
        $reason = '';

        // Check for @everyone/@here abuse
        if ($message->mention_everyone) {
            $isSpam = true;
            $reason = 'Attempted @everyone/@here mention';
        }

        // Check for Discord invite links
        if (!$isSpam && preg_match(self::DISCORD_INVITE_REGEX, $content)) {
            $isSpam = true;
            $reason = 'Discord invite link';
        }

        // Check for multiple links (more than 3)
        if (!$isSpam) {
            preg_match_all(self::MULTI_LINK_REGEX, $content, $matches);
            if (count($matches[0]) > 3) {
                $isSpam = true;
                $reason = 'Too many links in message';
            }
        }

        if ($isSpam) {
            $this->handleSpam($message, $yuno, $reason);
        }
    }

    /**
     * Handle detected spam
     */
    private function handleSpam(Message $message, Yuno $yuno, string $reason): void
    {
        $userId = $message->author->id;
        $guildId = $message->guild->id;
        $key = "{$guildId}:{$userId}";

        // Delete the message
        $message->delete()->then(
            null,
            function (\Exception $e) use ($yuno) {
                $yuno->prompt->error("Failed to delete spam message", $e);
            }
        );

        // Increment warning count
        if (!isset($this->warnings[$key])) {
            $this->warnings[$key] = 0;
        }
        $this->warnings[$key]++;

        $warningCount = $this->warnings[$key];

        if ($warningCount >= $this->maxWarnings) {
            // Ban the user
            $message->guild->bans->ban($message->author->id, 1, "Spam filter: {$reason} (after {$warningCount} warnings)")->then(
                function () use ($message, $yuno, $reason) {
                    // Record mod action
                    DatabaseCommands::addModAction(
                        $yuno->database,
                        $message->guild->id,
                        $yuno->discord->user->id,
                        $message->author->id,
                        'ban',
                        "Spam filter: {$reason}",
                        (int)(microtime(true) * 1000)
                    );

                    $message->channel->sendMessage(":hammer: {$message->author->username} has been banned for spam ({$reason}).");

                    // Reset warnings
                    unset($this->warnings["{$message->guild->id}:{$message->author->id}"]);
                },
                function (\Exception $e) use ($yuno, $message) {
                    $yuno->prompt->error("Failed to ban spammer", $e);
                    $message->channel->sendMessage(":warning: Failed to ban spammer: {$e->getMessage()}");
                }
            );
        } else {
            // Send warning
            $remaining = $this->maxWarnings - $warningCount;
            $message->channel->sendMessage(
                ":warning: {$message->author}, your message was deleted ({$reason}). " .
                "Warning {$warningCount}/{$this->maxWarnings}. {$remaining} more warning(s) will result in a ban."
            );
        }
    }

    public function beforeShutdown(Yuno $yuno): void
    {
        $this->eventRegistered = false;
    }
}
