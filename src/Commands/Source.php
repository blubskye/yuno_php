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
 * Source command - show bot source information
 */
class Source extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            $this->runTerminal($yuno, $args);
            return;
        }

        $embed = new Embed($yuno->discord);
        $embed->setTitle(":scroll: Yuno Gasai - Source Code")
              ->setColor(0xff51ff)
              ->setDescription("Yuno Gasai is an open-source Discord bot originally written in JavaScript and ported to PHP.")
              ->addFieldValues('Original Author', 'Maeeen', true)
              ->addFieldValues('License', 'AGPL-3.0', true)
              ->addFieldValues('Version', $yuno->version, true)
              ->addFieldValues('Original Repository', '[GitHub](https://github.com/Maeeen/yuno-gasai-2)')
              ->addFieldValues('Technologies', 'PHP 8.1+, DiscordPHP, SQLite')
              ->setFooter("Made with ♥ by Maeeen")
              ->setTimestamp();

        $builder = MessageBuilder::new()->addEmbed($embed);
        $message->channel->sendMessage($builder);
    }

    public function runTerminal(Yuno $yuno, array $args): void
    {
        $yuno->prompt->info("Yuno Gasai v{$yuno->version}");
        $yuno->prompt->info("Original Author: Maeeen");
        $yuno->prompt->info("License: AGPL-3.0");
        $yuno->prompt->info("Repository: https://github.com/Maeeen/yuno-gasai-2");
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'source',
            'description' => 'Show bot source code information.',
            'aliases' => ['sources', 'github', 'repo'],
            'discord' => true,
            'terminal' => true,
            'list' => true,
            'listTerminal' => true,
            'isDMPossible' => true,
        ]);
    }
}
