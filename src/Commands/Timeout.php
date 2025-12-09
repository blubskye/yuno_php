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
 * Timeout command - timeout users (Discord's native timeout feature)
 */
class Timeout extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (count($args) < 2) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Usage: `timeout @user <duration> [reason]`\nDuration examples: `10m`, `1h`, `1d`, `1h30m`");
            return;
        }

        // Get mentioned user
        if ($message->mentions->count() === 0) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Please mention a user to timeout.");
            return;
        }

        $targetUser = $message->mentions->first();
        $targetMember = $message->guild->members->get('id', $targetUser->id);

        if ($targetMember === null) {
            $this->sendFail($yuno, $message, 'Timeout failed.', "User is not in the server.");
            return;
        }

        // Check if target is a master user
        if ($yuno->commandMan->isUserMaster($targetUser->id)) {
            $this->sendFail($yuno, $message, 'Timeout failed.', "Cannot timeout a master user.");
            return;
        }

        // Parse duration - find the duration argument (skip mentions)
        $durationStr = null;
        $reasonParts = [];
        $foundDuration = false;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '<@')) {
                continue;
            }
            if (!$foundDuration && Util::parseDuration($arg) !== null) {
                $durationStr = $arg;
                $foundDuration = true;
            } else {
                $reasonParts[] = $arg;
            }
        }

        if ($durationStr === null) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Please provide a valid duration (e.g., `10m`, `1h`, `1d`).");
            return;
        }

        $durationSeconds = Util::parseDuration($durationStr);

        // Discord timeout max is 28 days
        if ($durationSeconds > 28 * 24 * 60 * 60) {
            $this->sendFail($yuno, $message, 'Timeout failed.', "Timeout duration cannot exceed 28 days.");
            return;
        }

        $reason = !empty($reasonParts)
            ? implode(' ', $reasonParts) . " / Timed out by " . $message->author->username
            : "Timed out by " . $message->author->username;

        // Calculate timeout end time
        $timeoutUntil = new \DateTime();
        $timeoutUntil->add(new \DateInterval('PT' . $durationSeconds . 'S'));

        // Apply timeout
        $targetMember->timeoutMember($timeoutUntil, $reason)->then(
            function () use ($yuno, $message, $targetUser, $durationStr, $reason) {
                // Record to database
                DatabaseCommands::addModAction(
                    $yuno->database,
                    $message->guild->id,
                    $message->author->id,
                    $targetUser->id,
                    'timeout',
                    $reason,
                    (int)(microtime(true) * 1000)
                );

                $this->sendSuccess(
                    $yuno,
                    $message,
                    'Timeout successful.',
                    ":arrow_right: {$targetUser->username} has been timed out for **{$durationStr}**."
                );
            },
            function (\Exception $e) use ($yuno, $message, $targetUser) {
                $this->sendFail($yuno, $message, 'Timeout failed.', ":arrow_right: Failed to timeout {$targetUser->username}: {$e->getMessage()}");
            }
        );
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'timeout',
            'description' => 'Timeout a user for a specified duration.',
            'aliases' => ['mute', 'to'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['MODERATE_MEMBERS'],
            'examples' => [
                'timeout @user 10m',
                'timeout @user 1h spam',
                'timeout @user 1d being annoying'
            ],
        ]);
    }
}
