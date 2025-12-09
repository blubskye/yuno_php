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
 * Quote command - get a random Yuno Gasai quote
 */
class Quote extends BaseCommand
{
    private const QUOTES = [
        "Your future belongs to me",
        "I'm glad Yukkis mother is a good person, I didn't have to use any of the tools I brought",
        "I'm the only friend you need",
        "I was practically dead, but you gave me a future. Yukki is my hope in life, but if it won't come true then I will die for Yukki, and even in death I will chase after Yukki",
        "They are all planning to betray you!!!",
        "What's insane is this world that won't let me and Yukki be together!",
        "A half moon, it has a dark half and a bright half, just like me…",
        "Everything in this world is just a game and we are merely the pawns.",
        "Breaking curfew is 3 demerits. 3 demerits gets the cage, the cage means no food."
    ];

    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        $quote = self::QUOTES[array_rand(self::QUOTES)];
        $message->channel->sendMessage($quote);
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'quote',
            'description' => 'Get a random quote from Yuno Gasai.',
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'isDMPossible' => true,
        ]);
    }
}
