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
use Yuno\Util;

/**
 * AddMasterUser command - add a master user
 */
class AddMasterUser extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            $this->runTerminal($yuno, $args);
            return;
        }

        if (empty($args) && $message->mentions->count() === 0) {
            // List current master users
            $masterUsers = $yuno->config->get('commands.master-users') ?? [];

            if (empty($masterUsers)) {
                $this->sendSuccess($yuno, $message, 'Master Users', "No master users are configured.");
                return;
            }

            $userList = implode(', ', array_map(fn($id) => "<@{$id}>", $masterUsers));
            $this->sendSuccess($yuno, $message, 'Master Users', $userList);
            return;
        }

        // Get user ID
        $userId = null;
        if ($message->mentions->count() > 0) {
            $userId = $message->mentions->first()->id;
        } else {
            $userId = Util::parseUserId($args[0]);
        }

        if ($userId === null) {
            $this->sendFail($yuno, $message, 'Invalid User', "Please mention a user or provide a valid user ID.");
            return;
        }

        $masterUsers = $yuno->config->get('commands.master-users') ?? [];

        if (in_array($userId, $masterUsers)) {
            $this->sendFail($yuno, $message, 'Already Master', "This user is already a master user.");
            return;
        }

        $masterUsers[] = $userId;
        $yuno->config->set('commands.master-users', $masterUsers);
        $yuno->config->save();

        // Reload command manager config
        $yuno->commandMan->configLoaded($yuno, $yuno->config);

        $this->sendSuccess($yuno, $message, 'Master User Added', "<@{$userId}> has been added as a master user.");
    }

    public function runTerminal(Yuno $yuno, array $args): void
    {
        if (empty($args)) {
            $masterUsers = $yuno->config->get('commands.master-users') ?? [];
            $yuno->prompt->info("Master users: " . implode(', ', $masterUsers));
            return;
        }

        $userId = $args[0];
        $masterUsers = $yuno->config->get('commands.master-users') ?? [];

        if (in_array($userId, $masterUsers)) {
            $yuno->prompt->error("User is already a master user.");
            return;
        }

        $masterUsers[] = $userId;
        $yuno->config->set('commands.master-users', $masterUsers);
        $yuno->config->save();

        $yuno->commandMan->configLoaded($yuno, $yuno->config);
        $yuno->prompt->success("Added {$userId} as master user.");
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'add-masteruser',
            'description' => 'Add a master user (bypasses all permission checks).',
            'aliases' => ['addmaster'],
            'discord' => true,
            'terminal' => true,
            'list' => false,
            'listTerminal' => true,
            'onlyMasterUsers' => true,
            'examples' => [
                'add-masteruser @user',
                'add-masteruser 123456789012345678'
            ],
        ]);
    }
}
