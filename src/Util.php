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

namespace Yuno;

use Discord\Parts\User\User;
use Discord\Parts\User\Member;

/**
 * Utility functions
 */
class Util
{
    /**
     * Check if a string is a valid URL
     */
    public static function checkIfUrl(string $str): bool
    {
        return filter_var($str, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Get user avatar URL
     */
    public static function getAvatarURL(User|Member $user, int $size = 256): string
    {
        if ($user instanceof Member) {
            $user = $user->user;
        }

        $avatar = $user->avatar;

        if ($avatar === null) {
            // Default avatar
            $discriminator = (int)($user->discriminator ?? 0);
            $index = $discriminator % 5;
            return "https://cdn.discordapp.com/embed/avatars/{$index}.png";
        }

        $extension = str_starts_with($avatar, 'a_') ? 'gif' : 'png';
        return "https://cdn.discordapp.com/avatars/{$user->id}/{$avatar}.{$extension}?size={$size}";
    }

    /**
     * Parse user mention or ID
     */
    public static function parseUserId(string $str): ?string
    {
        // Check if it's a mention
        if (preg_match('/<@!?(\d+)>/', $str, $matches)) {
            return $matches[1];
        }

        // Check if it's a plain ID
        if (preg_match('/^\d{17,20}$/', $str)) {
            return $str;
        }

        return null;
    }

    /**
     * Parse channel mention or ID
     */
    public static function parseChannelId(string $str): ?string
    {
        // Check if it's a mention
        if (preg_match('/<#(\d+)>/', $str, $matches)) {
            return $matches[1];
        }

        // Check if it's a plain ID
        if (preg_match('/^\d{17,20}$/', $str)) {
            return $str;
        }

        return null;
    }

    /**
     * Parse role mention or ID
     */
    public static function parseRoleId(string $str): ?string
    {
        // Check if it's a mention
        if (preg_match('/<@&(\d+)>/', $str, $matches)) {
            return $matches[1];
        }

        // Check if it's a plain ID
        if (preg_match('/^\d{17,20}$/', $str)) {
            return $str;
        }

        return null;
    }

    /**
     * Format duration in seconds to human-readable string
     */
    public static function formatDuration(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        if ($secs > 0 || empty($parts)) $parts[] = "{$secs}s";

        return implode(' ', $parts);
    }

    /**
     * Format bytes to human-readable string
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Calculate XP needed for a level
     */
    public static function xpForLevel(int $level): int
    {
        return 5 * pow($level, 2) + 50 * $level + 100;
    }

    /**
     * Calculate level from total XP
     */
    public static function levelFromXp(int $xp): int
    {
        $level = 0;
        $totalXpNeeded = 0;

        while ($totalXpNeeded <= $xp) {
            $level++;
            $totalXpNeeded += self::xpForLevel($level);
        }

        return max(0, $level - 1);
    }

    /**
     * Truncate string to max length with ellipsis
     */
    public static function truncate(string $str, int $maxLength, string $ellipsis = '...'): string
    {
        if (mb_strlen($str) <= $maxLength) {
            return $str;
        }

        return mb_substr($str, 0, $maxLength - mb_strlen($ellipsis)) . $ellipsis;
    }

    /**
     * Escape markdown special characters
     */
    public static function escapeMarkdown(string $str): string
    {
        return preg_replace('/([*_`~|\\\\])/', '\\\\$1', $str);
    }

    /**
     * Parse duration string to seconds (e.g., "1h30m", "2d", "30s")
     */
    public static function parseDuration(string $str): ?int
    {
        $total = 0;
        $str = strtolower(trim($str));

        if (preg_match_all('/(\d+)([dhms])/', $str, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $value = (int)$match[1];
                $unit = $match[2];

                $total += match ($unit) {
                    'd' => $value * 86400,
                    'h' => $value * 3600,
                    'm' => $value * 60,
                    's' => $value,
                    default => 0,
                };
            }
            return $total;
        }

        // If just a number, assume seconds
        if (is_numeric($str)) {
            return (int)$str;
        }

        return null;
    }

    /**
     * Clean a channel by cloning it and deleting the original
     * This preserves permissions and settings while removing all messages
     */
    public static function cleanChannel(\Discord\Parts\Channel\Channel $channel, \Discord\Discord $discord)
    {
        return $channel->guild->channels->save(
            $channel->guild->channels->create([
                'name' => $channel->name,
                'type' => $channel->type,
                'position' => $channel->position,
                'topic' => $channel->topic,
                'nsfw' => $channel->nsfw,
                'rate_limit_per_user' => $channel->rate_limit_per_user,
                'parent_id' => $channel->parent_id,
                'permission_overwrites' => $channel->permission_overwrites->toArray(),
            ])
        )->then(function ($newChannel) use ($channel) {
            return $channel->guild->channels->delete($channel)->then(
                function () use ($newChannel) {
                    return $newChannel;
                }
            );
        });
    }

    /**
     * Clean anime synopsis by truncating and adding a link
     */
    public static function cleanSynopsis(string $synopsis, int $id, string $type = 'anime'): string
    {
        $maxLen = 800;
        if (mb_strlen($synopsis) <= $maxLen) {
            return $synopsis;
        }

        $truncated = mb_substr($synopsis, 0, $maxLen);
        $link = "https://myanimelist.net/{$type}/{$id}";
        return $truncated . "... [Read More]({$link})";
    }
}
