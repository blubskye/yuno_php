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
 * Ping command - check bot latency
 */
class Ping extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            $yuno->prompt->info("Pong!");
            return;
        }

        $start = microtime(true);

        $message->channel->sendMessage(":ping_pong: Pinging...")->then(function (Message $sentMessage) use ($start, $yuno, $message) {
            $latency = round((microtime(true) - $start) * 1000);

            $embed = $this->createEmbed($yuno, ":ping_pong: Pong!", "Latency: **{$latency}ms**");
            $this->setRequester($embed, $message->member);

            $sentMessage->edit(\Discord\Builders\MessageBuilder::new()
                ->setContent('')
                ->addEmbed($embed));
        });
    }

    public function runTerminal(Yuno $yuno, array $args): void
    {
        $yuno->prompt->success("Pong!");
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'ping',
            'description' => 'Check the bot\'s latency.',
            'discord' => true,
            'terminal' => true,
            'list' => true,
            'listTerminal' => true,
            'isDMPossible' => true,
        ]);
    }
}
