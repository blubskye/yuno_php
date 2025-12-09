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
 * SetSpamFilter command - enable/disable spam filter
 */
class SetSpamFilter extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (empty($args)) {
            $spamFilterStatus = DatabaseCommands::getSpamFilterEnabled($yuno->database);
            $isEnabled = $spamFilterStatus[$message->guild->id] ?? false;

            $status = $isEnabled ? 'enabled' : 'disabled';
            $this->sendSuccess($yuno, $message, 'Spam Filter Status', "Spam filter is currently **{$status}** on this server.");
            return;
        }

        $value = strtolower($args[0]);

        if ($value === 'on' || $value === 'true' || $value === 'enable' || $value === '1') {
            DatabaseCommands::setSpamFilterEnabled($yuno->database, $message->guild->id, true);

            // Reload spam filter in modules
            foreach ($yuno->modules as $module) {
                if (method_exists($module, 'reloadSpamFilter')) {
                    $module->reloadSpamFilter();
                }
            }

            $this->sendSuccess($yuno, $message, 'Spam Filter Enabled', "Spam filter has been **enabled** on this server.");
        } elseif ($value === 'off' || $value === 'false' || $value === 'disable' || $value === '0') {
            DatabaseCommands::setSpamFilterEnabled($yuno->database, $message->guild->id, false);

            // Reload spam filter in modules
            foreach ($yuno->modules as $module) {
                if (method_exists($module, 'reloadSpamFilter')) {
                    $module->reloadSpamFilter();
                }
            }

            $this->sendSuccess($yuno, $message, 'Spam Filter Disabled', "Spam filter has been **disabled** on this server.");
        } else {
            $this->sendFail($yuno, $message, 'Invalid Value', "Please use `on`, `off`, `enable`, `disable`, `true`, or `false`.");
        }
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'set-spamfilter',
            'description' => 'Enable or disable the spam filter for this server.',
            'aliases' => ['spamfilter', 'antispam'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['MANAGE_GUILD'],
            'examples' => [
                'set-spamfilter on',
                'set-spamfilter off'
            ],
        ]);
    }
}
