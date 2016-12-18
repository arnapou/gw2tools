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
use Arnapou\GW2Api\Exception\InvalidTokenException;
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
        $manager    = $this->getDoctrine()->getManager();
        $repo       = $this->getDoctrine()->getRepository(Token::class);

        foreach ($collection->find() as $data) {
            if (!isset($data['account']) || !isset($data['last_update'])) {
                continue;
            }
            if ($data['last_update'] > time() - 86400) {
                // ignore if statistic is fresh
                continue;
            }

            $accountName = $data['account'];
            $token       = $repo->findOneBy(['name' => $accountName]);
            if (empty($token) || !$token->hasRight('other.disable_statistics')) {
                $collection->deleteMany(['name' => $accountName]);
                continue;
            }

            $disableAccount = false;
            try {
                $env->setAccessToken((string) $token);
                $account = new Account($env);
                $account->getName(); // used only to trigger InvalidTokenException if something is wrong

                if ($account->calculateStatistics($collection)) {
                    $output->writeln("statistics calclulated for <info>" . $accountName . "</info>");
                }
            }
            catch (InvalidTokenException $ex) {
                $disableAccount = true;
            }
            catch (MissingPermissionException $ex) {
                $disableAccount = true;
            }
            catch (\Exception $ex) {
                $output->writeln("<error>" . $ex->getMessage() . "</error>");

                $data['last_update'] = time();
                $collection->updateOne(['account' => $accountName], ['$set' => $data], ['upsert' => true]);
            }
            if ($disableAccount) {
                $token->setIsValid(false);
                $manager->persist($token);
                $manager->flush();
                $collection->deleteMany(['name' => $accountName]);

                $output->writeln("disabled statistics for <info>" . $accountName . "</info>");
            }
        }
    }

}
