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
 * List command - show available commands
 */
class ListCommand extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            $this->runTerminal($yuno, $args);
            return;
        }

        $commands = $yuno->commandMan->getUniqueCommands();
        $prefix = $yuno->config->get('commands.default-prefix');

        // Group commands by category
        $categories = [
            'Moderation' => [],
            'Experience' => [],
            'Configuration' => [],
            'Information' => [],
            'Entertainment' => [],
            'Other' => [],
        ];

        foreach ($commands as $name => $command) {
            $about = $command->getAbout();

            // Skip hidden commands
            if (($about['list'] ?? true) === false) {
                continue;
            }

            // Skip Discord-unavailable commands
            if (($about['discord'] ?? true) === false) {
                continue;
            }

            $desc = $about['description'] ?? 'No description';
            $cmdStr = "`{$prefix}{$name}` - {$desc}";

            // Categorize
            $permissions = $about['requiredPermissions'] ?? [];
            $cmdName = strtolower($name);

            if (in_array('BAN_MEMBERS', $permissions) || in_array('KICK_MEMBERS', $permissions) ||
                in_array('MODERATE_MEMBERS', $permissions) || str_contains($cmdName, 'ban') ||
                str_contains($cmdName, 'kick') || str_contains($cmdName, 'clean') ||
                str_contains($cmdName, 'timeout') || str_contains($cmdName, 'mod')) {
                $categories['Moderation'][] = $cmdStr;
            } elseif (str_contains($cmdName, 'xp') || str_contains($cmdName, 'level') ||
                      str_contains($cmdName, 'exp') || str_contains($cmdName, 'rank')) {
                $categories['Experience'][] = $cmdStr;
            } elseif (str_contains($cmdName, 'set-') || str_contains($cmdName, 'config') ||
                      str_contains($cmdName, 'prefix') || str_contains($cmdName, 'init')) {
                $categories['Configuration'][] = $cmdStr;
            } elseif (str_contains($cmdName, 'ping') || str_contains($cmdName, 'stats') ||
                      str_contains($cmdName, 'list') || str_contains($cmdName, 'source') ||
                      str_contains($cmdName, 'urban') || str_contains($cmdName, 'help')) {
                $categories['Information'][] = $cmdStr;
            } elseif (str_contains($cmdName, 'anime') || str_contains($cmdName, 'manga') ||
                      str_contains($cmdName, 'neko') || str_contains($cmdName, '8ball') ||
                      str_contains($cmdName, 'praise') || str_contains($cmdName, 'scold') ||
                      str_contains($cmdName, 'quote')) {
                $categories['Entertainment'][] = $cmdStr;
            } else {
                $categories['Other'][] = $cmdStr;
            }
        }

        // Build embed
        $embed = new Embed($yuno->discord);
        $embed->setTitle(":scroll: Available Commands")
              ->setColor(0xff51ff)
              ->setFooter("Use {$prefix}list for this list • Yuno v{$yuno->version}");

        foreach ($categories as $category => $cmds) {
            if (!empty($cmds)) {
                $embed->addFieldValues($category, implode("\n", array_slice($cmds, 0, 10)));
                if (count($cmds) > 10) {
                    $embed->addFieldValues("{$category} (cont.)", implode("\n", array_slice($cmds, 10)));
                }
            }
        }

        $builder = MessageBuilder::new()->addEmbed($embed);
        $message->channel->sendMessage($builder);
    }

    public function runTerminal(Yuno $yuno, array $args): void
    {
        $commands = $yuno->commandMan->getUniqueCommands();

        $yuno->prompt->info("Available Commands:");
        $yuno->prompt->info("-------------------");

        foreach ($commands as $name => $command) {
            $about = $command->getAbout();

            // Only show terminal-available commands
            if (($about['listTerminal'] ?? false) === false && ($about['terminal'] ?? false) === false) {
                continue;
            }

            $desc = $about['description'] ?? 'No description';
            $yuno->prompt->info("  {$name} - {$desc}");
        }
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'list',
            'description' => 'Show all available commands.',
            'aliases' => ['help', 'commands'],
            'discord' => true,
            'terminal' => true,
            'list' => true,
            'listTerminal' => true,
            'isDMPossible' => true,
        ]);
    }
}
