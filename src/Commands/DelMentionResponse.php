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
 * DelMentionResponse command - delete a mention response
 */
class DelMentionResponse extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (empty($args)) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Usage: `del-mentionresponse <trigger>`");
            return;
        }

        $trigger = implode(' ', $args);

        $existing = DatabaseCommands::getMentionResponseFromTrigger($yuno->database, $message->guild->id, $trigger);

        if ($existing === null) {
            $this->sendFail($yuno, $message, 'Not Found', "No mention response found for trigger `{$trigger}`.");
            return;
        }

        DatabaseCommands::delMentionResponse($yuno->database, $existing['id']);

        $this->sendSuccess($yuno, $message, 'Mention Response Deleted', "The response for trigger `{$trigger}` has been deleted.");
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'del-mentionresponse',
            'description' => 'Delete a mention response.',
            'aliases' => ['delmr', 'delresponse'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['MANAGE_GUILD'],
            'examples' => [
                'del-mentionresponse hello'
            ],
        ]);
    }
}
