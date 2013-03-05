<?php
/**
 * Flexpage Navigation Block
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @copyright Copyright (c) 2009 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package block_flexpagenav
 * @author Mark Nielsen
 */

/**
 * Plugin version
 *
 * @author Mark Nielsen
 * @package block_flexpagenav
 */

$plugin->version      = 2013030500;
$plugin->requires     = 2011120503;
$plugin->component    = 'block_flexpagenav';
$plugin->release      = '2.2.0 (Build: 20120629)';
$plugin->maturity     = MATURITY_STABLE;
$plugin->dependencies = array(
    'format_flexpage' => 2011062802,
    'local_mr'        => 2010090200,
);