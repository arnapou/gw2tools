<?php

/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gw2tool;

use AppBundle\Command\AbstractCommand;
use AppBundle\Controller\AbstractController;
use Arnapou\GW2Api\Cache\MongoCache;
use Arnapou\GW2Api\Environment;
use Arnapou\GW2Api\Storage\MongoStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait Gw2ApiEnvironmentTrait {

    /**
     *
     * @var array
     */
    protected $gwEnvironments = [];

    /**
     * 
     * @return Environment
     */
    public function getGwEnvironment($lang = null) {
        if ($this instanceof AbstractCommand) {
            $container  = $this->getContainer(); /* @var $container ContainerInterface */
            $tokenCode  = str_replace('gw2tool:', '', $this->getName());
            $logchannel = 'gw2consolerequests';
        }
        elseif ($this instanceof AbstractController) {
            $lang       = $lang ? $lang : $this->getTranslator()->getLocale();
            $container  = $this->container; /* @var $container ContainerInterface */
            $tokenCode  = (!empty($this->token) ? $this->token->getCode() : '          ');
            $logchannel = 'gw2frontrequests';
        }
        else {
            throw new \Exception('Internal error which should not happen. Contact the administrator');
        }

        if (empty($lang)) {
            throw new \Exception('Internal error which should not happen. Contact the administrator');
        }
        if ($lang && isset($this->gwEnvironments[$lang])) {
            return $this->gwEnvironments[$lang];
        }

        $mongoService = $container->get('app.mongo'); /* @var $mongoService MongoService */
        $mongoDB      = $mongoService->getCacheDatabase();
        $cache        = new MongoCache($mongoDB);
        $env          = new Environment($lang);
        $env->setCache($cache);
        $env->setStorage(new MongoStorage($mongoDB));
        $env->setCurlRequestTimeout(20);
        $env->setCacheRetention($container->getParameter('gw2apiclient.cache.duration'));

        // cache rules
        $cacheRules = $container->getParameter('gw2apiclient.cache.rules');
        if (is_array($cacheRules)) {
            foreach ($cacheRules as $apiEndpoint => $seconds) {
                $env->addCacheRetentionRule($apiEndpoint, $seconds);
            }
        }

        // log gw2 api requests if debug
        if ($container->getParameter('gw2apiclient.debug.request')) {
            $logger = $container->get('monolog.logger.' . $logchannel); /* @var $logger \Monolog\Logger */
            $env->getEventListener()->bind(Environment::onRequest, function($event) use ($logger, $tokenCode) {
                $message = str_pad($event['code'], 5)
                    . "  " . str_pad(sprintf("%.3f", $event['time']), 7)
                    . "  " . str_pad($tokenCode, 12)
                    . "  " . $event['url'];
                $logger->info($message);
            });
        }

        $this->gwEnvironments[$lang] = $env;
        return $env;
    }

}
