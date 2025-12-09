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
 * SetLevelRoleMap command - map a level to a role
 */
class SetLevelRoleMap extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (count($args) < 2) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Not enough arguments. Usage: `set-levelrolemap <level> <@role>`");
            return;
        }

        // Parse the level number
        $level = (int)$args[0];
        if (!is_numeric($args[0]) || $level < 0) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Level must be a positive number.");
            return;
        }

        // Get the role from mentions
        $role = $message->mention_roles->first();
        if ($role === null) {
            // Try to fetch by ID
            $roleId = preg_replace('/[^0-9]/', '', $args[1]);
            if (!empty($roleId)) {
                $role = $message->guild->roles->get('id', $roleId);
            }
        }

        if ($role === null) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Role not found. Please mention a role or provide a valid role ID.");
            return;
        }

        // Get current level role map
        $levelRoleMap = DatabaseCommands::getLevelRoleMap($yuno->database, $message->guild->id);
        if ($levelRoleMap === null) {
            $levelRoleMap = [];
        }

        // Add/update the mapping
        $levelRoleMap[$level] = $role->id;

        // Save back to database
        DatabaseCommands::setLevelRoleMap($yuno->database, $message->guild->id, $levelRoleMap);

        $message->channel->sendMessage(":white_check_mark: Level role map updated! Users who reach level **{$level}** will receive the **{$role->name}** role.");
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'set-levelrolemap',
            'description' => 'Maps a level to a role. When users reach that level, they automatically get the role.',
            'aliases' => ['slrmap'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'onlyMasterUsers' => true,
            'examples' => [
                'set-levelrolemap 5 @Member',
                'set-levelrolemap 10 @Active',
                'set-levelrolemap 25 @Veteran'
            ],
        ]);
    }
}
