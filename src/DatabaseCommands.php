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

use Yuno\Lib\LRUCache;

/**
 * Database operations for Yuno bot
 */
class DatabaseCommands
{
    // Cache for guild settings (5 minute TTL, max 500 guilds)
    private static ?LRUCache $guildSettingsCache = null;

    // Cache for XP data (1 minute TTL, max 1000 entries)
    private static ?LRUCache $xpDataCache = null;

    private static function initCaches(): void
    {
        if (self::$guildSettingsCache === null) {
            self::$guildSettingsCache = new LRUCache(500, 5 * 60 * 1000);
        }
        if (self::$xpDataCache === null) {
            self::$xpDataCache = new LRUCache(1000, 60 * 1000);
        }
    }

    /**
     * Initialize database tables
     */
    public static function initDB(Database $database, Yuno $yuno, bool $newDb = false): void
    {
        self::initCaches();

        $version = $database->all("PRAGMA user_version;");
        $dbVer = $version[0]['user_version'] ?? 0;

        if ($dbVer < $yuno->intVersion && !$newDb) {
            $yuno->prompt->info("The database isn't at the good version for the bot. (Yuno's version: {$yuno->intVersion}; dbvers: {$dbVer}). Expect errors, and report them.");

            if ($dbVer === 0) {
                $yuno->prompt->error("The database isn't for the Yuno's v2 version. Please update the db.");
                $yuno->shutdown(-1);
                return;
            }
        }

        $database->run("PRAGMA user_version = " . $yuno->intVersion);

        // Create experiences table
        $database->run("CREATE TABLE IF NOT EXISTS experiences (
            level INTEGER,
            userID TEXT,
            guildID TEXT,
            exp INTEGER
        )");

        // Create guilds table
        $database->run("CREATE TABLE IF NOT EXISTS guilds (
            id TEXT,
            prefix VARCHAR(5),
            onJoinDMMsg TEXT,
            onJoinDMMsgTitle VARCHAR(255),
            spamFilter INTEGER,
            measureXP INTEGER,
            levelRoleMap TEXT
        )");

        // Create channelcleans table
        $database->run("CREATE TABLE IF NOT EXISTS channelcleans (
            gid TEXT,
            cname TEXT,
            cleantime INTEGER,
            warningtime INTEGER,
            remainingtime TEXT
        )");

        // Create mentionResponses table
        $database->run("CREATE TABLE IF NOT EXISTS mentionResponses (
            id INTEGER PRIMARY KEY,
            gid TEXT,
            trigger TEXT,
            response TEXT,
            image TEXT
        )");

        // Create banImages table
        $database->run("CREATE TABLE IF NOT EXISTS banImages (
            gid TEXT,
            banner TEXT,
            image TEXT
        )");

        // Create modActions table
        $database->run("CREATE TABLE IF NOT EXISTS modActions (
            id INTEGER PRIMARY KEY,
            gid TEXT,
            moderatorId TEXT,
            targetId TEXT,
            action TEXT,
            reason TEXT,
            timestamp INTEGER
        )");

        // Create indexes
        $database->run("CREATE INDEX IF NOT EXISTS idx_modactions_gid_moderator ON modActions(gid, moderatorId)");
        $database->run("CREATE INDEX IF NOT EXISTS idx_modactions_gid_action ON modActions(gid, action)");
        $database->run("CREATE INDEX IF NOT EXISTS idx_experiences_user_guild ON experiences(userID, guildID)");
        $database->run("CREATE INDEX IF NOT EXISTS idx_guilds_id ON guilds(id)");
        $database->run("CREATE INDEX IF NOT EXISTS idx_channelcleans_gid_cname ON channelcleans(gid, cname)");
        $database->run("CREATE INDEX IF NOT EXISTS idx_mentionresponses_gid_trigger ON mentionResponses(gid, trigger)");
        $database->run("CREATE INDEX IF NOT EXISTS idx_banimages_gid_banner ON banImages(gid, banner)");
    }

    /**
     * Initialize a guild in the database
     */
    public static function initGuild(Database $database, string $guildId): void
    {
        $exists = $database->all("SELECT * FROM guilds WHERE id = ?", [$guildId]);
        if (empty($exists)) {
            $database->run("INSERT INTO guilds(id) VALUES(?)", [$guildId]);
        }
    }

    /**
     * Get all guild prefixes
     */
    public static function getPrefixes(Database $database): array
    {
        $guilds = $database->all("SELECT id, prefix FROM guilds");
        $result = [];

        foreach ($guilds as $guild) {
            $result[$guild['id']] = $guild['prefix'];
        }

        return $result;
    }

