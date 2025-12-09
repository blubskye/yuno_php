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
 * InitGuild command - initialize guild in database
 */
class InitGuild extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        DatabaseCommands::initGuild($yuno->database, $message->guild->id);

        $this->sendSuccess(
            $yuno,
            $message,
            'Guild Initialized',
            "Guild **{$message->guild->name}** has been initialized in the database."
        );
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'init-guild',
            'description' => 'Initialize this guild in the database.',
            'aliases' => ['initguild'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['MANAGE_GUILD'],
        ]);
    }
}
