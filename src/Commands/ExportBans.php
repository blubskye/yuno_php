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
 * ExportBans command - export the ban list to a file
 */
class ExportBans extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        $guildId = $message->guild->id;

        $message->channel->sendMessage(':hourglass: Fetching bans... This may take a while for large ban lists.')->then(
            function (Message $statusMsg) use ($yuno, $message, $guildId) {
                $message->guild->bans->freshen()->then(
                    function ($bans) use ($yuno, $statusMsg, $guildId) {
                        $bannedUserIds = [];
                        foreach ($bans as $ban) {
                            $bannedUserIds[] = $ban->user->id;
                        }

                        $banStr = json_encode($bannedUserIds);
                        $filename = "./BANS-{$guildId}.txt";

                        try {
                            file_put_contents($filename, $banStr);
                            $count = count($bannedUserIds);
                            $statusMsg->edit("Bans exported successfully! **{$count}** bans saved with Guild ID: {$guildId}");
                            $yuno->prompt->info("[ExportBans] Exported {$count} bans for guild {$guildId}");
                        } catch (\Exception $e) {
                            $statusMsg->edit("Error while saving bans: " . $e->getMessage());
                            $yuno->prompt->error("ExportBans error", $e);
                        }
                    },
                    function (\Exception $e) use ($statusMsg, $yuno) {
                        $statusMsg->edit("Error while fetching bans: " . $e->getMessage());
                        $yuno->prompt->error("ExportBans error", $e);
                    }
                );
            }
        );
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'exportbans',
            'description' => 'Export the ban list to a .txt file.',
            'aliases' => ['ebans'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'onlyMasterUsers' => true,
            'requiredPermissions' => ['BAN_MEMBERS'],
        ]);
    }
}
