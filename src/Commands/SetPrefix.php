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
 * SetPrefix command - change the bot's prefix for this guild
 */
class SetPrefix extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (empty($args)) {
            $currentPrefix = DatabaseCommands::getPrefixes($yuno->database)[$message->guild->id]
                ?? $yuno->config->get('commands.default-prefix');

            $this->sendSuccess($yuno, $message, 'Current Prefix', "The current prefix is: `{$currentPrefix}`");
            return;
        }

        $newPrefix = $args[0];

        if (strlen($newPrefix) > 5) {
            $this->sendFail($yuno, $message, 'Prefix too long', "The prefix cannot be longer than 5 characters.");
            return;
        }

        DatabaseCommands::setPrefix($yuno->database, $message->guild->id, $newPrefix);

        $this->sendSuccess($yuno, $message, 'Prefix changed', "The prefix has been changed to: `{$newPrefix}`");
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'set-prefix',
            'description' => 'Change the command prefix for this server.',
            'aliases' => ['prefix'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['MANAGE_GUILD'],
            'examples' => ['set-prefix !', 'set-prefix .'],
        ]);
    }
}
