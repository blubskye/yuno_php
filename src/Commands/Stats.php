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
use Discord\Parts\User\Member;
use Discord\Parts\Embed\Embed;
use Discord\Builders\MessageBuilder;
use Yuno\Yuno;
use Yuno\Util;

/**
 * Stats command - show bot statistics
 */
class Stats extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            $this->runTerminal($yuno, $args);
            return;
        }

        $uptime = time() - $_SERVER['REQUEST_TIME'];
        $memUsage = memory_get_usage(true);
        $memPeak = memory_get_peak_usage(true);

        $guildCount = $yuno->discord->guilds->count();
        $userCount = 0;
        foreach ($yuno->discord->guilds as $guild) {
            $userCount += $guild->member_count;
        }

        $embed = new Embed($yuno->discord);
        $embed->setTitle(":bar_chart: Bot Statistics")
              ->setColor(0xff51ff)
              ->addFieldValues('Version', $yuno->version, true)
              ->addFieldValues('PHP Version', PHP_VERSION, true)
              ->addFieldValues('DiscordPHP', \Discord\Discord::VERSION ?? 'Unknown', true)
              ->addFieldValues('Uptime', Util::formatDuration($uptime), true)
              ->addFieldValues('Guilds', (string)$guildCount, true)
              ->addFieldValues('Users', (string)$userCount, true)
              ->addFieldValues('Memory Usage', Util::formatBytes($memUsage), true)
              ->addFieldValues('Peak Memory', Util::formatBytes($memPeak), true)
              ->addFieldValues('Commands Loaded', (string)count($yuno->commandMan->getUniqueCommands()), true)
              ->setFooter("Yuno Gasai v{$yuno->version} - PHP Port")
              ->setTimestamp();

        $builder = MessageBuilder::new()->addEmbed($embed);
        $message->channel->sendMessage($builder);
    }

    public function runTerminal(Yuno $yuno, array $args): void
    {
        $uptime = time() - $_SERVER['REQUEST_TIME'];
        $memUsage = memory_get_usage(true);

        $yuno->prompt->info("Yuno v{$yuno->version}");
        $yuno->prompt->info("PHP " . PHP_VERSION);
        $yuno->prompt->info("Uptime: " . Util::formatDuration($uptime));
        $yuno->prompt->info("Memory: " . Util::formatBytes($memUsage));

        if ($yuno->discord !== null) {
            $yuno->prompt->info("Guilds: " . $yuno->discord->guilds->count());
        }
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'stats',
            'description' => 'Show bot statistics.',
            'discord' => true,
            'terminal' => true,
            'list' => true,
            'listTerminal' => true,
            'isDMPossible' => true,
        ]);
    }
}
