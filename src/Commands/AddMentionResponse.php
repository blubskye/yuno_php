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
use Yuno\DatabaseCommands;

/**
 * AddMentionResponse command - add custom trigger->response pairs
 */
class AddMentionResponse extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (count($args) < 2) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Usage: `add-mentionresponse <trigger> | <response> [| image_url]`");
            return;
        }

        $argsStr = implode(' ', $args);

        // Parse trigger | response | image
        $parts = array_map('trim', explode('|', $argsStr));

        if (count($parts) < 2) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Please separate trigger and response with `|`");
            return;
        }

        $trigger = $parts[0];
        $response = $parts[1];
        $image = $parts[2] ?? null;

        if (empty($trigger) || empty($response)) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Trigger and response cannot be empty.");
            return;
        }

        // Validate image URL if provided
        if ($image !== null && !empty($image) && !Util::checkIfUrl($image)) {
            $this->sendFail($yuno, $message, 'Invalid Image URL', "The image URL is not valid.");
            return;
        }

        // Check if trigger already exists
        $existing = DatabaseCommands::getMentionResponseFromTrigger($yuno->database, $message->guild->id, $trigger);
        if ($existing !== null) {
            $this->sendFail($yuno, $message, 'Trigger Exists', "A response for trigger `{$trigger}` already exists. Delete it first with `del-mentionresponse`.");
            return;
        }

        DatabaseCommands::addMentionResponses($yuno->database, $message->guild->id, $trigger, $response, $image);

        $embed = $this->successEmbed($yuno, 'Mention Response Added', "");
        $embed->addFieldValues('Trigger', $trigger, true);
        $embed->addFieldValues('Response', $response, true);
        if ($image !== null && !empty($image)) {
            $embed->addFieldValues('Image', $image);
        }
        $this->setRequester($embed, $message->member);
        $this->sendEmbed($message, $embed);
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'add-mentionresponse',
            'description' => 'Add a custom mention trigger->response.',
            'aliases' => ['addmr', 'addresponse'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['MANAGE_GUILD'],
            'examples' => [
                'add-mentionresponse hello | Hello there!',
                'add-mentionresponse cute | You\'re cute! | https://i.imgur.com/example.gif'
            ],
        ]);
    }
}
