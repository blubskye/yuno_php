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
 * MassSetXp command - set XP for all members with a specific role
 */
class MassSetXp extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (count($args) < 2) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Not enough arguments. Usage: `mass-setxp <level> <@role>`");
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

        // Send processing message
        $message->channel->sendMessage(":hourglass: Processing... Fetching all members with role **{$role->name}**...")->then(
            function (Message $processingMsg) use ($yuno, $message, $role, $level) {
                // Fetch all guild members
                $message->guild->members->freshen()->then(
                    function ($members) use ($yuno, $message, $processingMsg, $role, $level) {
                        $membersWithRole = [];
                        foreach ($members as $member) {
                            if ($member->roles->has($role->id)) {
                                $membersWithRole[] = $member;
                            }
                        }

                        if (count($membersWithRole) === 0) {
                            $processingMsg->edit(":negative_squared_cross_mark: No members found with the role **{$role->name}**.");
                            return;
                        }

                        $processingMsg->edit(":hourglass: Found **" . count($membersWithRole) . "** members with role **{$role->name}**. Setting them to level **{$level}**...");

                        $successCount = 0;
                        $failCount = 0;
                        $skippedBots = 0;

                        foreach ($membersWithRole as $member) {
                            // Skip bots
                            if ($member->user->bot) {
                                $skippedBots++;
                                continue;
                            }

                            try {
                                DatabaseCommands::setXPData($yuno->database, $message->guild->id, $member->id, 0, $level);
                                $successCount++;
                            } catch (\Exception $e) {
                                $failCount++;
                                $yuno->prompt->error("Failed to set XP for {$member->user->username}", $e);
                            }
                        }

                        $processingMsg->edit(
                            ":white_check_mark: Mass XP update complete!\n\n" .
                            "**Role:** {$role->name}\n" .
                            "**Level set:** {$level}\n" .
                            "**XP set:** 0 (fresh at level)\n" .
                            "**Successfully updated:** {$successCount} members\n" .
                            "**Failed:** {$failCount} members\n" .
                            "**Skipped bots:** {$skippedBots}"
                        );
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
            'command' => 'mass-setxp',
            'description' => 'Sets all members with a specific role to a target level (with 0 XP at that level).',
            'aliases' => ['massxp', 'bulkxp'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'onlyMasterUsers' => true,
            'examples' => [
                'mass-setxp 5 @Member',
                'mass-setxp 10 @Active'
            ],
        ]);
    }
}
