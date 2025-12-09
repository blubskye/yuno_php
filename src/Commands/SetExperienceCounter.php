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
 * SetExperienceCounter command - enable/disable XP tracking
 */
class SetExperienceCounter extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (empty($args)) {
            $xpEnabledGuilds = DatabaseCommands::getGuildsWhereExpIsEnabled($yuno->database);
            $isEnabled = in_array($message->guild->id, $xpEnabledGuilds);

            $status = $isEnabled ? 'enabled' : 'disabled';
            $this->sendSuccess($yuno, $message, 'XP Counter Status', "Experience counting is currently **{$status}** on this server.");
            return;
        }

        $value = strtolower($args[0]);

        if ($value === 'on' || $value === 'true' || $value === 'enable' || $value === '1') {
            DatabaseCommands::setXPEnabled($yuno->database, $message->guild->id, true);

            // Reload XP guilds in the Experience module
            foreach ($yuno->modules as $module) {
                if (method_exists($module, 'reloadXpGuilds')) {
                    $module->reloadXpGuilds();
                }
            }

            $this->sendSuccess($yuno, $message, 'XP Counter Enabled', "Experience counting has been **enabled** on this server.");
        } elseif ($value === 'off' || $value === 'false' || $value === 'disable' || $value === '0') {
            DatabaseCommands::setXPEnabled($yuno->database, $message->guild->id, false);

            // Reload XP guilds in the Experience module
            foreach ($yuno->modules as $module) {
                if (method_exists($module, 'reloadXpGuilds')) {
                    $module->reloadXpGuilds();
                }
            }

            $this->sendSuccess($yuno, $message, 'XP Counter Disabled', "Experience counting has been **disabled** on this server.");
        } else {
            $this->sendFail($yuno, $message, 'Invalid Value', "Please use `on`, `off`, `enable`, `disable`, `true`, or `false`.");
        }
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'set-experiencecounter',
            'description' => 'Enable or disable XP tracking for this server.',
            'aliases' => ['set-xp', 'xp-toggle'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['MANAGE_GUILD'],
            'examples' => [
                'set-experiencecounter on',
                'set-experiencecounter off'
            ],
        ]);
    }
}
