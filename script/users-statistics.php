<?php

/*
 * This file is part of the Arnapou FileStore package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include_once __DIR__ . '/../vendor/autoload.php';

Arnapou\GW2Tools\Service::getInstance();

use Arnapou\GW2Tools\User;

/*
 * STATISTICS
 */
foreach (User::getConnection()->query("SELECT * FROM `" . User::table() . "`") as $row) {
    try {
        $user    = new User($row);
        $account = $user->checkAccount();
        if (empty($account)) {
            continue;
        }

        $account->calculateStatistics();
    }
    catch (\Exception $ex) {
        echo $user->getAccount()->getName() . " " . $ex->getMessage() . "\n";
    }
}