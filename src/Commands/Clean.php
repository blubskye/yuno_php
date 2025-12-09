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

/**
 * Clean command - bulk delete messages
 */
class Clean extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (empty($args) || !is_numeric($args[0])) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Usage: `clean <number>` (1-100)");
            return;
        }

        $count = (int)$args[0];

        if ($count < 1 || $count > 100) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Please provide a number between 1 and 100.");
            return;
        }

        // Add 1 to include the command message itself
        $count++;

        // Fetch messages
        $message->channel->getMessageHistory(['limit' => $count])->then(
            function ($messages) use ($yuno, $message, $count) {
                if ($messages->count() === 0) {
                    $message->channel->sendMessage(":negative_squared_cross_mark: No messages to delete.");
                    return;
                }

                // Filter out messages older than 14 days (Discord limitation)
                $twoWeeksAgo = time() - (14 * 24 * 60 * 60);
                $deletableMessages = [];

                foreach ($messages as $msg) {
                    $msgTimestamp = $msg->timestamp->getTimestamp();
                    if ($msgTimestamp > $twoWeeksAgo) {
                        $deletableMessages[] = $msg;
                    }
                }

                if (empty($deletableMessages)) {
                    $message->channel->sendMessage(":negative_squared_cross_mark: All messages are older than 14 days and cannot be bulk deleted.");
                    return;
                }

                // Bulk delete
                $message->channel->deleteMessages($deletableMessages)->then(
                    function () use ($message, $deletableMessages) {
                        $deleted = count($deletableMessages);
                        $message->channel->sendMessage(":white_check_mark: Successfully deleted **{$deleted}** message(s).")->then(
                            function (Message $confirmMsg) {
                                // Delete the confirmation message after 3 seconds
                                $confirmMsg->channel->guild->discord->getLoop()->addTimer(3, function () use ($confirmMsg) {
                                    $confirmMsg->delete();
                                });
                            }
                        );
                    },
                    function (\Exception $e) use ($yuno, $message) {
                        $this->sendFail($yuno, $message, 'Clean failed.', "Failed to delete messages: {$e->getMessage()}");
                    }
                );
            },
            function (\Exception $e) use ($yuno, $message) {
                $this->sendFail($yuno, $message, 'Clean failed.', "Failed to fetch messages: {$e->getMessage()}");
            }
        );
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'clean',
            'description' => 'Bulk delete messages in the channel.',
            'aliases' => ['purge', 'clear'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['MANAGE_MESSAGES'],
            'examples' => [
                'clean 10',
                'clean 50',
                'clean 100'
            ],
        ]);
    }
}
