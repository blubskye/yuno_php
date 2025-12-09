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

// Ensure we're running from the command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Check PHP version
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    die("PHP 8.1 or higher is required. You are running PHP " . PHP_VERSION . "\n");
}

// Load Composer autoloader
$autoloader = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    die("Composer dependencies not installed. Run 'composer install' first.\n");
}

require_once $autoloader;

// Change to script directory
chdir(__DIR__);

// Create and launch Yuno
$yuno = new \Yuno\Yuno();
$yuno->parseArguments($argv);
