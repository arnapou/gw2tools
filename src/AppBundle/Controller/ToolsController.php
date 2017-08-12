<?php
/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use function Arnapou\GW2Api\chatlink_item;
use Arnapou\GW2Api\Storage\MongoStorage;
use function Gw2tool\image;
use MongoDB\BSON\Regex;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ToolsController extends AbstractController
{

    /**
     *
     * @Route("/{_locale}/tools/itemstatslist/", requirements={"_locale" = "de|en|es|fr"})
     * @param Request $request
     * @return Response
     */
    public function itemStatsAction(Request $request)
    {
        $langs = array_unique(['en', $this->getTranslator()->getLocale()]);
        $stats = [];

        foreach ($langs as $lang) {
            $client   = $this->getGwEnvironment($lang)->getClientVersion2();
            $allstats = $client->apiItemstats($client->apiItemstats());
            foreach ($allstats as $stat) {
                if (empty($stat['name']) || !isset($stat['attributes']) ||
                    !is_array($stat['attributes']) || count($stat['attributes']) < 2
                ) {
                    continue;
                }

                // sanitize and order attributes
                $attrs = [];
                foreach ($stat['attributes'] as $attr => $value) {
                    $attrs[] = ['name' => $attr, 'value' => $value];
                }
                usort($attrs, function ($a, $b) {
                    $ret = $a['value'] <=> $b['value'];
                    if ($ret == 0) {
                        return strtolower($a['name']) <=> strtolower($b['name']);
                    }
                    return -$ret;
                });
                $attrs = array_column($attrs, 'name');

                // save
                $key = implode(':', $attrs);
                if (!isset($stats[$key])) {
                    $stats[$key] = [
                        $lang     => $stat['name'],
                        'attrs'   => $attrs,
                        'nbattrs' => count($attrs),
                        'key'     => $key,
                    ];
                } else {
                    $stats[$key][$lang] = $stat['name'];
                }
            }
        }

        // final sort
        usort($stats, function ($a, $b) {
            $ret = $a['nbattrs'] <=> $b['nbattrs'];
            if ($ret == 0) {
                return strtolower($a['key']) <=> strtolower($b['key']);
            }
            return -$ret;
        });

        return $this->renderTool('itemstatslist', [
            'langs' => $langs,
            'stats' => $stats,
        ]);
    }

    /**
     *
     * @Route("/{_locale}/tools/chatcode/", requirements={"_locale" = "de|en|es|fr"})
     * @param Request $request
     * @return Response
     */
    public function chatcodeAction(Request $request)
    {

        return $this->renderTool('chatcode', [
        ]);
    }

    /**
     *
     * @see      https://docs.mongodb.com/php-library/master/tutorial/
     *
     * @Route("/{_locale}/tools/chatcode/generate-{item}-{upgrade1}-{upgrade2}-{skin}-{quantity}.json",
     *     requirements={
     *         "_locale"  = "de|en|es|fr",
     *         "item"     = "[0-9]+",
     *         "upgrade1" = "[0-9]+",
     *         "upgrade2" = "[0-9]+",
     *         "skin"     = "[0-9]+",
     *         "quantity" = "[0-9]+"
     *     })
     * @param int $item
     * @param int $upgrade1
     * @param int $upgrade2
     * @param int $skin
     * @param int $quantity
     * @return Response
     */
    public function chatcodeGenerateAction($item, $upgrade1, $upgrade2, $skin, $quantity)
    {
        return new JsonResponse([
            'chatlink' => chatlink_item($item, $skin, $upgrade1, $upgrade2, $quantity),
        ]);
    }

    /**
     *
     * @see https://docs.mongodb.com/php-library/master/tutorial/
     *
     * @Route("/{_locale}/tools/chatcode/skins-{query}.json", requirements={"_locale" = "de|en|es|fr", "query" = ".+"})
     * @param string $query
     * @return Response
     */
    public function chatcodeSkinsSearchAction($query)
    {
        $env     = $this->getGwEnvironment();
        $storage = $env->getStorage();
        /* @var $storage MongoStorage */
        $collection = $storage->getCollection($env->getLang(), 'skins');

        if (ctype_digit($query)) {
            $criteria = ['key' => new Regex($query, '')];
        } else {
            $criteria = ['data.name' => new Regex($this->queryToRegex($query), 'i')];
        }
        $data = iterator_to_array(
            $collection->find($criteria, [
                'limit' => 100,
                'sort'  => ['data.name' => 1],
            ])
        );

        $data = array_map(function ($item) {
            return [
                'id'    => (int)$item['data']['id'],
                'icon'  => image($item['data']['icon'] ?? 'empty'),
                'value' => $item['data']['name'],
            ];
        }, $data);

        return new JsonResponse($data);
    }

    /**
     *
     * @see https://docs.mongodb.com/php-library/master/tutorial/
     *
     * @Route("/{_locale}/tools/chatcode/items-{query}.json", requirements={"_locale" = "de|en|es|fr", "query" = ".+"})
     * @param string $query
     * @param null   $type
     * @return Response
     */
    public function chatcodeItemsSearchAction($query, $type = null)
    {
        $env     = $this->getGwEnvironment();
        $storage = $env->getStorage();
        /* @var $storage MongoStorage */
        $collection = $storage->getCollection($env->getLang(), 'items');

        if (ctype_digit($query)) {
            $criteria = ['key' => new Regex($query, '')];
        } else {
            $criteria = ['data.name' => new Regex($this->queryToRegex($query), 'i')];
        }
        if ($type) {
            $criteria += ['data.type' => $type];
        }
        $data = iterator_to_array(
            $collection->find($criteria, [
                'limit' => 100,
                'sort'  => ['data.name' => 1],
            ])
        );

        $data = array_map(function ($item) {
            return [
                'id'       => (int)$item['data']['id'],
                'icon'     => image($item['data']['icon'] ?? 'empty'),
                'value'    => $item['data']['name'],
                'chatlink' => chatlink_item($item['data']['id']),
                'rarity'   => $item['data']['rarity'] ?? '',
            ];
        }, $data);

        return new JsonResponse($data);
    }

    /**
     *
     * @see https://docs.mongodb.com/php-library/master/tutorial/
     *
     * @Route("/{_locale}/tools/chatcode/upgrades-{query}.json",
     *     requirements={"_locale" = "de|en|es|fr", "query" = ".+"})
     * @param string $query
     * @return Response
     */
    public function chatcodeUpgradesSearchAction($query)
    {
        return $this->chatcodeItemsSearchAction($query, 'UpgradeComponent');
    }

    /**
     * @param $query
     * @return string
     */
    protected function queryToRegex($query)
    {
        $query = preg_replace('!\s+!', ' ', $query);
        $query = trim($query);
        if (empty($query)) {
            return '.+';
        }
        $parts = explode(' ', $query);
        $parts = array_map('preg_quote', $parts);
        $regex = implode('.+', $parts);
        return $regex;
    }


    /**
     *
     * @param string $tool
     * @param array  $context
     * @return Response
     * @internal param string $page
     */
    protected function renderTool($tool, $context)
    {
        $response = $this->render('_tools/tool-' . $tool . '.html.twig', $context);
        $response->setMaxAge(900);
        $response->setExpires(new \DateTime('@' . (time() + 900)));
        $response->setPublic();
        return $response;
    }
}
