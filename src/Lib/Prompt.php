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
 * Console output handler with colored logging
 */
class Prompt
{
    private static ?Prompt $instance = null;

    public bool $colors = true;
    public bool $showTime = true;
    public array $hiddenGroups = [];

    // ANSI color codes
    private const RESET = "\033[0m";
    private const BOLD = "\033[1m";

    // Colors
    private const RED = "\033[31m";
    private const GREEN = "\033[32m";
    private const YELLOW = "\033[33m";
    private const BLUE = "\033[34m";
    private const MAGENTA = "\033[35m";
    private const CYAN = "\033[36m";
    private const WHITE = "\033[37m";
    private const GRAY = "\033[90m";

    // Yuno pink (RGB: 200, 140, 141)
    private const YUNO_PINK = "\033[38;2;200;140;141m";
    private const YUNO_PINK_BOLD = "\033[1;38;2;200;140;141m";

    private function __construct()
    {
        // Singleton
    }

    public static function init(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Configure prompt from config
     */
    public function configLoaded(object $yuno, object $config): void
    {
        $showTime = $config->get('logging.show-time');
        $hiddenGroups = $config->get('logging.hidden-groups');

        if (is_bool($showTime)) {
            $this->showTime = $showTime;
        }

        if (is_array($hiddenGroups)) {
            $this->hiddenGroups = $hiddenGroups;
        }
    }

    /**
     * Format message with colors
     */
    private function format(string $message, string $color, string $prefix = ''): string
    {
        $time = '';
        if ($this->showTime) {
            $time = date('[H:i:s] ');
        }

        if ($this->colors) {
            return self::GRAY . $time . self::RESET . $color . $prefix . self::RESET . ' ' . $message . self::RESET;
        }

        return $time . $prefix . ' ' . $message;
    }

    /**
     * Log info message
     */
    public function info(string $message, ?string $group = null): void
    {
        if ($group !== null && in_array($group, $this->hiddenGroups)) {
            return;
        }
        echo $this->format($message, self::CYAN, '[INFO]') . PHP_EOL;
    }

    /**
     * Log success message
     */
    public function success(string $message): void
    {
        echo $this->format($message, self::GREEN, '[SUCCESS]') . PHP_EOL;
    }

    /**
     * Log warning message
     */
    public function warn(string $message): void
    {
        echo $this->format($message, self::YELLOW, '[WARN]') . PHP_EOL;
    }

    /**
     * Alias for warn
     */
    public function warning(string $message): void
    {
        $this->warn($message);
    }

    /**
     * Log error message
     */
    public function error(string $message, ?\Throwable $e = null): void
    {
        echo $this->format($message, self::RED, '[ERROR]') . PHP_EOL;

        if ($e !== null) {
            echo $this->format($e->getMessage(), self::RED, '[ERROR]') . PHP_EOL;
            if ($this->colors) {
                echo self::GRAY . $e->getTraceAsString() . self::RESET . PHP_EOL;
            } else {
                echo $e->getTraceAsString() . PHP_EOL;
            }
        }
    }

    /**
     * Log debug message
     */
    public function debug(string $message): void
    {
        echo $this->format($message, self::MAGENTA, '[DEBUG]') . PHP_EOL;
    }

    /**
     * Write without newline
     */
    public function writeWithoutJumpingLine(string $message): void
    {
        echo "\r" . $message;
    }

    /**
     * Display ASCII art banner
     */
    public function showBanner(string $version): void
    {
        $pink = $this->colors ? self::YUNO_PINK : '';
        $pinkBold = $this->colors ? self::YUNO_PINK_BOLD : '';
        $reset = $this->colors ? self::RESET : '';

        echo <<<BANNER
{$pink}⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠂⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣯⢻⡘⢷⠀⠀⠀⠀⠀⠀⣿⣦⠹⡄⠀⢿⠟⣿⡆⠀⠸⡄⠀⢸⡄⠀⠀⠀⠀⠀⠀⠀⠀⣧
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢠⡄⠀⢸⣤⣳⡘⣇⠀⠀⠀⠀⠀⢸⣟⣆⢻⣆⢸⡆⢹⣿⣄⠀⣷⠀⢰⡇⠀⠀⠀⠀⠀⠀⠀⠀⢸
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⠸⡄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⣇⠀⠈⣆⠹⣿⣸⡇⡄⠀⠀⠀⢸⢧⠀⠈⠻⣆⢿⠀⠉⢻⡆⢹⠀⢸⡇⠀⠀⠀⠀⠀⠀⠀⠀⢸
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⠀⣷⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠸⣟⣦⠀⠘⣆⠘⢷⣷⠹⣆⣀⣀⣸⣿⣧⣀⣀⣈⡳⡄⢸⠀⢹⡀⡀⢸⡇⠀⠀⠀⠀⠀⠀⠀⠀⢸
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⠘⡆⣿⢇⠀⠰⡄⠀⠀⠀⠐⢦⡀⣀⣠⣿⣯⣳⣀⠼⣮⠻⣿⠋⠹⡉⠉⠇⠈⢿⡈⢹⣏⠉⠛⢻⡀⠈⣷⣇⣾⡇⠀⠀⠀⠀⠀⠀⠀⠀⢸
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⣧⣇⣿⢘⡆⠀⠸⡄⠀⠀⠀⠀⠁⠀⠀⢹⠀⠀⠈⢲⣼⣦⣼⣧⡤⣄⣰⣤⣀⠈⣧⠏⢻⡀⠀⢸⡇⠉⢹⣸⣿⠁⠀⠀⠀⠀⠀⠀⠀⠀⠘
⠀⠀⠀⠀⢰⠀⠀⠀⠀⠀⠀⠀⠀⠘⡟⡟⡏⠉⠙⣇⠀⢿⣄⠀⠀⠀⠀⠀⢦⡸⣄⡴⣾⠿⠋⣹⣿⡟⣻⣿⠟⠷⢶⣤⡏⠀⠘⢧⠀⢸⠇⠀⠸⣿⡏⠀⠀⠀⠀⠀⠀⡄⠀⢀⣀
⠀⢣⠀⢀⡿⡀⠀⠀⢀⡀⠀⠀⠀⠀⣿⣿⣷⣶⣶⠾⢦⡈⣏⢢⡀⠀⡀⠀⠀⠙⣿⡜⠁⠀⡿⠟⠁⠀⠉⣃⣴⣶⡄⠘⣿⣦⣀⠸⣆⢸⠀⠀⣰⡟⠀⠀⠀⠀⠀⠀⣰⡇⠀⡼⣆
⠀⠸⡆⠸⡇⣧⠀⠀⣤⢣⠀⠀⠀⠀⢻⣿⣿⡏⠹⢿⣷⣝⢿⣆⠙⢦⡉⠢⢄⡀⢬⣓⣦⡀⠀⠀⠀⠸⡿⠜⣿⣿⣿⠀⣿⣾⡟⠛⣿⡟⠀⢠⡟⣀⡤⠋⢀⠀⠀⣠⣿⠇⣸⠇⠁
⠀⠀⠸⡀⡇⠸⡄⠀⡟⢮⢇⠀⠀⠀⠈⢿⣿⡇⢰⣾⣿⣿⡆⠙⢦⠀⠉⠲⢤⣉⠓⠿⠭⠍⠃⠀⠀⠀⢹⣄⠹⠿⠛⢀⡿⠘⠁⠀⣿⠃⠀⣹⣾⣟⣠⣶⠏⠀⣴⣿⣟⣴⠏⠀⠙
⠀⠀⠀⠹⣽⠀⢣⡀⡇⠈⠻⣧⠀⠀⠀⠘⢿⠀⠈⣇⢹⡿⣿⠀⠀⠀⠀⠀⠀⠉⠀⠀⠀⠀⠀⠀⠀⠀⠀⠙⠶⠤⠴⠾⣁⢀⣠⣴⣣⣶⣿⣽⡿⢻⣿⠃⣠⣾⣿⣿⠏⣿⠀⠀⠀
⠀⠀⠀⠀⠹⣇⠈⢳⢱⠀⠀⢈⡷⣄⠀⢳⣌⣳⣀⠘⠷⠴⢿⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠉⠉⠉⠉⠉⠀⠀⠀⠉⠉⠁⠀⠀⢀⣿⣦⣿⣣⣾⣿⡿⠋⣿⠀⣿⠀⠀⠀
⠀⠀⠀⠀⠀⠈⠓⠀⠻⡄⣠⠾⠋⠀⣹⢦⣙⣟⠛⠛⠛⠃⡼⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢈⣿⠟⠋⣻⣾⠏⠀⠀⡿⠀⣿⡤⠖⠋
⠀⠀⠀⠀⠀⠀⢠⣄⣤⠛⢁⣠⣴⣞⡁⣸⡏⢿⡁⠀⢀⡴⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⠇⢀⣶⠟⠀⠀⠀⢠⡇⢰⡏⠀⠀⢀
⠀⠀⠀⠀⠀⠀⠀⠈⠉⠉⠉⠉⢩⣾⠿⢹⢷⣸⣇⡴⠋⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣿⠀⣾⠁⠀⠀⠀⠀⢸⠀⢸⠀⢀⡴⡟
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⡴⠟⠁⠀⢸⠈⣿⣿⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢠⣿⣸⠇⠀⠀⠀⢰⠆⠀⠀⢸⡶⣿⣭⣟
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠁⠀⠀⠀⠀⢸⠀⢹⣿⡈⠳⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⣿⡟⣠⠄⠀⠀⡼⠀⠀⠀⢸⠃⠈⠻⣿
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⠀⠀⢻⣷⡀⠘⢷⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣾⣿⢧⠏⠀⠀⢰⡇⠀⠀⠀⣿⠀⠀⠀⠈
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⡆⠀⠈⣿⣷⡀⠈⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢠⣿⡿⡼⠀⠀⠀⠾⣸⠀⠀⠀⡟⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⡇⠀⠀⠘⣏⠻⣄⠀⠀⠀⠀⣀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⣿⣡⡇⠀⠀⠀⢰⡇⠀⠀⢰⡇⠀⠀⠠⠖
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⡇⠀⠀⠀⠈⣾⠎⢷⡀⠀⠀⠸⣍⠉⠉⠉⠁⠀⠀⠈⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣾⢷⣿⠇⠀⠀⠀⡞⠀⠀⠀⣾⣧⡴⠋⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⡇⠀⠀⠀⠀⠈⢷⣿⡿⣄⠀⠀⠈⠙⠿⠶⠤⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⣾⡟⢀⣿⠀⠀⠀⢰⠃⠀⠀⢰⡿⠋⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣯⠀⠀⠀⠀⠀⠀⢿⡇⠈⣧⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣀⣴⡿⠟⢻⡇⢸⣿⠀⠀⢠⡏⠀⠀⢀⣾⠀⠀⠀⠀⠀⠀
⣠⡄⠀⠀⠀⠀⠀⠀⠀⠀⣴⣦⡦⠀⠀⠀⢸⠀⠀⠀⠀⠀⠀⢸⡇⠀⢹⠱⡄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣀⣴⡾⠟⠋⠀⠀⣼⡇⣿⣿⠀⢀⡎⠀⠀⠀⣸⡟⠀⠀⠀⠀⠀⠀
⣿⣿⣦⣤⣤⣤⣤⣤⣠⣶⣿⣿⣄⠀⠀⠀⠈⡆⠀⠀⠀⠀⠀⠀⡇⠀⢸⠀⠹⡆⠀⠀⠀⠀⠀⠀⣀⣤⣶⣾⣿⠟⠀⠀⠀⠀⠀⣹⢁⣿⣿⢀⡾⠀⠀⠀⢠⢻⠇⠀⠀⠀⠀⣀⡤
⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⠁⠀⠀⠀⡇⠀⠀⠀⠀⠀⠀⣇⠀⢸⠀⠀⠘⠶⠤⠶⠶⠛⠋⠁⠀⢈⣿⡁⠀⠀⠀⠀⠀⠀⣾⣾⣿⣿⡾⠀⠀⠀⠀⠈⡏⣀⡤⠶⠊⠉⠀⠀
⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣇⠀⠀⠀⠀⠀⢳⠀⠀⠀⠀⠀⠀⣿⠀⢸⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⣧⠀⠀⠀⠀⠀⣼⣿⣿⣿⡿⠁⠀⠀⠀⠀⣼⠏⠁⠀⠀⠀⠀⠀⠀
⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠀⠀⠀⠀⠀⢸⡆⠀⠀⠀⠀⠀⣿⠀⢸⠀⠀⠀⠀⠀⠀⡇⠀⠀⠀⠀⠀⠀⢸⡄⠀⠀⠀⣠⡟⢹⣿⡟⠁⠀⠀⠀⣀⣸⡿⠀⠀⠀⠀⠀⠀⠀⠀
⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡆⠀⠀⠀⠀⠀⡇⠀⠀⠀⠀⠀⣸⠀⢸⠀⠀⠀⠀⠀⠀⡇⠀⠀⠀⠀⠀⠀⠘⣷⠀⠀⢰⣿⢥⣽⠏⠀⠀⠀⠀⠀⢩⣿⠇⠀⠀⠀⠀⠀⠀⠀⠀
⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡇⠀⠀⠀⠀⠀⢹⠀⠀⠀⠀⠀⣿⠀⡸⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣿⣠⣴⣾⣯⡾⠋⠀⠀⠀⠀⠀⢠⣿⡏⠀⠀⠀⠀⠀⠀⠀⠀⠀
⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠀⠀⠀⠀⠀⠀⠸⡄⠀⠀⠀⠀⣾⠀⡇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⠞⠿⠋⠁⢸⡿⠀⠀⠀⠀⠀⠀⣰⣿⡟⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣦⠀⠀⠀⠀⠀⠀⡇⠀⠀⠀⠀⣿⠀⡇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⡞⠁⠀⠀⠀⢀⡞⠀⠀⠀⠀⠀⢀⣾⣿⠟⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠏⠀⠀⠀⠀⠀⠀⢸⡄⠀⠀⠀⢸⣠⡇⠀⠀⠀⠀⠀⠀⠀⢀⣠⡾⠋⠀⠀⠀⠀⣠⡞⠀⢀⠀⣀⢀⣴⣿⣿⣋⣤⠤⠖⠒⠚⠛⠛⠒⠦⣤⣀⠀{$reset}

{$pinkBold}                    ♥ Yuno Gasai v{$version} ♥{$reset}
{$pink}           "I'll protect this server forever... just for you~"{$reset}

BANNER;
    }

    /**
     * Show CLI help
     */
    public function showHelp(array $options): void
    {
        echo PHP_EOL;
        echo self::BOLD . "Yuno Gasai v2 - PHP Port" . self::RESET . PHP_EOL;
        echo PHP_EOL;
        echo "Usage: php index.php [options]" . PHP_EOL;
        echo PHP_EOL;

        foreach ($options as $option) {
            $arg = $option['argument'];
            $desc = $option['description'];
            $aliases = $option['aliases'] ?? [];

            $aliasStr = '';
            if (!empty($aliases)) {
                $aliasStr = ' (' . implode(', ', $aliases) . ')';
            }

            echo "  " . self::CYAN . $arg . self::RESET . $aliasStr . PHP_EOL;
            echo "      " . $desc . PHP_EOL;
        }

        echo PHP_EOL;
    }
}
