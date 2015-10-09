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

use Arnapou\GW2Tools\Service;

Service::getInstance();


$account = \Arnapou\GW2Tools\Gw2Account::getInstance('DEA69D98-AD5C-DA4E-9C0A-BB0B4E2BDD16FCD2DF87-E4DF-42D3-BA18-1AE0B1F25B85');

$char = $account->getCharacter('Tao Lympik');

$attrs = $char->getAttributes();

echo "\n\n";
