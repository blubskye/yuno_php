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
use Yuno\DatabaseCommands;

/**
 * XP command - check user experience and level
 */
class Xp extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        // Check if XP is enabled for this guild
        $xpEnabledGuilds = DatabaseCommands::getGuildsWhereExpIsEnabled($yuno->database);
        if (!in_array($message->guild->id, $xpEnabledGuilds)) {
            $message->channel->sendMessage("Experience counting is __disabled__ on the server.");
            return;
        }

        // Determine target user
        $targetMember = $message->member;

        // Check for mentioned user
        if ($message->mentions->count() > 0) {
            $mentionedUser = $message->mentions->first();
            $targetMember = $message->guild->members->get('id', $mentionedUser->id);
        } elseif (!empty($args)) {
            // Check if first arg is a user ID
            $userId = Util::parseUserId($args[0]);
            if ($userId !== null) {
                $member = $message->guild->members->get('id', $userId);
                if ($member !== null) {
                    $targetMember = $member;
                } else {
                    $message->channel->sendMessage(":negative_squared_cross_mark: Cannot find the asked user. He's maybe not on the server :thinking: ?");
                    return;
                }
            }
        }

        // Check if target is a bot
        if ($targetMember->user->bot) {
            $message->channel->sendMessage(":robot: Bots don't have xp!");
            return;
        }

        // Get XP data
        $xpData = DatabaseCommands::getXPData($yuno->database, $message->guild->id, $targetMember->id);
        $neededExp = Util::xpForLevel($xpData['level']);
        $expToNextLevel = $neededExp - $xpData['xp'];

        // Create embed
        $embed = new Embed($yuno->discord);
        $embed->setAuthor($targetMember->displayname . "'s experience card", Util::getAvatarURL($targetMember->user))
              ->setColor(0xff51ff)
              ->addFieldValues('Current level', (string)$xpData['level'], true)
              ->addFieldValues('Current exp', (string)$xpData['xp'], true)
              ->addFieldValues('Exp needed until next level (' . ($xpData['level'] + 1) . ')', (string)$expToNextLevel);

        $builder = MessageBuilder::new()->addEmbed($embed);
        $message->channel->sendMessage($builder);
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'xp',
            'description' => 'Check user experience and level.',
            'aliases' => ['rank', 'level', 'exp'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'examples' => ['xp @mention', 'xp [id]', 'xp'],
        ]);
    }
}
