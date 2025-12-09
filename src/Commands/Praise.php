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
 * Praise command - praise a user with a cute image
 */
class Praise extends BaseCommand
{
    private const PRAISE_IMAGES = [
        'https://media.giphy.com/media/ny8mlxWio6WBi/giphy.gif'
    ];

    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if ($message->mentions->count() === 0) {
            $message->channel->sendMessage("Who do you want me to praise?");
            return;
        }

        $mentionedUser = $message->mentions->first();
        $image = self::PRAISE_IMAGES[array_rand(self::PRAISE_IMAGES)];

        $message->channel->sendMessage("<@{$mentionedUser->id}> {$image}");
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'praise',
            'description' => 'Praise a user with a cute image.',
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'examples' => [
                'praise @user'
            ],
        ]);
    }
}
