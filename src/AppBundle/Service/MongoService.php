<?php

/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Service;

use Doctrine\ORM\EntityManager;
use MongoDB\Client as MongoClient;
use MongoDB\Database as MongoDatabase;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class MongoService
{
    /**
     *
     * @var Container
     */
    private $container;

    /**
     *
     * @var EntityManager
     */
    private $em;

    /**
     *
     * @var MongoClient
     */
    private $client = null;

    /**
     *
     * @var MongoDatabase
     */
    private $cacheDatabase = null;

    public function __construct(EntityManager $em, Container $container)
    {
        $this->em        = $em;
        $this->container = $container;
    }

    /**
     *
     * @return MongoClient
     */
    public function getClient()
    {
        if (empty($this->client)) {
            $this->client = new MongoClient('mongodb://localhost:27017', [], [
                'typeMap' => [
                    'root'     => 'array',
                    'document' => 'array',
                ],
            ]);
        }
        return $this->client;
    }

    /**
     *
     * @return MongoDatabase
     */
    public function getCacheDatabase()
    {
        if (empty($this->cacheDatabase)) {
            $mongo               = $this->getClient();
            $this->cacheDatabase = $mongo->selectDatabase($this->container->getParameter('mongo.collection'));
        }
        return $this->cacheDatabase;
    }
}
