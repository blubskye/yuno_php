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
use Yuno\DatabaseCommands;

/**
 * Kick command - kick users from the server
 */
class Kick extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        $argsStr = implode(' ', $args);
        $reason = "Kicked by " . $message->author->username;

        // Check for reason separator
        if (str_contains($argsStr, '|')) {
            $parts = explode('|', $argsStr, 2);
            $argsStr = trim($parts[0]);
            $reason = trim($parts[1]) . " / Kicked by " . $message->author->username;
        }

        // Get users to kick from mentions
        if ($message->mentions->count() === 0) {
            $message->channel->sendMessage(":negative_squared_cross_mark: No users to kick. Please mention users.");
            return;
        }

        foreach ($message->mentions as $user) {
            $member = $message->guild->members->get('id', $user->id);

            if ($member === null) {
                $this->sendFail($yuno, $message, 'Kick failed.', ":arrow_right: User {$user->username} is not in the server.");
                continue;
            }

            // Check if target is a master user
            if ($yuno->commandMan->isUserMaster($user->id)) {
                $this->sendFail($yuno, $message, 'Kick failed.', ":arrow_right: Failed to kick user {$user->username}. The user is on the master list.");
                continue;
            }

            // Execute kick
            $member->kick($reason)->then(
                function () use ($yuno, $message, $user, $reason) {
                    // Record to database
                    DatabaseCommands::addModAction(
                        $yuno->database,
                        $message->guild->id,
                        $message->author->id,
                        $user->id,
                        'kick',
                        $reason,
                        (int)(microtime(true) * 1000)
                    );

                    $this->sendSuccess($yuno, $message, 'Kick successful.', ":arrow_right: User {$user->username} has been kicked.");
                },
                function (\Exception $e) use ($yuno, $message, $user) {
                    $this->sendFail($yuno, $message, 'Kick failed.', ":arrow_right: Failed to kick {$user->username}: {$e->getMessage()}");
                }
            );
        }
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'kick',
            'description' => 'Kicks users from the server.',
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['KICK_MEMBERS'],
            'dangerous' => true,
            'examples' => [
                'kick @someone | reason',
                'kick @user1 @user2 | multiple users'
            ],
        ]);
    }
}
