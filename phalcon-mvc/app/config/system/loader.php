<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    loader.php
// Created: 2014-08-20 01:56:59
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

$loader = new Phalcon\Loader();

/**
 * Register namespace mapping taken from the configuration files:
 */
$loader->registerNamespaces(
    array(
            'OpenExam\Controllers' => $config->application->controllersDir,
            'OpenExam\Models'      => $config->application->modelsDir,
            'OpenExam\Library'     => $config->application->libraryDir,
            'OpenExam\Plugins'     => $config->application->pluginsDir,
    )
);

$loader->register();

/**
 * Include Composer auto-loader.
 */
require_once PROJ_DIR . '/vendor/autoload.php';

return $loader;
