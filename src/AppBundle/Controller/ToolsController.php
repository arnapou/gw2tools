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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ToolsController extends AbstractController
{

    /**
     *
     * @Route("/{_locale}/tools/itemstats_list", requirements={"_locale" = "de|en|es|fr"})
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

        return $this->renderTool('itemstats_list', [
            'langs' => $langs,
            'stats' => $stats,
        ]);
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
