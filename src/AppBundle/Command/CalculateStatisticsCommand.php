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

        $tokens = [];
        foreach ($repo->findAll() as /* @var $token Token */ $token) {
            $tokens[$token->getName()] = $token;
        }

        $statistics = [];
        foreach ($collection->find() as $data) {
            if (!isset($data['account']) || !isset($data['last_update'])) {
                continue;
            }
            $statistics[$data['account']] = $data;
        }

        foreach ($tokens as $accountName => /* @var $token Token */ $token) {
            if ($token->hasRight('other.disable_statistics')) {
                $collection->deleteMany(['name' => $accountName]);
                continue;
            }
            if ($token->getLastaccess() < time() - 86400 * 7) {
                // ignore if the account was not connected for 1 week
                continue;
            }
            $data = isset($statistics[$accountName]) ? $statistics[$accountName] : null;
            if (
                empty($data) || // no data previously calculated > do it !
                $data['last_update'] <= time() - Account::STATISTIC_RETENTION_SECONDS // old, we should calculate again
            ) {

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

                    if ($data) {
                        $data['last_update'] = time();
                        $collection->updateOne(['account' => $accountName], ['$set' => $data], ['upsert' => true]);
                    }
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

        foreach ($statistics as $accountName => $data) {
            if (!isset($tokens[$accountName])) {
                // old statistics we should delete
                $collection->deleteMany(['name' => $accountName]);
            }
        }
    }

}
