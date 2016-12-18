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
use Gw2tool\Account;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalculateStatisticsCommand extends AbstractCommand {

    protected function configure() {
        $this
            ->setName('gw2tool:statistics')
            ->setDescription('Calculate statistics.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $env        = $this->getGwEnvironment('en');
        $cache      = $env->getCache(); /* @var $cache MongoCache */
        $collection = $cache->getMongoDB()->selectCollection('statistics');

        $repo = $this->getDoctrine()->getRepository(Token::class);
        foreach ($repo->findAll() as /* @var $token Token */ $token) {
            try {
                $env->setAccessToken((string) $token);
                $account    = new Account($env);
                $calculated = $account->calculateStatistics($collection);

                if ($calculated) {
                    $output->writeln("statistics calclulated for " . $token->getName());
                }
            }
            catch (\Exception $ex) {
                $output->writeln($ex->getMessage());
            }
        }
    }

}
