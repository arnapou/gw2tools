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

use Arnapou\GW2Api\Storage\MongoStorage;
use MongoDB\BSON\UTCDateTime as MongoDate;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PopulateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('gw2tool:populate')
            ->setDescription('Populate mongoDB storage with all Gw2 API objects.')
            ->addArgument('lang', InputArgument::REQUIRED, 'The language.')
            ->addArgument('apiname', InputArgument::OPTIONAL, 'The optional api name.');
    }

    protected function getRandomizedTime($t)
    {
        if ($t < 3600) {
            return $t;
        }
        $delta = floor(0.1 * $t);
        $t     = $t + mt_rand(0, $delta) - $delta / 2;
        return round($t);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lang = $input->getArgument('lang');
        if (!\in_array($lang, $this->getLocales())) {
            throw new \Exception('Value not allowed for argument "lang"');
        }
        $argumentApiName = $input->getArgument('apiname');

        $env     = $this->getGwEnvironment($lang);
        $storage = $env->getStorage();
        /* @var $storage MongoStorage */
        $client = $env->getClientVersion2();
        foreach ($this->getArrayClasses($env, $lang) as $class) {
            try {
                $apiName   = $class['name'];
                $apiMethod = $class['method'];
                if (!empty($argumentApiName) && $argumentApiName !== $apiName) {
                    continue;
                }
                $collection = $storage->getCollection($class['lang'], $apiName);
                $date       = new MongoDate((time() - $this->getRandomizedTime($class['time'])) * 1000);

                $output->writeln('<comment>[' . $lang . ']</comment> <info>' . $apiName . '</info> ');
                $ids       = array_map('strval', $client->$apiMethod());
                $freshIds  = [];
                $documents = $collection->find(['datecreated' => ['$gt' => $date]]);
                foreach ($documents as $document) {
                    $freshIds[] = $document['key'];
                }
                $notFreshIds = array_diff($ids, $freshIds);

                $n = \count($notFreshIds);
                $k = 0;
                if ($n) {
                    $step = ceil($n / 150);
                    $bar  = new ProgressBar($output, $n);
                    $bar->setFormat('  [%bar%] %percent:3s%% %current%/%max%  %elapsed:6s%/%estimated:-6s%  %memory:6s%');
                    $i = 0;
                    $bar->start();
                    $chunkSize = 2 * $step < 500 ? 500 : 2 * $step;
                    $chunks    = array_chunk($notFreshIds, $chunkSize);
                    foreach ($chunks as $chunk) {
                        try {
                            $items = $client->$apiMethod($chunk);
                            foreach ($items as $data) {
                                if (isset($data['id'])) {
                                    $storage->set($lang, $apiName, $data['id'], $data);
                                    $k++;
                                }
                                $i++;
                                if ($i % $step == 0) {
                                    $bar->advance($step);
                                }
                            }
                        } catch (\Exception $ex) {
                            $output->writeln('<error>' . $ex->getMessage() . '</error>');
                        }
                    }
                    $bar->finish();
                }
                if ($k) {
                    $output->writeln('    <info>' . $k . '/' . \count($ids) . '</info> objects stored');
                } else {
                    $output->writeln('    ' . $k . '/' . \count($ids) . ' objects stored');
                }
                $storage->clearCache();
            } catch (\Exception $ex) {
                $output->writeln('<error>' . $ex->getMessage() . '</error>');
            }
        }
    }
}
