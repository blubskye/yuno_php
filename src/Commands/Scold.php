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
 * Scold command - scold a user with a reaction image
 */
class Scold extends BaseCommand
{
    private const SCOLD_IMAGES = [
        'http://static3.fjcdn.com/thumbnails/comments/2+is+wrong+in+the+food+business+they+_e4a5025baf43b957f18c834d9615f7fe.jpg',
        'https://i.makeagif.com/media/6-29-2015/oQA7fS.gif',
        'https://i.imgur.com/ZLaayKG.gif',
        'http://orig15.deviantart.net/d57e/f/2012/148/1/7/u_mad_bro__by_meme_thickilisious-d51gdaa.png',
        'https://s-media-cache-ak0.pinimg.com/originals/71/42/a6/7142a6d8d7379e89605c853ec46cf80c.gif'
    ];

    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if ($message->mentions->count() === 0) {
            $message->channel->sendMessage("Who do you want me to scold?");
            return;
        }

        $mentionedUser = $message->mentions->first();
        $image = self::SCOLD_IMAGES[array_rand(self::SCOLD_IMAGES)];

        $message->channel->sendMessage("<@{$mentionedUser->id}> {$image}");
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'scold',
            'description' => 'Scold a user with a reaction image.',
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'examples' => [
                'scold @user'
            ],
        ]);
    }
}
