<?php

/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Command;

use AppBundle\Entity\Token;
use Arnapou\GW2Api\Cache\MongoCache;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('gw2tool:clean')
            ->setDescription('Clean old accounts and codes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limitTime = time() - 365 * 86400; // 1 year

        /*
         * clean statistics into mongodb
         */
        $env   = $this->getGwEnvironment('en');
        $cache = $env->getCache();
        /* @var $cache MongoCache */
        $collection = $cache->getMongoDB()->selectCollection('statistics');
        $collection->deleteMany(['last_update' => ['$lt' => $limitTime]]);

        /*
         * clean accounts into mysql
         */
        $conn  = $this->getDoctrine()->getConnection();
        $table = $this->getDoctrine()->getManager()->getClassMetadata(Token::class)->getTableName();
        $conn->exec('DELETE FROM `' . $table . '` WHERE `lastaccess` < ' . $limitTime);
    }
}
