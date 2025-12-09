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

use PDO;
use PDOException;

/**
 * SQLite database wrapper using PDO
 */
class Database
{
    private ?PDO $db = null;
    public bool $isEncrypted = false;

    public function __construct()
    {
        // Empty constructor
    }

    /**
     * Check if encryption is available (PHP doesn't have native SQLCipher support)
     */
    public function isEncryptionAvailable(): bool
    {
        return false; // PHP PDO doesn't support SQLCipher natively
    }

    /**
     * Open a database file
     *
     * @param string $file Path to database file
     * @param array $options Database options
     * @return self
     * @throws \RuntimeException
     */
    public function open(string $file, array $options = []): self
    {
        try {
            $this->db = new PDO("sqlite:{$file}");
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Note: PHP PDO doesn't support SQLCipher encryption
            // If encryption is needed, consider using a PHP encryption library
            // to encrypt/decrypt data at the application level

            // Apply PRAGMA optimizations if configured
            if (isset($options['pragmas'])) {
                $this->applyPragmas($options['pragmas']);
            }

            return $this;
        } catch (PDOException $e) {
            throw new \RuntimeException("Impossible to connect to the database {$file}. " . $e->getMessage());
        }
    }

    /**
     * Apply PRAGMA settings for optimization
     */
    private function applyPragmas(array $pragmas): void
    {
        // WAL mode - better concurrency and performance
        if (!empty($pragmas['walMode'])) {
            $this->run("PRAGMA journal_mode = WAL");
        }

        // Performance mode - bundle of safe optimizations
        if (!empty($pragmas['performanceMode'])) {
            $this->run("PRAGMA synchronous = NORMAL");
            $this->run("PRAGMA temp_store = MEMORY");
            $this->run("PRAGMA cache_size = -64000"); // 64MB cache
            $this->run("PRAGMA mmap_size = 268435456"); // 256MB mmap
        }

        // Individual settings (override performanceMode if set)
        if (isset($pragmas['cacheSize']) && is_numeric($pragmas['cacheSize'])) {
            $this->run("PRAGMA cache_size = " . (int)$pragmas['cacheSize']);
        }

        if (!empty($pragmas['memoryTemp'])) {
            $this->run("PRAGMA temp_store = MEMORY");
        }

        if (isset($pragmas['mmapSize']) && is_numeric($pragmas['mmapSize'])) {
            $this->run("PRAGMA mmap_size = " . (int)$pragmas['mmapSize']);
        }
    }

    /**
     * Run a SQL command (INSERT, UPDATE, DELETE, etc.)
     *
     * @param string $sql SQL command
     * @param array $params Parameters for prepared statement
     * @return int Number of affected rows
     * @throws \RuntimeException
     */
    public function run(string $sql, array $params = []): int
    {
        if ($this->db === null) {
            throw new \RuntimeException("Tried to access database, but not opened!");
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Run a query and return all results
     *
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array
     * @throws \RuntimeException
     */
    public function all(string $sql, array $params = []): array
    {
        if ($this->db === null) {
            throw new \RuntimeException("Tried to access database, but not opened!");
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Run a query and return a single row
     *
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array|null
     * @throws \RuntimeException
     */
    public function get(string $sql, array $params = []): ?array
    {
        if ($this->db === null) {
            throw new \RuntimeException("Tried to access database, but not opened!");
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    /**
     * Iterate over each row
     *
     * @param string $sql SQL query
     * @param array $params Parameters
     * @param callable $callback Function to call for each row
     */
    public function each(string $sql, array $params, callable $callback): void
    {
        if ($this->db === null) {
            throw new \RuntimeException("Tried to access database, but not opened!");
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        while ($row = $stmt->fetch()) {
            $callback($row);
        }
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId(): int
    {
        if ($this->db === null) {
            throw new \RuntimeException("Tried to access database, but not opened!");
        }

        return (int)$this->db->lastInsertId();
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction(): bool
    {
        if ($this->db === null) {
            throw new \RuntimeException("Tried to access database, but not opened!");
        }

        return $this->db->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit(): bool
    {
        if ($this->db === null) {
            throw new \RuntimeException("Tried to access database, but not opened!");
        }

        return $this->db->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollback(): bool
    {
        if ($this->db === null) {
            throw new \RuntimeException("Tried to access database, but not opened!");
        }

        return $this->db->rollBack();
    }

    /**
     * Close the database connection
     */
    public function close(): void
    {
        $this->db = null;
    }

    /**
     * Check if database is open
     */
    public function isOpen(): bool
    {
        return $this->db !== null;
    }

    /**
     * Get PDO instance for advanced operations
     */
    public function getPdo(): ?PDO
    {
        return $this->db;
    }
}
