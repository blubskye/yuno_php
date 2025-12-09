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
use Yuno\Util;
use React\Http\Browser;

/**
 * Urban command - search Urban Dictionary
 */
class Urban extends BaseCommand
{
    private const API_URL = 'https://api.urbandictionary.com/v0/define';
    private const THUMBNAIL = 'https://cdn.discordapp.com/attachments/446842126005829632/449800259468525568/urban_dictionary.png';

    public function run(Yuno $yuno, ?Member $author, array $args, ?Message $message): void
    {
        if ($message === null) {
            return;
        }

        if (empty($args)) {
            $message->channel->sendMessage(":negative_squared_cross_mark: Please input a search term.");
            return;
        }

        $term = implode(' ', $args);
        $url = self::API_URL . '?term=' . urlencode($term);

        $browser = new Browser($yuno->discord->getLoop());
        $browser->get($url)->then(
            function ($response) use ($yuno, $message, $term) {
                $body = (string)$response->getBody();
                $data = json_decode($body, true);

                if (!isset($data['list']) || empty($data['list'])) {
                    $message->channel->sendMessage(":negative_squared_cross_mark: No results found for `{$term}`");
                    return;
                }

                $result = $data['list'][0];
                $word = $result['word'] ?? $term;
                $definition = $result['definition'] ?? 'No definition';
                $example = $result['example'] ?? 'No example';
                $thumbsUp = $result['thumbs_up'] ?? 0;
                $thumbsDown = $result['thumbs_down'] ?? 0;
                $author = $result['author'] ?? 'Unknown';
                $permalink = $result['permalink'] ?? "https://www.urbandictionary.com/define.php?term=" . urlencode($term);

                // Truncate if too long
                $definition = Util::truncate($definition, 1000);
                $example = Util::truncate($example, 1000);

                $embed = new Embed($yuno->discord);
                $embed->setTitle($word)
                      ->setThumbnail(self::THUMBNAIL)
                      ->setColor(0x9eddf1)
                      ->addFieldValues(':notebook_with_decorative_cover: Definition', "`{$definition}`")
                      ->addFieldValues(':bookmark_tabs: Example', "`{$example}`")
                      ->addFieldValues(':small_red_triangle: Upvotes', "`{$thumbsUp}`", true)
                      ->addFieldValues(':small_red_triangle_down: Downvotes', "`{$thumbsDown}`", true)
                      ->addFieldValues(':link: URL', "[{$word}]({$permalink})")
                      ->setFooter("Author - {$author}");

                $builder = MessageBuilder::new()->addEmbed($embed);
                $message->channel->sendMessage($builder);
            },
            function (\Exception $e) use ($yuno, $message, $term) {
                $message->channel->sendMessage(":negative_squared_cross_mark: Error searching Urban Dictionary: " . $e->getMessage());
                $yuno->prompt->error("Urban Dictionary error", $e);
            }
        );
    }

    public function getAbout(): array
    {
        return array_merge($this->getDefaultAbout(), [
            'command' => 'urban',
            'description' => 'Search for a definition on Urban Dictionary.',
            'aliases' => ['ub'],
            'discord' => true,
            'terminal' => false,
            'list' => true,
            'isDMPossible' => true,
            'examples' => [
                'urban anime',
                'urban bruh moment'
            ],
        ]);
    }
}
