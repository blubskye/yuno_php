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
 * Ban command - ban users from the server
 */
class Ban extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        $argsStr = implode(' ', $args);
        $reason = "Banned by " . $message->author->username;

        // Check for reason separator
        if (str_contains($argsStr, '|')) {
            $parts = explode('|', $argsStr, 2);
            $argsStr = trim($parts[0]);
            $reason = trim($parts[1]) . " / Banned by " . $message->author->username;
        }

        // Get users to ban from mentions
        $usersToBan = [];

        foreach ($message->mentions as $user) {
            $inGuild = $message->guild->members->has($user->id);
            $usersToBan[] = [
                'user' => $user,
                'inGuild' => $inGuild
            ];
        }

        // Process ID arguments
        $argParts = preg_split('/\s+/', $argsStr);
        foreach ($argParts as $part) {
            $part = trim($part);
            if (empty($part) || str_starts_with($part, '<')) {
                continue;
            }

            // Try to parse as user ID
            if (preg_match('/^\d{17,20}$/', $part)) {
                // Try to fetch from guild
                $member = $message->guild->members->get('id', $part);

                if ($member !== null) {
                    $usersToBan[] = [
                        'user' => $member->user,
                        'inGuild' => true
                    ];
                } else {
                    // Try to fetch user from Discord API
                    $yuno->discord->users->fetch($part)->then(
                        function ($user) use (&$usersToBan) {
                            $usersToBan[] = [
                                'user' => $user,
                                'inGuild' => false
                            ];
                        },
                        function () use ($message, $yuno, $part) {
                            $this->sendFail($yuno, $message, 'Ban failed.', ":arrow_right: Failed to ban user with ID `{$part}`: User not found on Discord.");
                        }
                    );
                }
            }
        }

        if (empty($usersToBan)) {
            $message->channel->sendMessage(":negative_squared_cross_mark: No users to ban. Please mention users or provide user IDs.");
            return;
        }

        // Ban each user
        foreach ($usersToBan as $targetData) {
            $target = $targetData['user'];
            $inGuild = $targetData['inGuild'];

            // Check if target is a master user
            if ($yuno->commandMan->isUserMaster($target->id)) {
                $this->sendFail($yuno, $message, 'Ban failed.', ":arrow_right: Failed to ban user {$target->username}. The user is on the master list.");
                continue;
            }

            // Get ban image
            $banImage = DatabaseCommands::getBanImage($yuno->database, $message->guild->id, $message->author->id);
            if ($banImage === null) {
                $banImage = $yuno->config->get('ban.default-image');
            }

            // Execute ban
            $message->guild->bans->ban($target->id, 1, $reason)->then(
                function () use ($yuno, $message, $target, $inGuild, $reason, $banImage) {
                    // Record to database
                    DatabaseCommands::addModAction(
                        $yuno->database,
                        $message->guild->id,
                        $message->author->id,
                        $target->id,
                        'ban',
                        $reason,
                        (int)(microtime(true) * 1000)
                    );

                    $embed = $this->successEmbed(
                        $yuno,
                        'Ban successful.',
                        ":arrow_right: User {$target->username} has been successfully banned." . ($inGuild ? '' : ' (User was not in server)')
                    );
                    $this->setRequester($embed, $message->member);

                    if ($banImage !== null && Util::checkIfUrl($banImage)) {
                        $embed->setImage($banImage);
                    }

                    $this->sendEmbed($message, $embed);
                },
                function (\Exception $e) use ($yuno, $message, $target) {
                    $this->sendFail($yuno, $message, 'Ban failed.', ":arrow_right: Failed to ban {$target->username}: {$e->getMessage()}");
                }
            );
        }
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'ban',
            'description' => 'Bans users from the server. Works with mentions, user IDs (in server), and user IDs (not in server).',
            'aliases' => ['bean', 'banne'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['BAN_MEMBERS'],
            'dangerous' => true,
            'examples' => [
                'ban @someone | reason',
                'ban 123456789012345678 | spam',
                'ban @user1 @user2 123456789012345678 | multiple users',
                'ban 123456789012345678'
            ],
        ]);
    }
}
