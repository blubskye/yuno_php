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
 * SetJoinMessage command - set DM message for new members
 */
class SetJoinMessage extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (empty($args)) {
            // Show current message
            $messages = DatabaseCommands::getJoinDMMessages($yuno->database);
            $currentMsg = $messages[$message->guild->id] ?? null;

            if ($currentMsg === null) {
                $this->sendSuccess($yuno, $message, 'Join Message', "No join DM message is set for this server.");
            } else {
                $this->sendSuccess($yuno, $message, 'Current Join Message', "```\n{$currentMsg}\n```");
            }
            return;
        }

        $newMessage = implode(' ', $args);

        // Check for "off" or "disable"
        if (strtolower($newMessage) === 'off' || strtolower($newMessage) === 'disable' || strtolower($newMessage) === 'none') {
            DatabaseCommands::setJoinDMMessage($yuno->database, $message->guild->id, '');
            $this->sendSuccess($yuno, $message, 'Join Message Disabled', "Join DM messages have been disabled.");
            return;
        }

        DatabaseCommands::setJoinDMMessage($yuno->database, $message->guild->id, $newMessage);

        $this->sendSuccess($yuno, $message, 'Join Message Set', "New members will receive this DM:\n```\n{$newMessage}\n```");
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'set-joinmessage',
            'description' => 'Set the DM message sent to new members.',
            'aliases' => ['joinmsg', 'welcomemsg'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['MANAGE_GUILD'],
            'examples' => [
                'set-joinmessage Welcome to our server!',
                'set-joinmessage off',
                'set-joinmessage'
            ],
        ]);
    }
}
