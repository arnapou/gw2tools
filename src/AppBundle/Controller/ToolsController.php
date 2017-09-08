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

use Arnapou\GW2Api\Model\AchievementCategory;
use Arnapou\GW2Api\Model\AchievementGroup;
use Arnapou\GW2Api\Model\File;
use Arnapou\GW2Api\Model\Glider;
use Arnapou\GW2Api\Model\Mailcarrier;
use Arnapou\GW2Api\Model\Mini;
use Arnapou\GW2Api\Model\Outfit;
use Arnapou\GW2Api\Model\Pet;
use Arnapou\GW2Api\Model\Profession;
use Arnapou\GW2Api\Model\Title;
use Arnapou\GW2Api\Storage\MongoStorage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ToolsController extends AbstractController
{

    /**
     *
     * @Route("/{_locale}/tools/api_itemstats/", requirements={"_locale" = "de|en|es|fr"})
     * @return Response
     */
    public function apiItemStatsAction()
    {
        $langs = array_unique(['en', $this->getTranslator()->getLocale()]);
        $stats = [];

        foreach ($langs as $lang) {
            $allstats = $this->getAllItems('itemstats', null, $lang);
            foreach ($allstats as $stat) {
                if (empty($stat['name']) || !isset($stat['attributes']) ||
                    !is_array($stat['attributes']) || count($stat['attributes']) < 2
                ) {
                    continue;
                }

                // sanitize and order attributes
                $sum   = 0;
                $attrs = [];
                foreach ($stat['attributes'] as $attr => $value) {
                    $attrs[] = ['name' => $attr, 'value' => $value];
                    $sum     += $value;
                }
                if ($sum == 0) {
                    continue;
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

        return $this->renderTool('api_itemstats', [
            'langs'  => $langs,
            'stats'  => $stats,
            'client' => $this->getGwEnvironment()->getClientVersion2(),
        ]);
    }

    /**
     *
     * @Route("/{_locale}/tools/api_pets/", requirements={"_locale" = "de|en|es|fr"})
     * @return Response
     */
    public function apiPetsAction()
    {
        $pets = $this->getAllItems('pets', Pet::class);

        return $this->renderTool('api_pets', [
            'pets'   => $pets,
            'client' => $this->getGwEnvironment()->getClientVersion2(),
        ]);
    }

    /**
     *
     * @Route("/{_locale}/tools/api_files/", requirements={"_locale" = "de|en|es|fr"})
     * @return Response
     */
    public function apiFilesAction()
    {
        $files = $this->getAllItems('files', File::class, null, [], null, ['key' => 1]);

        return $this->renderTool('api_files', [
            'files'  => $files,
            'client' => $this->getGwEnvironment()->getClientVersion2(),
        ]);
    }

    /**
     *
     * @Route("/{_locale}/tools/api_titles/", requirements={"_locale" = "de|en|es|fr"})
     * @return Response
     */
    public function apiTitlesAction()
    {
        $groups = $this->getAllItems('achievementsgroups', AchievementGroup::class, null, [], null, ['data.order' => 1]);
        $titles = $this->getAllItems('titles', Title::class);
        $map    = [];
        foreach ($titles as $title) {
            $map[$title->getAchievementId()] = $title;
        }
        $items = [];
        foreach ($groups as $group) {
            /** @var AchievementGroup $group */
            foreach ($group->getCategories() as $category) {
                /** @var AchievementCategory $category */
                foreach ($category->getAchievementsIds() as $id) {
                    if (isset($map[$id])) {
                        $items[] = [
                            'group'    => $group,
                            'category' => $category,
                            'title'    => $map[$id],
                        ];
                    }
                }
            }
        }

        return $this->renderTool('api_titles', [
            'titles' => $items,
            'client' => $this->getGwEnvironment()->getClientVersion2(),
        ]);
    }

    /**
     *
     * @Route("/{_locale}/tools/api_quaggans/", requirements={"_locale" = "de|en|es|fr"})
     * @return Response
     */
    public function apiQuaggansAction()
    {
        $env      = $this->getGwEnvironment();
        $quaggans = $env->getClientVersion2()->apiQuaggans($env->getClientVersion2()->apiQuaggans());

        return $this->renderTool('api_quaggans', [
            'quaggans' => $quaggans,
            'client'   => $this->getGwEnvironment()->getClientVersion2(),
        ]);
    }

    /**
     *
     * @Route("/{_locale}/tools/api_specializations/", requirements={"_locale" = "de|en|es|fr"})
     * @return Response
     */
    public function apiSpecializationsAction()
    {
        $professions = $this->getAllItems('professions', Profession::class, null, [], null, ['data.name' => 1]);

        return $this->renderTool('api_specializations', [
            'professions' => $professions,
            'client'      => $this->getGwEnvironment()->getClientVersion2(),
        ]);
    }

    /**
     *
     * @Route("/{_locale}/tools/api_professionskills/", requirements={"_locale" = "de|en|es|fr"})
     * @return Response
     */
    public function apiProfessionskillsAction()
    {
        $professions = $this->getAllItems('professions', Profession::class, null, [], null, ['data.name' => 1]);

        return $this->renderTool('api_professionskills', [
            'professions' => $professions,
            'client'      => $this->getGwEnvironment()->getClientVersion2(),
        ]);
    }

    /**
     *
     * @Route("/{_locale}/tools/api_minis/", requirements={"_locale" = "de|en|es|fr"})
     * @return Response
     */
    public function apiMinisAction()
    {
        $minis = $this->getAllItems('minis', Mini::class, null, [], null, ['data.order' => 1]);

        $minisByRarity = [];
        foreach ($minis as $mini) {
            $minisByRarity[$mini->getItem()->getRarity()][] = $mini;
        }

        return $this->renderTool('api_minis', [
            'minis'  => $minisByRarity,
            'client' => $this->getGwEnvironment()->getClientVersion2(),
        ]);
    }

    /**
     *
     * @Route("/{_locale}/tools/api_gliders/", requirements={"_locale" = "de|en|es|fr"})
     * @return Response
     */
    public function apiGlidersAction()
    {
        $gliders = $this->getAllItems('gliders', Glider::class, null, [], null, ['data.order' => 1]);

        return $this->renderTool('api_gliders', [
            'gliders' => $gliders,
            'client'  => $this->getGwEnvironment()->getClientVersion2(),
        ]);
    }

    /**
     *
     * @Route("/{_locale}/tools/api_mailcarriers/", requirements={"_locale" = "de|en|es|fr"})
     * @return Response
     */
    public function apiMailcarriersAction()
    {
        $mailcarriers = $this->getAllItems('mailcarriers', Mailcarrier::class, null, [], null, ['data.order' => 1]);

        return $this->renderTool('api_mailcarriers', [
            'mailcarriers' => $mailcarriers,
            'client'       => $this->getGwEnvironment()->getClientVersion2(),
        ]);
    }

    /**
     *
     * @Route("/{_locale}/tools/api_outfits/", requirements={"_locale" = "de|en|es|fr"})
     * @return Response
     */
    public function apiOutfitsAction()
    {
        $outfits = $this->getAllItems('outfits', Outfit::class);

        return $this->renderTool('api_outfits', [
            'outfits' => $outfits,
            'client'  => $this->getGwEnvironment()->getClientVersion2(),
        ]);
    }

    /**
     *
     * @Route("/{_locale}/tools/chatcode/", requirements={"_locale" = "de|en|es|fr"})
     * @return Response
     */
    public function chatcodeAction()
    {
        return $this->renderTool('chatcode', []);
    }

    /**
     * @param string $collectionName
     * @param string $class
     * @param string $lang
     * @param array  $criteria
     * @param int    $limit
     * @param array  $sort
     * @return array
     */
    private function getAllItems($collectionName, $class = null, $lang = null, $criteria = [], $limit = null, $sort = null)
    {
        $env     = $this->getGwEnvironment($lang);
        $storage = $env->getStorage();
        /* @var $storage MongoStorage */
        $collection = $storage->getCollection($env->getLang(), $collectionName);
        $options    = [];
        if ($limit) {
            $options['limit'] = (int)$limit;
        }
        if (!empty($sort) && is_array($sort)) {
            $options['sort'] = $sort;
        }

        $items = [];
        foreach ($collection->find($criteria, $options) as $doc) {
            if ($class) {
                $items[$doc['key']] = new $class($env, $doc['key']);
            } elseif (isset($doc['data'])) {
                $items[$doc['key']] = $doc['data'];
            }
        }

        return $items;
    }

    /**
     *
     * @param string $tool
     * @param array  $context
     * @return Response
     * @internal param string $page
     */
    private function renderTool($tool, $context)
    {
        $context['page_title'] = $this->trans('tools.' . $tool);
        $response              = $this->render('tool-' . $tool . '.html.twig', $context);
        $response->setMaxAge(900);
        $response->setExpires(new \DateTime('@' . (time() + 900)));
        $response->setPublic();
        return $response;
    }

    /**
     * @return string
     */
    public function getViewPrefix()
    {
        return 'tools/';
    }
}
