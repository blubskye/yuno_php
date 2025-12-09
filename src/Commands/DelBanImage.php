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
 * DelBanImage command - delete custom ban image
 */
class DelBanImage extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        DatabaseCommands::delBanImage($yuno->database, $message->guild->id, $message->author->id);

        $this->sendSuccess($yuno, $message, 'Ban Image Deleted', "Your custom ban image has been removed. The default image will be used.");
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'del-banimage',
            'description' => 'Delete your custom ban image.',
            'aliases' => ['delbanimage', 'removebanimage'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['BAN_MEMBERS'],
        ]);
    }
}
