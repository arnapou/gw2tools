<?php

/*
 * This file is part of the Arnapou FileStore package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include __DIR__ . '/../vendor/autoload.php';

Arnapou\GW2Tools\Service::getInstance();

use Arnapou\GW2Tools\Api\User;

$conn = User::getConnection();

// clean old non accessed codes / users
$conn->executeDelete(User::table(), 'lastaccess < ' . (time() - 180 * 86400));

foreach ($conn->query("SELECT * FROM `" . User::table() . "`") as $row) {
    $user = new User($row);
    $user->checkAccount(); // automatic delete if error with token
}
