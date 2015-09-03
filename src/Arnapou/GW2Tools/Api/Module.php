<?php

/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\GW2Tools\Api;

use Arnapou\GW2Api\Core\AbstractClient;
use Arnapou\GW2Api\Exception\InvalidTokenException;
use Arnapou\GW2Api\Exception\MissingPermissionException;
use Arnapou\GW2Api\Model\Guild;
use Arnapou\GW2Api\SimpleClient;
use Arnapou\GW2Tools\Exception\AccessNotAllowedException;
use Arnapou\GW2Tools\Service;
use Arnapou\Toolbox\Http\ResponseJson;

class Module extends \Arnapou\GW2Tools\AbstractModule {

    /**
     *
     * @var User
     */
    protected $user;

    /**
     *
     * @var array
     */
    protected $menu;

    /**
     *
     * @var boolean
     */
    protected $isOwner;

    public function configure() {
        parent::configure();

        // generic
        $this->addRoute('', [$this, 'routeIndex']);
        $this->addRoute('technical-infos', [$this, 'routeTechnicalInfos']);
        $this->addRoute('token-check', [$this, 'routeTokenCheck']);

        // proxy images
        $this->addRoute('guild/{id}.png', [$this, 'routeImageGuild'])->assert('id', '[A-F0-9-]{35,40}');
        $this->addRoute('proxy/{id}.png', [$this, 'routeImageProxy'])->assert('id', '[A-F0-9]+/[0-9]+');

        // user space
        $regexpCode = '[A-Za-z0-9]{10}';

        $this->addRoute('{code}/{any}', [$this, 'routeHome'])->assert('code', $regexpCode)->assert('any', '.*');
        $this->addRoute('{code}/{page}/', [$this, 'routePage'])->assert('code', $regexpCode)->assert('page', '[a-z0-9_]+');
        $this->addRoute('{code}/{page}/content.html', [$this, 'routePageContent'])->assert('code', $regexpCode)->assert('page', '[a-z0-9_]+');
        $this->addRoute('{code}/account/save-rights', [$this, 'routeSaveRights'], 'POST')->assert('code', $regexpCode);
        $this->addRoute('{code}/account/delete-token', [$this, 'routeDeleteToken'], 'POST')->assert('code', $regexpCode);
        $this->addRoute('{code}/character/{name}', [$this, 'routeCharacter'])->assert('code', $regexpCode);
        $this->addRoute('{code}/character/{name}.html', [$this, 'routeCharacterContent'])->assert('code', $regexpCode);
    }

    /**
     * 
     * @return MenuList
     */
    public function getMenu() {
        if (!isset($this->menu)) {
            $this->menu = new MenuList($this->user ? $this->user->getAccount() : null);
        }
        return $this->menu;
    }

    /**
     * 
     * @return array
     */
    public function getBreadcrumb() {
        if ($this->user) {
            $code = $this->user->getCode();
            $path = rawurldecode($this->getService()->getRequest()->getPathInfo());
            foreach ($this->getMenu() as /* @var $menu Menu */ $menu) {
                foreach ($menu->getItems() as $item) {
                    if (isset($item['uri']))
                        if (isset($item['uri']) && '/api/' . $code . '/' . $item['uri'] === $path) {
                            return [$menu->getLabel(), $item['label']];
                        }
                }
            }
        }
        return null;
    }

    /**
     * 
     * @return boolean
     */
    public function isOwner() {
        return $this->isOwner;
    }

    /**
     * 
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * 
     */
    public function routeSaveRights() {
        if ($this->user && $this->isOwner) {
            $rights = $this->getService()->getRequest()->get('rights');
            if (!is_array($rights) || empty($rights)) {
                $rights = [];
            }
            $allowedRights = array_keys($this->getMenu()->getRights());
            $sanitizedRights = [];
            foreach ($rights as $right) {
                if (in_array($right, $allowedRights)) {
                    $sanitizedRights[] = $right;
                }
            }
            $this->user->set('rights', $sanitizedRights);
            $this->user->save();
        }
        return new ResponseJson(['ok' => true]);
    }

