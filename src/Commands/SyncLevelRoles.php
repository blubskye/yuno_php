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
use Yuno\DatabaseCommands;

/**
 * SyncLevelRoles command - sync level roles for all users at a specific level
 */
class SyncLevelRoles extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        // Get the level role map
        $levelRoleMap = DatabaseCommands::getLevelRoleMap($yuno->database, $message->guild->id);

        if ($levelRoleMap === null || empty($levelRoleMap)) {
            $message->channel->sendMessage(":negative_squared_cross_mark: No level role map configured for this guild. Use `set-levelrolemap` to configure it first.");
            return;
        }

        if (empty($args)) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Not enough arguments. Usage: `sync-levelroles <level>`");
            return;
        }

        // Parse the level number
        $targetLevel = (int)$args[0];
        if (!is_numeric($args[0]) || $targetLevel < 0) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Level must be a positive number.");
            return;
        }

        $message->channel->sendMessage(":hourglass: Processing... Fetching all guild members and checking XP data for level **{$targetLevel}**...")->then(
            function (Message $processingMsg) use ($yuno, $message, $levelRoleMap, $targetLevel) {
                // Fetch all guild members
                $message->guild->members->freshen()->then(
                    function ($members) use ($yuno, $message, $processingMsg, $levelRoleMap, $targetLevel) {
                        $usersAtLevel = [];

                        foreach ($members as $member) {
                            if ($member->user->bot) {
                                continue;
                            }

                            $xpData = DatabaseCommands::getXPData($yuno->database, $message->guild->id, $member->id);
                            if ($xpData !== null && $xpData['level'] === $targetLevel) {
                                $usersAtLevel[] = $member;
                            }
                        }

                        if (empty($usersAtLevel)) {
                            $processingMsg->edit(":negative_squared_cross_mark: No users found at level **{$targetLevel}**.");
                            return;
                        }

                        $processingMsg->edit(":hourglass: Found **" . count($usersAtLevel) . "** users at level **{$targetLevel}**. Syncing roles...");

                        $successCount = 0;
                        $failCount = 0;
                        $skippedCount = 0;

                        foreach ($usersAtLevel as $member) {
                            // Find all roles that should be assigned (level <= user's level)
                            $rolesToAssign = [];

                            foreach ($levelRoleMap as $level => $roleId) {
                                if ((int)$level <= $targetLevel) {
                                    $role = $message->guild->roles->get('id', $roleId);
                                    if ($role !== null && !$member->roles->has($roleId)) {
                                        $rolesToAssign[] = $role;
                                    }
                                }
                            }

                            if (empty($rolesToAssign)) {
                                $skippedCount++;
                                continue;
                            }

                            // Assign all roles
                            try {
                                foreach ($rolesToAssign as $role) {
                                    $member->addRole($role);
                                }
                                $successCount++;
                            } catch (\Exception $e) {
                                $failCount++;
                                $yuno->prompt->error("Failed to assign roles to {$member->user->username}", $e);
                            }
                        }

                        $embed = new Embed($yuno->discord);
                        $embed->setColor(0x43cc24)
                              ->setTitle(":white_check_mark: Level roles synced!")
                              ->setDescription("Synced roles for all users at level **{$targetLevel}**")
                              ->addFieldValues('Users processed', (string)count($usersAtLevel), true)
                              ->addFieldValues('Roles assigned', (string)$successCount, true)
                              ->addFieldValues('Already had roles', (string)$skippedCount, true)
                              ->addFieldValues('Failed', (string)$failCount, true);

                        $builder = MessageBuilder::new()->addEmbed($embed);
                        $processingMsg->edit($builder);
                    },
                    function (\Exception $e) use ($processingMsg) {
                        $processingMsg->edit(":negative_squared_cross_mark: Failed to fetch members: {$e->getMessage()}");
                    }
                );
            }
        );
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'sync-levelroles',
            'description' => 'Assigns all level roles to ALL users at a specific level.',
            'aliases' => ['syncroles', 'fixroles'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'onlyMasterUsers' => true,
            'examples' => [
                'sync-levelroles 5',
                'sync-levelroles 10'
            ],
        ]);
    }
}
