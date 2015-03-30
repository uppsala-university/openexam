<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    config.php
// Created: 2014-09-09 23:41:57
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

$config = include(__DIR__ . '/../system/config.php');
include(__DIR__ . '/../system/loader.php');
return $config;