    /**
     * 
     */
    public function routeDeleteToken() {
        if ($this->user && $this->isOwner) {
            $this->user->delete();
        }
        return new ResponseJson(['ok' => true]);
    }

    /**
     * 
     * @param string $code
     * @return User
     */
    public function getUserByCode($code) {
        $user = User::findByCode($code);
        if ($user) {
            $user->setLastaccess()->save();
            return $user;
        }
        return null;
    }

    /**
     * 
     * @param string $token
     * @return User
     */
    public function getUserByToken($token) {
        $user = User::findByToken($token);
        if ($user) {
            return $user;
        }
        return null;
    }

    /**
     * 
     * @return string
     */
    public function routeTechnicalInfos() {
        return $this->renderPage('technical-infos.twig');
    }

    /**
     * 
     * @return string
     */
    public function routeIndex() {
        return $this->renderPage('home.twig');
    }

    /**
     * 
     * @param string $code
     * @param string $any
     * @return string
     */
    public function routeHome($code, $any = null) {
        $user = $this->getUserByCode($code);
        if ($user) {
            $this->user = $user;
            $this->isOwner = in_array($user->getToken(), $this->getCookieTokens());
            if ($any === '' || $any === null) {
                return $this->getService()->returnResponseRedirect('./account/');
            }
        }
    }

    /**
     * 
     * @param string $id
     * @return \Arnapou\Toolbox\Http\Response
     */
    public function routeImageProxy($id) {
        try {
            $url = 'https://render.guildwars2.com/file/' . $id . '.png';
            return FileVault::getVaultProxy()->getResponse($url);
        }
        catch (Exception $e) {
            
        }
    }

    protected function renderPage($template, $context = array()) {
        try {
            $context['request'] = $this->getService()->getRequest();
            return parent::renderPage($template, $context);
        }
        catch (\Twig_Error_Runtime $e) {
            $previous = $e->getPrevious();
            if (empty($previous) || get_class($previous) !== MissingPermissionException::class) {
                throw $e;
            }
            return $this->renderPage('error-no-permission.twig', ['permission' => $previous->getMessage()]);
        }
    }

    /**
     * 
     * @param string $id
     * @return \Arnapou\Toolbox\Http\Response
     */
    public function routeImageGuild($id) {
        try {
            $client = Service::getInstance()->newSimpleClient();
            $guild = new Guild($client, $id);

            $url = $guild->getIconLinkGw2Png();
            if ($url) {
                return FileVault::getVaultEmblems()->getResponse($url);
            }
        }
        catch (Exception $e) {
            
        }
    }

    /**
     * 
     * @return array
     */
    public function getCookieTokens() {
        $cookie = $this->getService()->getRequest()->cookies->get('accesstoken');
        if (empty($cookie)) {
            return [];
        }
        return explode('|', $cookie);
    }

    /**
     * 
     * @return array
     */
    public function getCookieUsers($all = false) {
        $users = [];
        foreach ($this->getCookieTokens() as $token) {
            try {
                $user = $this->getUserByToken($token);
                if ($user && $user->checkAccount()) {
                    if ($all || empty($this->user) || $user->getCode() !== $this->user->getCode()) {
                        $users[] = $user;
                    }
                }
            }
            catch (Exception $e) {
                
            }
        }
        usort($users, function($a, $b) {
            return strcmp($a->getAccount()->getName(), $b->getAccount()->getName());
        });
        return $users;
    }

    /**
     * 
     * @param string $code
     * @param string $page
     * @return array
     */
    protected function getContext($page) {
        if ($this->user) {
            return [
                'page'    => $page,
                'user'    => $this->user,
                'code'    => $this->user->getCode(),
                'account' => $this->user->getAccount(),
            ];
        }
        return null;
    }

