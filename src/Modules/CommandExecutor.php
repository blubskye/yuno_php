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
 * Command executor module - routes messages to the command system
 */
class CommandExecutor implements ModuleInterface
{
    private ?string $workOnlyOnGuild = null;
    private string $defaultPrefix = '.';
    private array $prefixes = [];
    private string $dmMessage = "I'm just a bot :'(";
    private ?Yuno $yuno = null;
    private bool $eventRegistered = false;

    public function getModuleName(): string
    {
        return 'command-executor';
    }

    public function init(Yuno $yuno, bool $hotReloaded = false): void
    {
        $this->yuno = $yuno;
    }

    public function configLoaded(Yuno $yuno, Config $config): void
    {
        $workOnlyOnGuild = $config->get('debug.work-only-on-guild');
        $defaultPrefix = $config->get('commands.default-prefix');
        $dmMessage = $config->get('chat.dm');

        if (is_string($workOnlyOnGuild) && $workOnlyOnGuild !== '') {
            $this->workOnlyOnGuild = $workOnlyOnGuild;
        }

        if (is_string($defaultPrefix)) {
            $this->defaultPrefix = $defaultPrefix;
        }

        if (is_string($dmMessage)) {
            $this->dmMessage = $dmMessage;
        }
    }

    /**
     * Called when Discord is connected
     */
    public function discordConnected(Yuno $yuno): void
    {
        $this->yuno = $yuno;

        // Load guild prefixes from database
        $this->prefixes = DatabaseCommands::getPrefixes($yuno->database);

        if (!$this->eventRegistered) {
            $this->eventRegistered = true;

            $yuno->discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use ($yuno) {
                $this->handleMessage($message, $yuno);
            });
        }
    }

    /**
     * Handle incoming messages
     */
    private function handleMessage(Message $message, Yuno $yuno): void
    {
        // Ignore bot's own messages
        if ($message->author->id === $yuno->discord->user->id) {
            return;
        }

        // Handle DMs
        if ($message->guild === null) {
            $this->handleDM($message, $yuno);
            return;
        }

        // Check work-only-on-guild restriction
        if ($this->workOnlyOnGuild !== null && $message->guild->id !== $this->workOnlyOnGuild) {
            return;
        }

        $content = $message->content;
        $guildPrefix = $this->prefixes[$message->guild->id] ?? $this->defaultPrefix;

        // Check if bot is mentioned
        $botMentionPattern = '/<@!?' . $yuno->discord->user->id . '>/';
        if (preg_match($botMentionPattern, $content) && !$message->mention_everyone) {
            $contentWithoutMention = trim(preg_replace($botMentionPattern, '', $content));

            // If just a mention or mention with delay-like words, run delay command
            if ($contentWithoutMention === '' ||
                in_array(strtolower($contentWithoutMention), ['delay', 'wait', 'hold'])) {
                $yuno->commandMan->execute($yuno, $message->member, 'delay', $message);
                return;
            }
        }

        // Check for command prefix
        if (str_starts_with($content, $guildPrefix)) {
            $command = substr($content, strlen($guildPrefix));
            $yuno->commandMan->execute($yuno, $message->member, $command, $message);
        }
    }

    /**
     * Handle DM messages
     */
    private function handleDM(Message $message, Yuno $yuno): void
    {
        $content = $message->content;

        // Check if it's a DM command
        $command = str_starts_with($content, $this->defaultPrefix)
            ? substr($content, strlen($this->defaultPrefix))
            : $content;

        if ($yuno->commandMan->isDMCommand($command)) {
            $yuno->commandMan->executeDM($yuno, $message->author, $command, $message);
        } else {
            $message->reply($this->dmMessage . "\nYou can also send !source(s) to get the sources of the bot.");
        }
    }

    public function beforeShutdown(Yuno $yuno): void
    {
        $this->eventRegistered = false;
    }
}
