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
 * DELETE OLD USERS
 */
$conn = User::getConnection();

// clean old non accessed codes / users : more than one year
$conn->executeDelete(User::table(), 'lastaccess < ' . (time() - 365 * 86400));

////
//// 2015-10-04
//// disabled because of some weird issues on the api when doing a lot of requests
//// I should look further before reactivating it 
////
//foreach ($conn->query("SELECT * FROM `" . User::table() . "`") as $row) {
//    $user = new User($row);
//    $user->checkAccount(); // automatic delete if error with token
//}
