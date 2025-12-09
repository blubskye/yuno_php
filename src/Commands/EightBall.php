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
use Discord\Parts\Embed\Embed;
use Discord\Builders\MessageBuilder;
use Yuno\Yuno;

/**
 * 8ball command - magic 8-ball fortune telling
 */
class EightBall extends BaseCommand
{
    private const RESPONSES = [
        // Positive
        'It is certain.',
        'It is decidedly so.',
        'Without a doubt.',
        'Yes - definitely.',
        'You may rely on it.',
        'As I see it, yes.',
        'Most likely.',
        'Outlook good.',
        'Yes.',
        'Signs point to yes.',
        // Neutral
        'Reply hazy, try again.',
        'Ask again later.',
        'Better not tell you now.',
        'Cannot predict now.',
        'Concentrate and ask again.',
        // Negative
        'Don\'t count on it.',
        'My reply is no.',
        'My sources say no.',
        'Outlook not so good.',
        'Very doubtful.',
    ];

    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (empty($args)) {
            $message->channel->sendMessage(":8ball: Please ask a question!");
            return;
        }

        $question = implode(' ', $args);
        $response = self::RESPONSES[array_rand(self::RESPONSES)];

        // Determine color based on response type
        $positiveResponses = array_slice(self::RESPONSES, 0, 10);
        $neutralResponses = array_slice(self::RESPONSES, 10, 5);

        if (in_array($response, $positiveResponses)) {
            $color = 0x43cc24; // Green
        } elseif (in_array($response, $neutralResponses)) {
            $color = 0xffcc00; // Yellow
        } else {
            $color = 0xff0000; // Red
        }

        $embed = new Embed($yuno->discord);
        $embed->setTitle(":8ball: Magic 8-Ball")
              ->setColor($color)
              ->addFieldValues('Question', $question)
              ->addFieldValues('Answer', "**{$response}**")
              ->setFooter("Asked by {$message->author->username}")
              ->setTimestamp();

        $builder = MessageBuilder::new()->addEmbed($embed);
        $message->channel->sendMessage($builder);
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => '8ball',
            'description' => 'Ask the magic 8-ball a question.',
            'aliases' => ['eightball', 'magic8ball'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'isDMPossible' => true,
            'examples' => [
                '8ball Will I win the lottery?',
                '8ball Is PHP better than JavaScript?'
            ],
        ]);
    }
}
