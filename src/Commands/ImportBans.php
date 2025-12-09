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
 * ImportBans command - import bans from a saved file
 */
class ImportBans extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (empty($args)) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Please provide the guild ID. Usage: `importbans <guild-id>`");
            return;
        }

        $guildId = $args[0];

        // Validate guild ID (only digits for Discord snowflake IDs)
        if (!preg_match('/^[0-9]+$/', $guildId)) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Invalid guild ID. Guild IDs should only contain numbers.");
            return;
        }

        $filename = "./BANS-{$guildId}.txt";

        if (!file_exists($filename)) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Ban file not found for guild ID: {$guildId}");
            return;
        }

        try {
            $data = file_get_contents($filename);
            $bans = json_decode($data, true);

            if (!is_array($bans) || empty($bans)) {
                $message->channel->sendMessage(":negative_squared_cross_mark: No bans found in the file or invalid format.");
                return;
            }

            $message->channel->sendMessage(":hourglass: Starting ban import for " . count($bans) . " users... This may take a while.")->then(
                function (Message $statusMsg) use ($yuno, $message, $bans) {
                    $this->processImport($yuno, $message, $statusMsg, $bans);
                }
            );
        } catch (\Exception $e) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Error reading ban file: " . $e->getMessage());
            $yuno->prompt->error("ImportBans error", $e);
        }
    }

    private function processImport(Yuno $yuno, Message $message, Message $statusMsg, array $bans): void
    {
        $totalBanned = 0;
        $totalFailed = 0;
        $totalAlreadyBanned = 0;
        $processed = 0;
        $total = count($bans);

        // Process bans in batches using the event loop
        $batchSize = 5;
        $currentIndex = 0;

        $processNextBatch = function () use (
            &$processNextBatch,
            &$currentIndex,
            &$totalBanned,
            &$totalFailed,
            &$totalAlreadyBanned,
            &$processed,
            $yuno,
            $message,
            $statusMsg,
            $bans,
            $total,
            $batchSize
        ) {
            if ($currentIndex >= $total) {
                // All done
                $statusMsg->edit(
                    "Ban import complete!\n" .
                    ":white_check_mark: Successfully banned: **{$totalBanned}**\n" .
                    ":information_source: Already banned: **{$totalAlreadyBanned}**\n" .
                    ":negative_squared_cross_mark: Failed: **{$totalFailed}**\n" .
                    "Processed **{$total}** users."
                );
                $yuno->prompt->info("[ImportBans] Import complete. Banned: {$totalBanned}, Already banned: {$totalAlreadyBanned}, Failed: {$totalFailed}");
                return;
            }

            $batch = array_slice($bans, $currentIndex, $batchSize);
            $currentIndex += $batchSize;

            // Update status
            $progress = min($processed, $total);
            $percent = round(($progress / $total) * 100);
            $statusMsg->edit(
                ":hourglass: Processing ban import... {$progress}/{$total} ({$percent}%)\n" .
                "Banned: {$totalBanned} | Already banned: {$totalAlreadyBanned} | Failed: {$totalFailed}"
            );

            // Process batch
            foreach ($batch as $userId) {
                $message->guild->bans->ban($userId, 0, "Ban import from saved banlist")->then(
                    function () use (&$totalBanned, &$processed, $yuno, $userId) {
                        $totalBanned++;
                        $processed++;
                        $yuno->prompt->info("[ImportBans] Banned user {$userId}");
                    },
                    function (\Exception $e) use (&$totalFailed, &$totalAlreadyBanned, &$processed, $yuno, $userId) {
                        if (strpos($e->getMessage(), 'already banned') !== false || strpos($e->getMessage(), '10026') !== false) {
                            $totalAlreadyBanned++;
                        } else {
                            $totalFailed++;
                            $yuno->prompt->warn("[ImportBans] Failed to ban {$userId}: " . $e->getMessage());
                        }
                        $processed++;
                    }
                );
            }

            // Schedule next batch after delay
            $yuno->discord->getLoop()->addTimer(2, $processNextBatch);
        };

        // Start processing
        $processNextBatch();
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'importbans',
            'description' => 'Import bans from a saved ban list file.',
            'aliases' => ['ibans'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'onlyMasterUsers' => true,
            'requiredPermissions' => ['BAN_MEMBERS'],
            'examples' => [
                'importbans 123456789012345678'
            ],
        ]);
    }
}
