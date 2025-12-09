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
 * SetBanImage command - set custom ban image
 */
class SetBanImage extends BaseCommand
{
    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (empty($args)) {
            // Show current ban image
            $currentImage = DatabaseCommands::getBanImage($yuno->database, $message->guild->id, $message->author->id);

            if ($currentImage === null) {
                $defaultImage = $yuno->config->get('ban.default-image');
                $this->sendSuccess($yuno, $message, 'Ban Image', "You don't have a custom ban image set.\nDefault: {$defaultImage}");
            } else {
                $embed = $this->successEmbed($yuno, 'Your Ban Image', "Your custom ban image:");
                $embed->setImage($currentImage);
                $this->sendEmbed($message, $embed);
            }
            return;
        }

        $imageUrl = $args[0];

        if (!Util::checkIfUrl($imageUrl)) {
            $this->sendFail($yuno, $message, 'Invalid URL', "Please provide a valid image URL.");
            return;
        }

        $result = DatabaseCommands::setBanImage($yuno->database, $message->guild->id, $message->author->id, $imageUrl);

        $action = $result[0] === 'creating' ? 'set' : 'updated';

        $embed = $this->successEmbed($yuno, 'Ban Image ' . ucfirst($action), "Your ban image has been {$action}.");
        $embed->setImage($imageUrl);
        $this->setRequester($embed, $message->member);
        $this->sendEmbed($message, $embed);
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'set-banimage',
            'description' => 'Set your custom ban image.',
            'aliases' => ['banimage'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'requiredPermissions' => ['BAN_MEMBERS'],
            'examples' => [
                'set-banimage https://i.imgur.com/example.gif',
                'set-banimage'
            ],
        ]);
    }
}
