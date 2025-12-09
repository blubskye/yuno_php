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

namespace Yuno\Lib;

/**
 * Simple LRU (Least Recently Used) Cache with TTL support
 */
class LRUCache
{
    private array $cache = [];
    private array $timestamps = [];
    private int $maxSize;
    private int $ttl; // TTL in milliseconds

    /**
     * @param int $maxSize Maximum number of entries
     * @param int $ttl Time to live in milliseconds
     */
    public function __construct(int $maxSize = 500, int $ttl = 300000)
    {
        $this->maxSize = $maxSize;
        $this->ttl = $ttl;
    }

    /**
     * Get a value from cache
     */
    public function get(string $key): mixed
    {
        if (!isset($this->cache[$key])) {
            return null;
        }

        // Check TTL
        $now = $this->currentTimeMs();
        if (($now - $this->timestamps[$key]) > $this->ttl) {
            $this->delete($key);
            return null;
        }

        // Move to end (most recently used)
        $value = $this->cache[$key];
        unset($this->cache[$key]);
        $this->cache[$key] = $value;
        $this->timestamps[$key] = $now;

        return $value;
    }

    /**
     * Set a value in cache
     */
    public function set(string $key, mixed $value): void
    {
        // If key exists, remove it first
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
        }

        // Evict oldest entry if at max size
        if (count($this->cache) >= $this->maxSize) {
            $oldestKey = array_key_first($this->cache);
            if ($oldestKey !== null) {
                $this->delete($oldestKey);
            }
        }

        $this->cache[$key] = $value;
        $this->timestamps[$key] = $this->currentTimeMs();
    }

    /**
     * Check if key exists and is not expired
     */
    public function has(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        $now = $this->currentTimeMs();
        if (($now - $this->timestamps[$key]) > $this->ttl) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    /**
     * Delete a key from cache
     */
    public function delete(string $key): void
    {
        unset($this->cache[$key]);
        unset($this->timestamps[$key]);
    }

    /**
     * Clear all entries
     */
    public function clear(): void
    {
        $this->cache = [];
        $this->timestamps = [];
    }

    /**
     * Invalidate all entries with a given prefix
     */
    public function invalidatePrefix(string $prefix): void
    {
        foreach (array_keys($this->cache) as $key) {
            if (str_starts_with($key, $prefix)) {
                $this->delete($key);
            }
        }
    }

    /**
     * Get current size
     */
    public function size(): int
    {
        return count($this->cache);
    }

    /**
     * Get current time in milliseconds
     */
    private function currentTimeMs(): int
    {
        return (int)(microtime(true) * 1000);
    }

    /**
     * Clean up expired entries
     */
    public function cleanup(): void
    {
        $now = $this->currentTimeMs();

        foreach ($this->timestamps as $key => $timestamp) {
            if (($now - $timestamp) > $this->ttl) {
                $this->delete($key);
            }
        }
    }
}
