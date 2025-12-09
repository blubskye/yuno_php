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
use Yuno\Yuno;
use Yuno\Util;
use Yuno\DatabaseCommands;

/**
 * SetLevel command - set user's level manually
 */
class SetLevel extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (count($args) < 2) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Usage: `set-level @user <level>`");
            return;
        }

        // Get mentioned user
        if ($message->mentions->count() === 0) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Please mention a user.");
            return;
        }

        $targetUser = $message->mentions->first();

        // Find the level argument
        $level = null;
        foreach ($args as $arg) {
            if (is_numeric($arg)) {
                $level = (int)$arg;
                break;
            }
        }

        if ($level === null || $level < 0) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Please provide a valid level (0 or higher).");
            return;
        }

        // Calculate XP for this level
        $xp = 0;
        for ($i = 0; $i < $level; $i++) {
            $xp += Util::xpForLevel($i);
        }

        // Ensure user exists in XP table
        DatabaseCommands::getXPData($yuno->database, $message->guild->id, $targetUser->id);

        // Set the new level
        DatabaseCommands::setXPData($yuno->database, $message->guild->id, $targetUser->id, 0, $level);

        $this->sendSuccess(
            $yuno,
            $message,
            'Level Set',
            "{$targetUser->username}'s level has been set to **{$level}**."
        );
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'set-level',
            'description' => 'Set a user\'s level manually.',
            'aliases' => ['setlevel'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['MANAGE_GUILD'],
            'examples' => [
                'set-level @user 10',
                'set-level @user 0'
            ],
        ]);
    }
}