    /**
     * Set prefix for a guild
     */
    public static function setPrefix(Database $database, string $guildId, string $prefix): void
    {
        self::initGuild($database, $guildId);
        $database->run("UPDATE guilds SET prefix = ? WHERE id = ?", [$prefix, $guildId]);
    }

    /**
     * Get join DM messages for all guilds
     */
    public static function getJoinDMMessages(Database $database): array
    {
        $messages = $database->all("SELECT id, onJoinDMMsg FROM guilds");
        $result = [];

        foreach ($messages as $msg) {
            $result[$msg['id']] = $msg['onJoinDMMsg'];
        }

        return $result;
    }

    /**
     * Set join DM message for a guild
     */
    public static function setJoinDMMessage(Database $database, string $guildId, string $message): void
    {
        self::initGuild($database, $guildId);
        $database->run("UPDATE guilds SET onJoinDMMsg = ? WHERE id = ?", [$message, $guildId]);
    }

    /**
     * Get join DM message titles
     */
    public static function getJoinDMMessagesTitles(Database $database): array
    {
        $messages = $database->all("SELECT id, onJoinDMMsgTitle FROM guilds");
        $result = [];

        foreach ($messages as $msg) {
            $result[$msg['id']] = $msg['onJoinDMMsgTitle'];
        }

        return $result;
    }

    /**
     * Set join DM message title
     */
    public static function setJoinDMMessageTitle(Database $database, string $guildId, string $messageTitle): void
    {
        self::initGuild($database, $guildId);
        $database->run("UPDATE guilds SET onJoinDMMsgTitle = ? WHERE id = ?", [$messageTitle, $guildId]);
    }

    /**
     * Get spam filter status for all guilds
     */
    public static function getSpamFilterEnabled(Database $database): array
    {
        $spam = $database->all("SELECT id, spamFilter FROM guilds");
        $result = [];

        foreach ($spam as $row) {
            $result[$row['id']] = (bool)$row['spamFilter'];
        }

        return $result;
    }

    /**
     * Set spam filter enabled/disabled
     */
    public static function setSpamFilterEnabled(Database $database, string $guildId, bool $enabled): void
    {
        self::initGuild($database, $guildId);
        $database->run("UPDATE guilds SET spamFilter = ? WHERE id = ?", [$enabled ? 1 : 0, $guildId]);
    }

    /**
     * Get guilds where XP is enabled
     */
    public static function getGuildsWhereExpIsEnabled(Database $database): array
    {
        $sql = $database->all("SELECT id, measureXP FROM guilds");
        $result = [];

        foreach ($sql as $row) {
            if ($row['measureXP'] === 'true' || $row['measureXP'] === true || $row['measureXP'] == 1) {
                $result[] = $row['id'];
            }
        }

        return $result;
    }

    /**
     * Set XP enabled/disabled
     */
    public static function setXPEnabled(Database $database, string $guildId, bool $enabled): void
    {
        self::initGuild($database, $guildId);
        $database->run("UPDATE guilds SET measureXP = ? WHERE id = ?", [$enabled ? 'true' : 'false', $guildId]);
    }

    /**
     * Get level role map for a guild
     */
    public static function getLevelRoleMap(Database $database, string $guildId): ?array
    {
        self::initCaches();

        $cacheKey = "guild:levelRoleMap:{$guildId}";
        $cached = self::$guildSettingsCache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $sql = $database->all("SELECT levelRoleMap FROM guilds WHERE id = ?", [$guildId]);

        if (empty($sql) || $sql[0]['levelRoleMap'] === null) {
            self::$guildSettingsCache->set($cacheKey, null);
            return null;
        }

        $result = json_decode($sql[0]['levelRoleMap'], true);
        self::$guildSettingsCache->set($cacheKey, $result);
        return $result;
    }

    /**
     * Set level role map
     */
    public static function setLevelRoleMap(Database $database, string $guildId, array|string $roleMap): void
    {
        self::initCaches();

        if (is_array($roleMap)) {
            $roleMap = json_encode($roleMap);
        }

        self::$guildSettingsCache->delete("guild:levelRoleMap:{$guildId}");
        self::initGuild($database, $guildId);
        $database->run("UPDATE guilds SET levelRoleMap = ? WHERE id = ?", [$roleMap, $guildId]);
    }

