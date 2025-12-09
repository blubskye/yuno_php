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
 * Unban command - unban users from the server
 */
class Unban extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (empty($args)) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Please provide a user ID to unban.");
            return;
        }

        $reason = "Unbanned by " . $message->author->username;

        // Check for reason separator
        $argsStr = implode(' ', $args);
        if (str_contains($argsStr, '|')) {
            $parts = explode('|', $argsStr, 2);
            $argsStr = trim($parts[0]);
            $reason = trim($parts[1]) . " / Unbanned by " . $message->author->username;
        }

        // Parse user IDs
        $userIds = preg_split('/\s+/', $argsStr);

        foreach ($userIds as $userId) {
            $userId = Util::parseUserId($userId);

            if ($userId === null) {
                continue;
            }

            $message->guild->bans->unban($userId, $reason)->then(
                function () use ($yuno, $message, $userId, $reason) {
                    // Record to database
                    DatabaseCommands::addModAction(
                        $yuno->database,
                        $message->guild->id,
                        $message->author->id,
                        $userId,
                        'unban',
                        $reason,
                        (int)(microtime(true) * 1000)
                    );

                    $this->sendSuccess($yuno, $message, 'Unban successful.', ":arrow_right: User with ID `{$userId}` has been unbanned.");
                },
                function (\Exception $e) use ($yuno, $message, $userId) {
                    $this->sendFail($yuno, $message, 'Unban failed.', ":arrow_right: Failed to unban user `{$userId}`: {$e->getMessage()}");
                }
            );
        }
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'unban',
            'description' => 'Unbans users from the server.',
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['BAN_MEMBERS'],
            'examples' => [
                'unban 123456789012345678 | reason',
                'unban 123456789012345678'
            ],
        ]);
    }
}
