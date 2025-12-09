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

use Discord\Parts\Channel\Channel;
use Discord\Parts\Embed\Embed;
use Discord\Builders\MessageBuilder;
use React\EventLoop\TimerInterface;
use Yuno\Yuno;
use Yuno\Lib\Config;
use Yuno\DatabaseCommands;
use Yuno\Util;

/**
 * AutoCleaner module - automatically cleans channels on a schedule
 */
class AutoCleaner implements ModuleInterface
{
    private ?Yuno $yuno = null;
    private array $timers = []; // channelKey => TimerInterface

    public function getModuleName(): string
    {
        return 'auto-cleaner';
    }

    public function init(Yuno $yuno, bool $hotReloaded = false): void
    {
        $this->yuno = $yuno;

        if ($hotReloaded) {
            $this->setupCleaners($yuno);
        }
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
        $this->setupCleaners($yuno);
    }

    /**
     * Setup all auto-cleaners from database
     */
    private function setupCleaners(Yuno $yuno): void
    {
        $cleans = DatabaseCommands::getCleans($yuno->database);

        foreach ($cleans as $clean) {
            $guild = $yuno->discord->guilds->get('id', $clean['guildId']);
            if ($guild === null) {
                $yuno->prompt->error("Cannot (auto-)clean a channel: guild doesn't exist! GuildId: " . $clean['guildId']);
                continue;
            }

            $timerKey = "autocleaner-{$clean['guildId']}-{$clean['channelName']}";

            // Skip if already set up
            if (isset($this->timers[$timerKey])) {
                continue;
            }

            // Set up periodic timer (every 60 seconds)
            $this->timers[$timerKey] = $yuno->discord->getLoop()->addPeriodicTimer(60, function () use ($yuno, $clean, $guild, $timerKey) {
                $this->processClean($yuno, $clean, $guild, $timerKey);
            });
        }
    }

    /**
     * Process a single clean cycle
     */
    private function processClean(Yuno $yuno, array $originalClean, $guild, string $timerKey): void
    {
        // Get current clean data from database (may have changed)
        $clean = DatabaseCommands::getClean($yuno->database, $originalClean['guildId'], $originalClean['channelName']);
        if ($clean === null) {
            // Clean was removed, cancel timer
            $this->cancelTimer($timerKey);
            return;
        }

        // Find the channel
        $channel = null;
        foreach ($guild->channels as $ch) {
            if (strtolower($ch->name) === strtolower($originalClean['channelName'])) {
                $channel = $ch;
                break;
            }
        }

        if ($channel === null || $channel->type !== Channel::TYPE_TEXT) {
            $yuno->prompt->error("Cannot (auto-)clean a channel: channel doesn't exist! Guild: {$guild->name}; Channel: {$originalClean['channelName']}");
            DatabaseCommands::delClean($yuno->database, $originalClean['guildId'], $originalClean['channelName']);
            $this->cancelTimer($timerKey);
            return;
        }

        // Check if we should send warning
        if ($clean['remainingTime'] === $clean['timeBeforeClean']) {
            $embed = new Embed($yuno->discord);
            $embed->setAuthor("Yuno is going to clean this channel in {$clean['timeBeforeClean']} minutes. Speak now or forever hold your peace.");

            $builder = MessageBuilder::new()->addEmbed($embed);
            $channel->sendMessage($builder);
        }

        // Check if we should clean
        if ($clean['remainingTime'] <= 0) {
            // Clean the channel
            $this->cleanChannel($channel, $yuno)->then(
                function ($newChannel) use ($yuno, $clean, $originalClean) {
                    // Send completion message
                    $embed = new Embed($yuno->discord);
                    $embed->setImage("https://vignette3.wikia.nocookie.net/futurediary/images/9/94/Mirai_Nikki_-_06_-_Large_05.jpg")
                          ->setAuthor("Auto-clean: Yuno is done cleaning.", $yuno->discord->user->avatar)
                          ->setColor(0xff51ff);

                    $builder = MessageBuilder::new()->addEmbed($embed);
                    $newChannel->sendMessage($builder);

                    // Reset remaining time
                    DatabaseCommands::setClean(
                        $yuno->database,
                        $originalClean['guildId'],
                        $originalClean['channelName'],
                        $clean['timeFEachClean'],
                        $clean['timeBeforeClean'],
                        $clean['timeFEachClean'] * 60
                    );
                },
                function (\Exception $e) use ($yuno) {
                    $yuno->prompt->error("Failed to clean channel", $e);
                }
            );
        } else {
            // Decrement remaining time
            DatabaseCommands::setClean(
                $yuno->database,
                $originalClean['guildId'],
                $originalClean['channelName'],
                $clean['timeFEachClean'],
                $clean['timeBeforeClean'],
                $clean['remainingTime'] - 1
            );
        }
    }

    /**
     * Clean a channel by cloning it and deleting the original
     */
    private function cleanChannel(Channel $channel, Yuno $yuno)
    {
        return Util::cleanChannel($channel, $yuno->discord);
    }

    /**
     * Cancel a timer
     */
    private function cancelTimer(string $timerKey): void
    {
        if (isset($this->timers[$timerKey]) && $this->yuno !== null) {
            $this->yuno->discord->getLoop()->cancelTimer($this->timers[$timerKey]);
            unset($this->timers[$timerKey]);
        }
    }

    /**
     * Refresh all cleaners (called after changes)
     */
    public function refreshCleaners(): void
    {
        if ($this->yuno !== null) {
            $this->setupCleaners($this->yuno);
        }
    }

    public function beforeShutdown(Yuno $yuno): void
    {
        // Cancel all timers
        foreach (array_keys($this->timers) as $timerKey) {
            $this->cancelTimer($timerKey);
        }
        $this->timers = [];
    }
}