    /**
     * Get XP data for a user
     */
    public static function getXPData(Database $database, string $guildId, string $userId): array
    {
        self::initCaches();

        $cacheKey = "xp:{$guildId}:{$userId}";
        $cached = self::$xpDataCache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $sql = $database->all(
            "SELECT level, exp FROM experiences WHERE userID = ? AND guildID = ?",
            [$userId, $guildId]
        );

        if (empty($sql)) {
            $database->run(
                "INSERT INTO experiences (level, userID, guildID, exp) VALUES(?, ?, ?, ?)",
                [0, $userId, $guildId, 0]
            );

            $result = ['xp' => 0, 'level' => 0];
            self::$xpDataCache->set($cacheKey, $result);
            return $result;
        }

        $row = $sql[0];
        $result = [
            'xp' => (int)$row['exp'],
            'level' => (int)$row['level']
        ];

        self::$xpDataCache->set($cacheKey, $result);
        return $result;
    }

    /**
     * Set XP data for a user
     */
    public static function setXPData(Database $database, string $guildId, string $userId, int $xp, int $level): void
    {
        self::initCaches();
        self::$xpDataCache->delete("xp:{$guildId}:{$userId}");

        $database->run(
            "UPDATE experiences SET level = ?, exp = ? WHERE guildID = ? AND userID = ?",
            [$level, $xp, $guildId, $userId]
        );
    }

    /**
     * Get all channel cleans
     */
    public static function getCleans(Database $database): array
    {
        $cleans = $database->all("SELECT * FROM channelcleans");
        $result = [];

        foreach ($cleans as $clean) {
            $result[] = [
                'guildId' => $clean['gid'],
                'channelName' => $clean['cname'],
                'timeFEachClean' => (int)$clean['cleantime'],
                'timeBeforeClean' => (int)$clean['warningtime'],
                'remainingTime' => (int)$clean['remainingtime']
            ];
        }

        return $result;
    }

    /**
     * Get a specific clean
     */
    public static function getClean(Database $database, string $guildId, string $channelName): ?array
    {
        $clean = $database->get(
            "SELECT * FROM channelcleans WHERE gid = ? AND cname = ?",
            [$guildId, $channelName]
        );

        if ($clean === null) {
            return null;
        }

        return [
            'guildId' => $guildId,
            'channelName' => $clean['cname'],
            'timeFEachClean' => (int)$clean['cleantime'],
            'timeBeforeClean' => (int)$clean['warningtime'],
            'remainingTime' => (int)$clean['remainingtime']
        ];
    }

    /**
     * Set/create a clean entry
     */
    public static function setClean(
        Database $database,
        string $guildId,
        string $channelName,
        int $timeFEachClean,
        int $timeBeforeClean,
        ?int $remainingTime = null
    ): array {
        self::initGuild($database, $guildId);

        if ($remainingTime === null) {
            $remainingTime = $timeFEachClean * 60;
        }

        if ($remainingTime > $timeFEachClean * 60) {
            $remainingTime = $timeFEachClean * 60;
        }

        $entry = $database->all(
            "SELECT cleantime FROM channelcleans WHERE gid = ? AND cname = ?",
            [$guildId, $channelName]
        );

        if (empty($entry)) {
            $database->run(
                "INSERT INTO channelcleans(gid, cname, cleantime, warningtime, remainingtime) VALUES(?, ?, ?, ?, ?)",
                [$guildId, $channelName, $timeFEachClean, $timeBeforeClean, $remainingTime]
            );
            return ['creating', true];
        }

        $database->run(
            "UPDATE channelcleans SET cleantime = ?, warningtime = ?, remainingtime = ? WHERE gid = ? AND cname = ?",
            [$timeFEachClean, $timeBeforeClean, (string)$remainingTime, $guildId, $channelName]
        );
        return ['updating', true];
    }

    /**
     * Delete a clean entry
     */
    public static function delClean(Database $database, string $guildId, string $channelName): void
    {
        self::initGuild($database, $guildId);
        $database->run("DELETE FROM channelcleans WHERE gid = ? AND cname = ?", [$guildId, $channelName]);
    }

    /**
     * Add a mention response
     */
    public static function addMentionResponses(
        Database $database,
        string $guildId,
        string $trigger,
        string $response,
        ?string $image = null
    ): void {
        self::initGuild($database, $guildId);
        $database->run(
            "INSERT INTO mentionResponses(id, gid, trigger, response, image) VALUES(null, ?, ?, ?, ?)",
            [$guildId, $trigger, $response, $image ?? 'null']
        );
    }

    /**
     * Get all mention responses
     */
    public static function getMentionResponses(Database $database): array
    {
        $responses = $database->all("SELECT * FROM mentionResponses");
        $result = [];

        foreach ($responses as $response) {
            $result[] = [
                'id' => $response['id'],
                'guildId' => $response['gid'],
                'trigger' => $response['trigger'],
                'response' => $response['response'],
                'image' => $response['image']
            ];
        }

        return $result;
    }

