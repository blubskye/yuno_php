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

/**
 * Base command class with common functionality
 */
abstract class BaseCommand implements CommandInterface
{
    protected const FAIL_COLOR = 0xff0000;    // Red
    protected const SUCCESS_COLOR = 0x43cc24; // Green
    protected const INFO_COLOR = 0xff51ff;    // Pink

    /**
     * Create an embed response
     */
    protected function createEmbed(Yuno $yuno, string $title, string $description, int $color = self::INFO_COLOR): Embed
    {
        $embed = new Embed($yuno->discord);
        $embed->setTitle($title)
              ->setDescription($description)
              ->setColor($color)
              ->setTimestamp();

        return $embed;
    }

    /**
     * Create a success embed
     */
    protected function successEmbed(Yuno $yuno, string $title, string $description): Embed
    {
        return $this->createEmbed($yuno, ":white_check_mark: {$title}", $description, self::SUCCESS_COLOR);
    }

    /**
     * Create a failure embed
     */
    protected function failEmbed(Yuno $yuno, string $title, string $description): Embed
    {
        return $this->createEmbed($yuno, ":negative_squared_cross_mark: {$title}", $description, self::FAIL_COLOR);
    }

    /**
     * Send an embed message
     */
    protected function sendEmbed(Message $message, Embed $embed): void
    {
        $builder = MessageBuilder::new()->addEmbed($embed);
        $message->channel->sendMessage($builder);
    }

    /**
     * Send a success message
     */
    protected function sendSuccess(Yuno $yuno, Message $message, string $title, string $description): void
    {
        $this->sendEmbed($message, $this->successEmbed($yuno, $title, $description));
    }

    /**
     * Send a failure message
     */
    protected function sendFail(Yuno $yuno, Message $message, string $title, string $description): void
    {
        $this->sendEmbed($message, $this->failEmbed($yuno, $title, $description));
    }

    /**
     * Set embed requester footer
     */
    protected function setRequester(Embed $embed, Member $member): Embed
    {
        $embed->setFooter("Requested by {$member->user->username}");
        return $embed;
    }

    /**
     * Get default about values
     */
    protected function getDefaultAbout(): array
    {
        return [
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'listTerminal' => false,
            'requiredPermissions' => [],
            'onlyMasterUsers' => false,
            'isDMPossible' => false,
            'dangerous' => false,
            'aliases' => [],
            'examples' => [],
        ];
    }

    /**
     * Terminal command execution (override in subclass if needed)
     */
    public function runTerminal(Yuno $yuno, array $args): void
    {
        $yuno->prompt->error("This command is not available in terminal mode.");
    }
}
