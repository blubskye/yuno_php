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
 * Interface for all commands
 */
interface CommandInterface
{
    /**
     * Execute the command
     *
     * @param Yuno $yuno The Yuno instance
     * @param Member|null $author The command author (null for terminal)
     * @param array $args Command arguments
     * @param Message|null $message The Discord message
     */
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void;

    /**
     * Get command metadata
     *
     * @return array Command about information:
     *  - command: string - Command name
     *  - description: string - Command description
     *  - aliases: array|string - Command aliases
     *  - discord: bool - Can run in Discord
     *  - terminal: bool - Can run in terminal
     *  - list: bool - Show in help list
     *  - listTerminal: bool - Show in terminal help
     *  - requiredPermissions: array - Required Discord permissions
     *  - onlyMasterUsers: bool - Only master users can use
     *  - isDMPossible: bool - Works in DMs
     *  - dangerous: bool - Auto-ban if unprivileged user tries
     *  - examples: array - Usage examples
     */
    public function getAbout(): array;
}
