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

use AppBundle\Entity\Token;
use Arnapou\GW2Api\Model\Character;
use Gw2tool\Account;
use Gw2tool\Exception\AccessNotAllowedException;
use Gw2tool\Menu;
use Gw2tool\MenuItem;
use Gw2tool\MenuList;
use Gw2tool\Statistics;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends AbstractController
{

    /**
     *
     * @var Token
     */
    protected $token;

    /**
     *
     * @var Account
     */
    protected $account;

    /**
     *
     * @var array
     */
    protected $characters = [];

    /**
     *
     * @var array
     */
    protected $guilds = [];

    /**
     *
     * @var MenuList
     */
    protected $menu;

    /**
     *
     * @var boolean
     */
    protected $isOwner = false;

    /**
     *
     * @Route("/{_locale}/{_code}/", requirements={
     *     "_locale" = "de|en|es|fr",
     *     "_code" = "[a-zA-Z0-9]{10}"
     * })
     */
    public function homeAction(Request $request)
    {
        return $this->redirect('./account/');
    }

    /**
     *
     * @Route("/{_locale}/{_code}/statistics/{dataset}.json", requirements={
     *     "_locale" = "de|en|es|fr",
     *     "_code" = "[a-zA-Z0-9]{10}",
     *     "dataset" = "[a-zA-Z0-9_-]+"
     * })
     * @param         $_code
     * @param         $dataset
     * @param Request $request
     * @return JsonResponse
     */
    public function statisticsJsonAction($_code, $dataset, Request $request)
    {
        try {
            $context = $this->getContext($_code, 'statistics');
            $stats   = new Statistics($this, $this->account);
            if (preg_match('!^wallet-([0-9]+)$!', $dataset, $m)) {
                $method = 'getDatasetWallet';
                if (method_exists($stats, $method)) {
                    $data = $stats->$method($m[1]);
                    if (!empty($data)) {
                        $response = new JsonResponse($data);
                    }
                }
            } else {
                $method = 'getDataset' . $dataset;
                if (method_exists($stats, $method)) {
                    $response = new JsonResponse($stats->$method());
                }
            }
            if (isset($response)) {
                $response->setMaxAge(900);
                $response->setExpires(new \DateTime('@' . (time() + 900)));
                $response->setPublic();
                return $response;
            }
        } catch (\Exception $ex) {
            return new JsonResponse(['error' => $ex->getMessage()]);
        }
    }

    /**
     *
     * @Route("/{_locale}/{_code}/{page}/", requirements={
     *     "_locale" = "de|en|es|fr",
     *     "_code" = "[a-zA-Z0-9]{10}",
     *     "page" = "(?!raidplanner)[a-zA-Z0-9_]+"
     * })
     * @param         $_code
     * @param         $page
     * @param Request $request
     * @return Response
     */
    public function pageAction($_code, $page, Request $request)
    {
        $context = $this->getContext($_code, $page);
        return $this->render($page . '/page.html.twig', $context);
    }

    /**
     *
     * @Route("/{_locale}/{_code}/{page}/content.html", requirements={
     *     "_locale" = "de|en|es|fr",
     *     "_code" = "[a-zA-Z0-9]{10}",
     *     "page" = "[a-zA-Z0-9_]+"
     * })
     * @param         $_code
     * @param         $page
     * @param Request $request
     * @return Response
     */
    public function pageContentAction($_code, $page, Request $request)
    {
        try {
            $context = $this->getContext($_code, $page, true);
            return $this->render($page . '/content.html.twig', $context);
        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     *
     * @Route("/{_locale}/{_code}/gw2skills-{mode}/{name}", requirements={
     *     "_locale" = "de|en|es|fr",
     *     "_code" = "[a-zA-Z0-9]{10}",
     *     "name" = "[^.]+",
     *     "mode" = "pve|pvp|wvw"
     * })
     * @param         $_code
     * @param         $mode
     * @param         $name
     * @param Request $request
     * @return RedirectResponse|NotFoundHttpException
     */
    public function gw2skillsBuildAction($_code, $mode, $name, Request $request)
    {
        $context = $this->getContext($_code, 'character/' . $name);
        if (!isset($this->characters[$name])) {
            return $this->createNotFoundException();
        }
        $character = $this->characters[$name];
        /* @var $character Character */

        $url = $character->getGw2SkillsLink($mode);
        return $this->redirect($url);
    }

    /**
     *
     * @Route("/{_locale}/{_code}/character/{name}", requirements={
     *     "_locale" = "de|en|es|fr",
     *     "_code" = "[a-zA-Z0-9]{10}",
     *     "name" = "[^.]+"
     * })
     * @param         $_code
     * @param         $name
     * @param Request $request
     * @return Response|NotFoundHttpException
     */
    public function characterAction($_code, $name, Request $request)
    {
        $context = $this->getContext($_code, 'character/' . $name);
        if (!isset($this->characters[$name])) {
            return $this->createNotFoundException();
        }
        $context['character'] = $this->characters[$name];
        return $this->render('character/page.html.twig', $context);
    }

    /**
     *
     * @Route("/{_locale}/{_code}/character/{name}.html", requirements={
     *     "_locale" = "de|en|es|fr",
     *     "_code" = "[a-zA-Z0-9]{10}",
     *     "name" = "[^.]+"
     * })
     * @param         $_code
     * @param         $name
     * @param Request $request
     * @return Response|NotFoundHttpException
     */
    public function characterContentAction($_code, $name, Request $request)
    {
        try {
            $context = $this->getContext($_code, 'character/' . $name, true);
            if (!isset($this->characters[$name])) {
                return $this->createNotFoundException();
            }
            $context['character'] = $this->characters[$name];
            return $this->render('character/content.html.twig', $context);
        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     *
     * @Route("/{_locale}/{_code}/{folder}/{guildid}", requirements={
     *     "_locale" = "de|en|es|fr",
     *     "_code" = "[a-zA-Z0-9]{10}",
     *     "guildid" = "([a-zA-Z0-9]+-)+[a-zA-Z0-9]+",
     *     "folder" = "guild(_stash)?"
     * })
     * @param         $_code
     * @param         $guildid
     * @param         $folder
     * @param Request $request
     * @return Response|NotFoundHttpException
     */
    public function guildStashAction($_code, $guildid, $folder, Request $request)
    {
        $context = $this->getContext($_code, $folder . '/' . $guildid);
        if (!isset($this->guilds[$guildid])) {
            return $this->createNotFoundException();
        }
        $context['guild'] = $this->guilds[$guildid];
        return $this->render($folder . '/page.html.twig', $context);
    }

    /**
     *
     * @Route("/{_locale}/{_code}/{folder}/{guildid}.html", requirements={
     *     "_locale" = "de|en|es|fr",
     *     "_code" = "[a-zA-Z0-9]{10}",
     *     "guildid" = "([a-zA-Z0-9]+-)+[a-zA-Z0-9]+",
     *     "folder" = "guild(_stash)?"
     * })
     * @param         $_code
     * @param         $folder
     * @param         $guildid
     * @param Request $request
     * @return Response|NotFoundHttpException
     */
    public function guildStashContentAction($_code, $folder, $guildid, Request $request)
    {
        try {
            $context = $this->getContext($_code, $folder . '/' . $guildid, true);
            if (!isset($this->guilds[$guildid])) {
                return $this->createNotFoundException();
            }
            $context['guild'] = $this->guilds[$guildid];
            return $this->render($folder . '/content.html.twig', $context);
        } catch (AccessNotAllowedException $ex) {
            return $this->render('error-access-not-allowed.html.twig');
        }
    }

    /**
     *
     * @param string $_code
     * @param string $page
     * @param bool   $ownerMandatory
     * @return array
     * @throws AccessNotAllowedException
     */
    protected function getContext($_code, $page, $ownerMandatory = false)
    {
        $statistics = null;
        if (empty($this->token)) {
            $this->token = $this->getTokenRepository()->findOneByCode($_code);
            if (empty($this->token)) {
                throw $this->createNotFoundException('Unknown code.');
            }
            $this->isOwner = $this->isTokenOwner($this->token);
            if (!$this->isOwner && $ownerMandatory && $page === null ||
                !$this->isOwner && $ownerMandatory && $page !== null && !$this->token->hasRight($page)) {
                throw new AccessNotAllowedException();
            }
            if (!$this->checkToken($this->token)) {
                throw $this->createNotFoundException('The account is invalid or the official GW2 API is down. Try again later.');
            }
            $this->account    = $this->getAccount($this->token);
            $this->characters = $this->getCharacters($this->account);
            $this->guilds     = $this->getGuilds($this->account);
            $this->menu       = new MenuList($this->getTranslator(), $this->characters, $this->guilds);
            if ($page !== null && !$this->menu->pageExists($page)) {
                throw $this->createNotFoundException('Unknown page.');
            }
            $statistics = new Statistics($this, $this->account);
            // 
            // 2017-03-09 disabled dynamic calculation
            // cron will now compute stats
            // 
//            if (!$this->token->hasRight('other.disable_statistics') && $this->token->isValid()) {
//                $statistics->calculateStatistics();
//            }
        }
        return [
            'page'       => $page,
            'page_name'  => $this->getMenu()->pageName($page),
            'owner'      => $this->isOwner,
            'user'       => $this->token,
            'token'      => $this->token,
            'code'       => $this->token->getCode(),
            'account'    => $this->account,
            'characters' => $this->characters,
            'statistics' => $statistics,
        ];
    }

    protected function render($view, array $parameters = [], Response $response = null)
    {
        if ($this->token) {
            $parameters['owner']      = $this->isOwner;
            $parameters['user']       = $this->token;
            $parameters['token']      = $this->token;
            $parameters['code']       = $this->token->getCode();
            $parameters['account']    = $this->account;
            $parameters['characters'] = $this->characters;
        }
        return parent::render($view, $parameters, $response);
    }

    /**
     *
     * @return array
     */
    public function getBreadcrumb()
    {
        if ($this->token && $this->getMenu() ) {
            $code = $this->token->getCode();
            $path = rawurldecode($this->getRequest()->getPathInfo());
            foreach ($this->getMenu() as $menu) {
                /* @var $menu Menu */
                foreach ($menu->getItems() as $item) {
                    /* @var $item MenuItem */
                    if ($item->getUri() && '/' . $this->getTranslator()->getLocale() . '/' . $code . '/' . $item->getUri() === $path) {
                        return [$menu->getLabel(), $item->getLabel()];
                    }
                }
            }
        }
        return null;
    }

    /**
     *
     * @return MenuList
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     *
     * @return array
     */
    public function getPermissionsList()
    {
        return Account::permissionsList();
    }

    /**
     *
     * @param string $right
     * @param string $permission
     * @return boolean
     */
    public function isAllowed($right, $permission = null)
    {
        if ($permission) {
            if (empty($this->token) || !$this->account->hasPermission($permission)) {
                return false;
            }
        }
        if ($this->isOwner || $this->token && $this->token->hasRight($right)) {
            return true;
        }
        return false;
    }

    /**
     *
     * @param string $name
     * @return boolean
     */
    public function isAllowedCharacter($name)
    {
        $name = (string)$name;
        if ($this->isOwner) {
            return true;
        }
        if (empty($this->token)) {
            return false;
        }
        if (!$this->token->hasRight('other.limit_characters')) {
            return true;
        }
        return $this->token->hasRight('character/' . $name);
    }

    /**
     *
     * @param string $guildid
     * @return boolean
     */
    public function isAllowedGuildStash($guildid)
    {
        $guildid = (string)$guildid;
        if ($this->isOwner) {
            return true;
        }
        if (empty($this->token)) {
            return false;
        }
        return $this->token->hasRight('guild_stash/' . $guildid);
    }

    /**
     * @return string
     */
    function getViewPrefix()
    {
        return 'pages/';
    }
}