    /**
     * Get mention response from trigger
     */
    public static function getMentionResponseFromTrigger(Database $database, string $guildId, string $trigger): ?array
    {
        $response = $database->get(
            "SELECT * FROM mentionResponses WHERE gid = ? AND trigger = ?",
            [$guildId, $trigger]
        );

        if ($response === null) {
            return null;
        }

        return [
            'id' => $response['id'],
            'guildId' => $response['gid'],
            'trigger' => $response['trigger'],
            'response' => $response['response'],
            'image' => $response['image']
        ];
    }

    /**
     * Delete mention response
     */
    public static function delMentionResponse(Database $database, int $id): void
    {
        $database->run("DELETE FROM mentionResponses WHERE id = ?", [$id]);
    }

    /**
     * Set ban image for a banner
     */
    public static function setBanImage(Database $database, string $guildId, string $bannerId, string $imageUrl): array
    {
        self::initGuild($database, $guildId);

        $entry = $database->all(
            "SELECT image FROM banImages WHERE gid = ? AND banner = ?",
            [$guildId, $bannerId]
        );

        if (empty($entry)) {
            $database->run(
                "INSERT INTO banImages(gid, banner, image) VALUES(?, ?, ?)",
                [$guildId, $bannerId, $imageUrl]
            );
            return ['creating', true];
        }

        $database->run(
            "UPDATE banImages SET image = ? WHERE gid = ? AND banner = ?",
            [$imageUrl, $guildId, $bannerId]
        );
        return ['updating', true];
    }

    /**
     * Get ban image
     */
    public static function getBanImage(Database $database, string $guildId, string $bannerId): ?string
    {
        self::initGuild($database, $guildId);

        $result = $database->all(
            "SELECT * FROM banImages WHERE gid = ? AND banner = ?",
            [$guildId, $bannerId]
        );

        if (empty($result)) {
            return null;
        }

        return $result[0]['image'];
    }

    /**
     * Delete ban image
     */
    public static function delBanImage(Database $database, string $guildId, string $bannerId): void
    {
        $database->run("DELETE FROM banImages WHERE gid = ? AND banner = ?", [$guildId, $bannerId]);
    }

    /**
     * Add mod action
     */
    public static function addModAction(
        Database $database,
        string $guildId,
        string $moderatorId,
        string $targetId,
        string $action,
        ?string $reason,
        int $timestamp
    ): void {
        self::initGuild($database, $guildId);
        $database->run(
            "INSERT INTO modActions(id, gid, moderatorId, targetId, action, reason, timestamp) VALUES(null, ?, ?, ?, ?, ?, ?)",
            [$guildId, $moderatorId, $targetId, $action, $reason, $timestamp]
        );
    }

    /**
     * Check if mod action exists
     */
    public static function modActionExists(
        Database $database,
        string $guildId,
        string $targetId,
        string $action,
        int $timestamp
    ): bool {
        $result = $database->all(
            "SELECT id FROM modActions WHERE gid = ? AND targetId = ? AND action = ? AND timestamp = ?",
            [$guildId, $targetId, $action, $timestamp]
        );
        return !empty($result);
    }

    /**
     * Get mod stats for a guild
     */
    public static function getModStats(Database $database, string $guildId): array
    {
        self::initGuild($database, $guildId);

        $actionCounts = $database->all(
            "SELECT action, COUNT(*) as count FROM modActions WHERE gid = ? GROUP BY action",
            [$guildId]
        );

        $modCounts = $database->all(
            "SELECT moderatorId, action, COUNT(*) as count FROM modActions WHERE gid = ? GROUP BY moderatorId, action ORDER BY count DESC",
            [$guildId]
        );

        $topMods = $database->all(
            "SELECT moderatorId, COUNT(*) as count FROM modActions WHERE gid = ? GROUP BY moderatorId ORDER BY count DESC LIMIT 10",
            [$guildId]
        );

        return [
            'actionCounts' => $actionCounts,
            'modCounts' => $modCounts,
            'topMods' => $topMods
        ];
    }

    /**
     * Get total mod actions count
     */
    public static function getModActionsCount(Database $database, string $guildId): int
    {
        $result = $database->all(
            "SELECT COUNT(*) as count FROM modActions WHERE gid = ?",
            [$guildId]
        );
        return $result[0]['count'] ?? 0;
    }

    /**
     * Clear all caches
     */
    public static function clearCaches(): void
    {
        self::initCaches();
        self::$guildSettingsCache->clear();
        self::$xpDataCache->clear();
    }

    /**
     * Invalidate all caches for a guild
     */
    public static function invalidateGuildCache(string $guildId): void
    {
        self::initCaches();
        self::$guildSettingsCache->invalidatePrefix("guild:");
        self::$xpDataCache->invalidatePrefix("xp:{$guildId}:");
    }
}
