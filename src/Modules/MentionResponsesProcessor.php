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
use Discord\Parts\Embed\Embed;
use Discord\Builders\MessageBuilder;
use Discord\WebSockets\Event;
use Yuno\Yuno;
use Yuno\Lib\Config;
use Yuno\DatabaseCommands;

/**
 * MentionResponses processor module - responds to custom triggers when bot is mentioned
 */
class MentionResponsesProcessor implements ModuleInterface
{
    private array $mentionResponses = [];
    private ?Yuno $yuno = null;
    private bool $eventRegistered = false;

    public function getModuleName(): string
    {
        return 'mention-responses-processor';
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

        // Load mention responses from database
        $this->mentionResponses = DatabaseCommands::getMentionResponses($yuno->database);

        if (!$this->eventRegistered) {
            $this->eventRegistered = true;

            $yuno->discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use ($yuno) {
                $this->handleMessage($message, $yuno);
            });
        }
    }

    /**
     * Reload mention responses
     */
    public function reloadMentionResponses(): void
    {
        if ($this->yuno !== null) {
            $this->mentionResponses = DatabaseCommands::getMentionResponses($this->yuno->database);
        }
    }

    /**
     * Handle incoming messages for mention responses
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

        // Check if bot is mentioned
        $botMentioned = false;
        foreach ($message->mentions as $user) {
            if ($user->id === $yuno->discord->user->id) {
                $botMentioned = true;
                break;
            }
        }

        if (!$botMentioned) {
            return;
        }

        // Get content without bot mention
        $botMention = '<@' . $yuno->discord->user->id . '>';
        $botMentionNick = '<@!' . $yuno->discord->user->id . '>';
        $content = str_replace([$botMention, $botMentionNick], '', $message->content);
        $content = strtolower(trim($content));

        // Check for matching triggers
        foreach ($this->mentionResponses as $response) {
            if ($message->guild->id !== $response['guildId']) {
                continue;
            }

            $trigger = strtolower(trim($response['trigger']));
            if (strpos($content, $trigger) !== false) {
                $this->sendResponse($message, $yuno, $response);
                break;
            }
        }
    }

    /**
     * Send a mention response
     */
    private function sendResponse(Message $message, Yuno $yuno, array $response): void
    {
        // Replace $author placeholder with author's username
        $responseText = str_ireplace('$author', $message->author->username, $response['response']);

        $embed = new Embed($yuno->discord);
        $embed->setDescription($responseText)
              ->setColor(0xff51ff);

        // Add image if present
        if ($response['image'] !== null && $response['image'] !== 'null' && !empty($response['image'])) {
            $embed->setImage($response['image']);
        }

        $builder = MessageBuilder::new()->addEmbed($embed);
        $message->channel->sendMessage($builder);
    }

    public function beforeShutdown(Yuno $yuno): void
    {
        $this->eventRegistered = false;
    }
}
