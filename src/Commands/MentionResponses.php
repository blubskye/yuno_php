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
 * MentionResponses command - list all mention responses
 */
class MentionResponses extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        $allResponses = DatabaseCommands::getMentionResponses($yuno->database);

        // Filter to this guild only
        $responses = array_filter($allResponses, fn($r) => $r['guildId'] === $message->guild->id);

        if (empty($responses)) {
            $this->sendSuccess($yuno, $message, 'Mention Responses', "No mention responses configured for this server.");
            return;
        }

        $embed = new Embed($yuno->discord);
        $embed->setTitle(":speech_balloon: Mention Responses")
              ->setColor(0xff51ff)
              ->setFooter("Total: " . count($responses) . " responses");

        $responseList = '';
        $count = 0;

        foreach ($responses as $response) {
            $count++;
            if ($count > 20) {
                $responseList .= "\n*...and " . (count($responses) - 20) . " more*";
                break;
            }

            $hasImage = $response['image'] !== null && $response['image'] !== 'null' ? ' :frame_photo:' : '';
            $responseList .= "**{$response['trigger']}** → {$response['response']}{$hasImage}\n";
        }

        $embed->setDescription($responseList);

        $builder = MessageBuilder::new()->addEmbed($embed);
        $message->channel->sendMessage($builder);
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'mentionresponses',
            'description' => 'List all mention responses for this server.',
            'aliases' => ['listmr', 'responses'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['MANAGE_GUILD'],
        ]);
    }
}
