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
Arnapou\GW2Tools\Api\TokenVault::cleanTokens();
