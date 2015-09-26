<?php

/*
 * This file is part of the Arnapou FileStore package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

foreach ([
    'users-clean.php',
    'force-longterm-cache.php',
//    'users-statistics.php',
] as $file) {
    try {
        include __DIR__ . '/' . $file;
    }
    catch (Exception $ex) {
        echo $ex->getMessage() . "\n";
        echo $ex->getTraceAsString() . "\n";
    }
}