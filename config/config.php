<?php

/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return array(
    'path.cache'    => __DIR__ . '/../cache',
    'path.data'     => __DIR__ . '/../data',
    'path.log'      => __DIR__ . '/../log',
    'date.timezone' => 'UTC',
    'version'       => '1.0.15',
    'table'         => [
        'tokens' => 'tokens',
    ],
    'db'            => [
        'type'     => 'mysql',
        'host'     => 'localhost',
        'dbname'   => 'YOUR_DB_NAME',
        'user'     => 'YOUR_DB_USER',
        'password' => 'YOUR_DB_PASSWORD',
    ],
);
