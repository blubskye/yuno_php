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
use Yuno\DatabaseCommands;

/**
 * ModStats command - show moderation statistics
 */
class ModStats extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        $stats = DatabaseCommands::getModStats($yuno->database, $message->guild->id);
        $totalActions = DatabaseCommands::getModActionsCount($yuno->database, $message->guild->id);

        // Build action counts string
        $actionCountsStr = '';
        foreach ($stats['actionCounts'] as $action) {
            $emoji = match ($action['action']) {
                'ban' => ':hammer:',
                'kick' => ':boot:',
                'unban' => ':unlock:',
                'timeout' => ':mute:',
                default => ':gear:',
            };
            $actionCountsStr .= "{$emoji} {$action['action']}: **{$action['count']}**\n";
        }

        if (empty($actionCountsStr)) {
            $actionCountsStr = 'No moderation actions recorded.';
        }

        // Build top moderators string
        $topModsStr = '';
        $rank = 1;
        foreach ($stats['topMods'] as $mod) {
            $modUser = $yuno->discord->users->get('id', $mod['moderatorId']);
            $modName = $modUser ? $modUser->username : "Unknown ({$mod['moderatorId']})";
            $topModsStr .= "**#{$rank}** {$modName} - {$mod['count']} actions\n";
            $rank++;
            if ($rank > 5) break;
        }

        if (empty($topModsStr)) {
            $topModsStr = 'No moderators recorded.';
        }

        $embed = new Embed($yuno->discord);
        $embed->setTitle(":bar_chart: Moderation Statistics")
              ->setColor(0xff51ff)
              ->addFieldValues('Total Actions', (string)$totalActions, true)
              ->addFieldValues('Action Breakdown', $actionCountsStr)
              ->addFieldValues('Top Moderators', $topModsStr)
              ->setFooter("Stats for {$message->guild->name}")
              ->setTimestamp();

        $this->setRequester($embed, $message->member);

        $builder = MessageBuilder::new()->addEmbed($embed);
        $message->channel->sendMessage($builder);
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'mod-stats',
            'description' => 'Show moderation statistics for this server.',
            'aliases' => ['modstats'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['BAN_MEMBERS'],
        ]);
    }
}