    /**
     * 
     * @param string $code
     * @param string $name
     * @return string
     */
    public function routeCharacter($code, $name) {
        try {
            $context = $this->getContext('character');
            if ($context) {
                $name = rawurldecode($name);
                if (in_array($name, $context['account']->getCharacterNames())) {
                    $context['character_name'] = $name;
                    return $this->renderPage('character/page.twig', $context);
                }
            }
        }
        catch (InvalidTokenException $e) {
            return $this->renderPage('home.twig', ['token_error' => $e->getMessage()]);
        }
        catch (MissingPermissionException $e) {
            return $this->renderPage('error-no-permission.twig', ['permission' => $e->getMessage()]);
        }
    }

    /**
     * 
     * @param string $code
     * @param string $name
     * @return string
     */
    public function routeCharacterContent($code, $name) {
        try {
            $this->checkUserRights('character');
            $context = $this->getContext('character');
            if ($context) {
                $name = rawurldecode($name);
                if (in_array($name, $context['account']->getCharacterNames())) {
                    $context['character_name'] = $name;
                    return $this->renderPage('character/content.twig', $context);
                }
            }
        }
        catch (InvalidTokenException $e) {
            return $this->renderPage('error-token.twig', ['token_error' => $e->getMessage()]);
        }
        catch (MissingPermissionException $e) {
            return $this->renderPage('error-no-permission.twig', ['permission' => $e->getMessage()]);
        }
        catch (AccessNotAllowedException $e) {
            return $this->renderPage('error-access-not-allowed.twig');
        }
    }

    /**
     * 
     * @param string $code
     * @param string $page
     * @return string
     */
    public function routePage($code, $page) {
        try {
            $context = $this->getContext($page);
            if ($context) {
                return $this->renderPage($page . '/page.twig', $context);
            }
        }
        catch (InvalidTokenException $e) {
            return $this->renderPage('home.twig', ['token_error' => $e->getMessage()]);
        }
        catch (MissingPermissionException $e) {
            return $this->renderPage('error-no-permission.twig', ['permission' => $e->getMessage()]);
        }
    }

    /**
     * 
     * @param string $code
     * @param string $page
     * @return string
     */
    public function routePageContent($code, $page) {
        try {
            $this->checkUserRights($page);
            $context = $this->getContext($page);
            if ($context) {
                return $this->renderPage($page . '/content.twig', $context);
            }
        }
        catch (InvalidTokenException $e) {
            return $this->renderPage('error-token.twig', ['token_error' => $e->getMessage()]);
        }
        catch (MissingPermissionException $e) {
            return $this->renderPage('error-no-permission.twig', ['permission' => $e->getMessage()]);
        }
        catch (AccessNotAllowedException $e) {
            return $this->renderPage('error-access-not-allowed.twig');
        }
    }

    /**
     * 
     */
    protected function checkUserRights($page) {
        if ($this->user && !$this->isOwner) {
            if (!$this->user->hasRight($page)) {
                throw new AccessNotAllowedException();
            }
        }
    }

    /**
     * 
     * @return array
     */
    public function permissionsList() {
        return Gw2Account::permissionsList();
    }

    /**
     * 
     * @return ResponseJson
     */
    public function routeTokenCheck() {
        $data = [];
        try {
            $token = $this->getService()->getRequest()->get('token');
            if (empty($token)) {
                throw new InvalidTokenException('No token was provided.');
            }
            elseif (!preg_match('!^[A-F0-9-]{70,80}$!', $token)) {
                throw new InvalidTokenException('Invalid token.');
            }

            $user = User::findByToken($token);
            if (empty($user)) {
                $user = User::create($token);
            }
            $code = $user->getCode();

            $data['code'] = $code;
            $data['tokens'] = array_unique(array_merge(array_map(function(User $user) {
                        return $user->getToken();
                    }, $this->getCookieUsers(true)), [$token]));
        }
        catch (InvalidTokenException $e) {
            $data['error'] = $e->getMessage();
        }
        catch (MissingPermissionException $e) {
            $data['error'] = 'The token is missing "' . $e->getMessage() . '" permission.';
        }
        return new ResponseJson($data);
    }

}
