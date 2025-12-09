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

namespace Yuno\Modules;

use Yuno\Yuno;
use Yuno\Lib\Config;

/**
 * Interface for all modules
 */
interface ModuleInterface
{
    /**
     * Get the module name
     */
    public function getModuleName(): string;

    /**
     * Initialize the module
     *
     * @param Yuno $yuno The Yuno instance
     * @param bool $hotReloaded Whether this is a hot reload
     */
    public function init(Yuno $yuno, bool $hotReloaded = false): void;

    /**
     * Called when config is loaded
     *
     * @param Yuno $yuno The Yuno instance
     * @param Config $config The configuration
     */
    public function configLoaded(Yuno $yuno, Config $config): void;

    /**
     * Called before shutdown
     *
     * @param Yuno $yuno The Yuno instance
     */
    public function beforeShutdown(Yuno $yuno): void;
}
