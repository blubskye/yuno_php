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

namespace Yuno\Commands;

use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Channel;
use Discord\Parts\User\Member;
use Discord\Parts\Embed\Embed;
use Discord\Builders\MessageBuilder;
use Yuno\Yuno;
use Yuno\Util;
use Yuno\DatabaseCommands;

/**
 * AutoClean command - manage automatic channel cleaning
 */
class AutoClean extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (empty($args)) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Not enough arguments.");
            return;
        }

        $subcommand = strtolower($args[0]);
        $channel = $message->mention_channels->first();

        switch ($subcommand) {
            case 'remove':
                $this->handleRemove($yuno, $message, $channel);
                break;

            case 'clean':
                // Redirect to clean command
                $yuno->commandMan->execute($yuno, $message->member, 'clean #dummy-id', $message);
                break;

            case 'reset':
                $this->handleReset($yuno, $message, $channel);
                break;

            case 'list':
                $this->handleList($yuno, $message, $channel);
                break;

            case 'delay':
                $this->handleDelay($yuno, $message, $channel, $args);
                break;

            case 'add':
            case 'edit':
            default:
                $this->handleAddEdit($yuno, $message, $channel, $args, $subcommand);
                break;
        }
    }

    private function handleRemove(Yuno $yuno, Message $message, ?Channel $channel): void
    {
        if ($channel === null) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Please mention a channel.");
            return;
        }

        $clean = DatabaseCommands::getClean($yuno->database, $message->guild->id, $channel->name);
        if ($clean === null) {
            $message->channel->sendMessage(":negative_squared_cross_mark: This channel doesn't have any auto-clean set up.");
            return;
        }

        DatabaseCommands::delClean($yuno->database, $message->guild->id, $channel->name);

        // Refresh the auto-cleaner module
        $autoCleanerModule = $yuno->getModule('auto-cleaner');
        if ($autoCleanerModule !== null && method_exists($autoCleanerModule, 'refreshCleaners')) {
            $autoCleanerModule->refreshCleaners();
        }

        $message->channel->sendMessage(":white_check_mark: The auto-clean has been removed.");
    }

    private function handleReset(Yuno $yuno, Message $message, ?Channel $channel): void
    {
        if ($channel === null) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Please mention a channel.");
            return;
        }

        $clean = DatabaseCommands::getClean($yuno->database, $message->guild->id, $channel->name);
        if ($clean === null) {
            $message->channel->sendMessage(":negative_squared_cross_mark: This channel doesn't have any auto-clean set up.");
            return;
        }

        DatabaseCommands::setClean(
            $yuno->database,
            $message->guild->id,
            $channel->name,
            $clean['timeFEachClean'],
            $clean['timeBeforeClean'],
            $clean['timeFEachClean'] * 60
        );

        $message->channel->sendMessage(":white_check_mark: Reset!");
    }

    private function handleList(Yuno $yuno, Message $message, ?Channel $channel): void
    {
        if ($channel !== null) {
            $clean = DatabaseCommands::getClean($yuno->database, $message->guild->id, $channel->name);
            if ($clean === null) {
                $message->channel->sendMessage(":negative_squared_cross_mark: The auto-clean doesn't exist.");
                return;
            }

            $embed = new Embed($yuno->discord);
            $embed->setColor(0xff51ff)
                  ->setTitle("#{$channel->name} auto-clean configuration.")
                  ->addFieldValues('Time between each clean', str_pad((string)$clean['timeFEachClean'], 2, '0', STR_PAD_LEFT) . 'h', true)
                  ->addFieldValues('Warning thrown at', Util::formatDuration($clean['timeBeforeClean'] * 60) . ' remaining', true)
                  ->addFieldValues('Remaining time before clean', Util::formatDuration($clean['remainingTime'] * 60), true);

            $builder = MessageBuilder::new()->addEmbed($embed);
            $message->channel->sendMessage($builder);
        } else {
            $cleans = DatabaseCommands::getCleans($yuno->database);
            $channels = [];

            foreach ($cleans as $clean) {
                if ($clean['guildId'] === $message->guild->id) {
                    $channels[] = '#' . $clean['channelName'];
                }
            }

            $listMessage = empty($channels) ? 'None.' : '``` ' . implode(', ', $channels) . ' ```';

            $embed = new Embed($yuno->discord);
            $embed->setColor(0xff51ff)
                  ->setTitle('Channels having an auto-clean:')
                  ->setDescription($listMessage);

            $builder = MessageBuilder::new()->addEmbed($embed);
            $message->channel->sendMessage($builder);
        }
    }

    private function handleDelay(Yuno $yuno, Message $message, ?Channel $channel, array $args): void
    {
        if ($channel === null) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Please mention a channel.");
            return;
        }

        $clean = DatabaseCommands::getClean($yuno->database, $message->guild->id, $channel->name);
        if ($clean === null) {
            $message->channel->sendMessage(":negative_squared_cross_mark: This channel doesn't have any auto-clean set up.");
            return;
        }

        $delayMinutes = isset($args[2]) ? (int)$args[2] : 0;
        if ($delayMinutes <= 0) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Please provide a positive number of minutes to delay.");
            return;
        }

        DatabaseCommands::setClean(
            $yuno->database,
            $message->guild->id,
            $channel->name,
            $clean['timeFEachClean'],
            $clean['timeBeforeClean'],
            $clean['remainingTime'] + $delayMinutes
        );

        $message->channel->sendMessage(":white_check_mark: Delayed the clean by {$delayMinutes} minutes!");
    }

    private function handleAddEdit(Yuno $yuno, Message $message, ?Channel $channel, array $args, string $subcommand): void
    {
        if ($channel === null) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Please mention a channel.");
            return;
        }

        // Parse arguments based on subcommand
        $offset = ($subcommand === 'add' || $subcommand === 'edit') ? 2 : 1;
        $betweenCleans = isset($args[$offset]) ? (int)$args[$offset] : null;
        $beforeWarning = isset($args[$offset + 1]) ? (int)$args[$offset + 1] : null;

        if ($betweenCleans === null || $beforeWarning === null) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Not enough arguments.");
            return;
        }

        if ($subcommand === 'add') {
            $existing = DatabaseCommands::getClean($yuno->database, $message->guild->id, $channel->name);
            if ($existing !== null) {
                $message->channel->sendMessage(":negative_squared_cross_mark: The channel already has an auto-clean. Use `auto-clean edit` instead.");
                return;
            }
        }

        // Validate values
        if ($betweenCleans <= 0 || $beforeWarning <= 0) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Between cleans and before warning cannot be negative or equal to 0.");
            return;
        }

        $maxIntervalMs = 2147483647;
        if ($betweenCleans * 60 * 60 * 1000 > $maxIntervalMs) {
            $maxHours = (int)($maxIntervalMs / (60 * 60 * 1000));
            $message->channel->sendMessage(":negative_squared_cross_mark: Between cleans must be less than {$maxHours} hours.");
            return;
        }

        if ($beforeWarning / 60 >= $betweenCleans) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Before warning cannot be equal or higher than between cleans.");
            return;
        }

        $result = DatabaseCommands::setClean($yuno->database, $message->guild->id, $channel->name, $betweenCleans, $beforeWarning, null);

        // Refresh the auto-cleaner module
        $autoCleanerModule = $yuno->getModule('auto-cleaner');
        if ($autoCleanerModule !== null && method_exists($autoCleanerModule, 'refreshCleaners')) {
            $autoCleanerModule->refreshCleaners();
        }

        $niceMessage = "<#{$channel->id}> will be cleaned every {$betweenCleans} hours and a warning will be thrown {$beforeWarning} minutes before.";

        if ($result[0] === 'creating') {
            $message->channel->sendMessage("Clean created!\n{$niceMessage}");
        } else {
            $message->channel->sendMessage("Clean updated!\n{$niceMessage}");
        }
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'auto-clean',
            'description' => "Manage automatic channel cleaning.\nadd - add a new auto-clean\nremove - delete an auto-clean\nedit - change delays\nreset - reset counter\ndelay - add time\nlist - list active auto-cleans",
            'aliases' => ['autoclean'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'onlyMasterUsers' => true,
            'examples' => [
                'auto-clean add #channel 2 15',
                'auto-clean remove #channel',
                'auto-clean list',
                'auto-clean list #channel',
                'auto-clean reset #channel',
                'auto-clean delay #channel 30'
            ],
        ]);
    }
}
