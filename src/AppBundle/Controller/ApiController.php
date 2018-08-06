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
use Arnapou\DeltaConnected\BuildTemplate;
use Arnapou\GW2Api\Exception\ApiUnavailableException;
use Arnapou\GW2Api\Exception\InvalidTokenException;
use Arnapou\GW2Api\Exception\MissingPermissionException;
use Arnapou\GW2Api\Storage\MongoStorage;
use function Arnapou\GW2Api\chatlink_item;
use function Gw2tool\image;
use Gw2tool\MenuList;
use Gw2tool\Statistics;
use MongoDB\BSON\Regex;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends AbstractController
{
    /**
     *
     * @Route("/api/save-rights")
     * @deprecated
     * @param Request $request
     * @return JsonResponse
     */
    public function saveRightsAction(Request $request)
    {
        return $this->rightsSaveAction($request);
    }

    /**
     *
     * @Route("/api/rights-list")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rightsListAction(Request $request)
    {
        try {
            $token = $this->getAuthentifiedToken($request);
            return new JsonResponse([
                'rights' => $this->getAllowedRights($token),
            ]);
        } catch (\Exception $ex) {
            return $this->createJsonError($ex);
        }
    }

    /**
     *
     * @Route("/api/rights-save")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rightsSaveAction(Request $request)
    {
        try {
            $token   = $this->getAuthentifiedTokenMandatory($request, true);
            $manager = $this->getDoctrine()->getManager();
            $rights  = $request->get('rights');
            if (!is_array($rights) || empty($rights)) {
                $rights = [];
            }
            $allowedRights   = $this->getAllowedRights($token);
            $sanitizedRights = [];
            foreach ($rights as $right) {
                if (in_array($right, $allowedRights)) {
                    $sanitizedRights[] = $right;
                }
            }
            $token->setRights($sanitizedRights);
            $manager->persist($token);
            $manager->flush();

            if ($token->hasRight('other.disable_statistics') && $token->isValid()) {
                $stats = new Statistics($this, $this->getAccount($token));
                $stats->removeStatistics();
            }

            return new JsonResponse([
                'ok'      => true,
                'message' => $this->trans('global.saved_preferences'),
            ]);
        } catch (\Exception $ex) {
            return $this->createJsonError($ex);
        }
    }

    /**
     *
     * @Route("/api/token-list")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function tokenListAction(Request $request)
    {
        try {
            $search = $request->get('search');

            $items = [];
            foreach ($this->getCookieUsers() as $token) {
                /** @var Token $token */
                if ($search &&
                    stripos($token->getCode(), $search) === false &&
                    stripos($token->getName(), $search) === false &&
                    stripos($token->getToken(), $search) === false
                ) {
                    continue;
                }
                $items[] = [
                    'code'  => $token->getCode(),
                    'name'  => $token->getName(),
                    'token' => $token->getToken(),
                ];
            }

            return new JsonResponse($items);
        } catch (InvalidTokenException $ex) {
            return $this->createJsonError($this->trans('error.invalid-token'));
        } catch (MissingPermissionException $ex) {
            return $this->createJsonError($this->trans('error.invalid-token'));
        } catch (\Exception $ex) {
            return $this->createJsonError($ex);
        }
    }

    /**
     *
     * @Route("/api/token-replace")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function tokenReplaceAction(Request $request)
    {
        try {
            $newtoken = trim($request->get('newtoken'));
            $token    = $this->getAuthentifiedTokenMandatory($request, true);
            $manager  = $this->getDoctrine()->getManager();
            if ($token->getToken() == $newtoken) {
                throw new \Exception($this->trans('error.token-is-same'));
            }
            $alreadyExists = $this->getTokenRepository()->findOneByToken($newtoken);
            if ($alreadyExists) {
                throw new \Exception($this->trans('error.token-already-exists'));
            }
            // check new token
            $account = $this->getAccount($newtoken);
            if (empty($account->getName())) {
                throw new InvalidTokenException();
            }
            if ($token->getName() !== $account->getName()) {
                throw new \Exception($this->trans('error.mismatch-account'));
            }

            $tokens = [$newtoken];
            foreach ($this->getCookieTokens() as $value) {
                if ($value !== $token->getToken()) {
                    $tokens[] = $value;
                }
            }

            $token->setToken($newtoken);
            $manager->persist($token);
            $manager->flush();

            return new JsonResponse([
                'ok'     => true,
                'tokens' => array_unique($tokens),
            ]);
        } catch (InvalidTokenException $ex) {
            return $this->createJsonError($this->trans('error.invalid-token'));
        } catch (MissingPermissionException $ex) {
            return $this->createJsonError($this->trans('error.invalid-token'));
        } catch (\Exception $ex) {
            return $this->createJsonError($ex);
        }
    }

    /**
     *
     * @Route("/api/token-delete")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function tokenDeleteAction(Request $request)
    {
        try {
            $token   = $this->getAuthentifiedTokenMandatory($request, true);
            $manager = $this->getDoctrine()->getManager();

            $tokens = [];
            foreach ($this->getCookieTokens() as $value) {
                if ($value !== $token->getToken()) {
                    $tokens[] = $value;
                }
            }

            $manager->remove($token);
            $manager->flush();

            return new JsonResponse([
                'ok'     => true,
                'tokens' => array_unique($tokens),
            ]);
        } catch (\Exception $ex) {
            return $this->createJsonError($ex);
        }
    }

    /**
     *
     * @Route("/api/token-check")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function tokenCheckAction(Request $request)
    {
        try {
            $paramToken = trim($request->get('token'));
            if (empty($paramToken)) {
                throw new InvalidTokenException('No token was provided.');
            } elseif (!preg_match('!^[A-F0-9-]{70,80}$!', $paramToken)) {
                throw new InvalidTokenException('Invalid token.');
            }
            $repo        = $this->getTokenRepository();
            $entityToken = $repo->findOneByToken($paramToken);
            if (empty($entityToken)) {
                $entityToken = $repo->newToken($paramToken);
            }
            if (!$this->checkToken($entityToken, $exception)) {
                if ($exception instanceof ApiUnavailableException) {
                    throw $exception;
                }
                throw new InvalidTokenException('Invalid token.');
            }

            $tokens = [$entityToken->getToken()];
            foreach ($this->getCookieTokens() as $token) {
                $object = $repo->findOneByToken($token);
                if ($object && $this->checkToken($object)) {
                    $tokens[] = $object->getToken();
                }
            }

            return new JsonResponse([
                'code'   => $entityToken->getCode(),
                'tokens' => array_unique($tokens),
            ]);
        } catch (InvalidTokenException $e) {
            return $this->createJsonError($this->trans('error.invalid-token'));
        } catch (MissingPermissionException $e) {
            return $this->createJsonError('The token is missing "' . $e->getMessage() . '" permission.');
        } catch (ApiUnavailableException $e) {
            return $this->createJsonError($e->getMessage());
        }
    }

    /**
     *
     * @see      https://docs.mongodb.com/php-library/master/tutorial/
     *
     * @Route("/api/chatlink-generate")
     * @param Request $request
     * @return JsonResponse
     */
    public function chatlinkGenerateAction(Request $request)
    {
        try {
            $item     = $this->getRequestInteger($request, 'item');
            $skin     = $this->getRequestInteger($request, 'skin', 0);
            $upgrade1 = $this->getRequestInteger($request, 'upgrade1', 0);
            $upgrade2 = $this->getRequestInteger($request, 'upgrade2', 0);
            $quantity = $this->getRequestInteger($request, 'quantity', 0);
            return new JsonResponse([
                'chatlink' => chatlink_item($item, $skin, $upgrade1, $upgrade2, $quantity),
            ]);
        } catch (\Exception $ex) {
            return $this->createJsonError($ex);
        }
    }

    /**
     *
     * @Route("/api/arcdps-traits")
     * @param Request $request
     * @return JsonResponse
     */
    public function arcdpsTraitsAction(Request $request)
    {
        try {
            $lang          = $this->getRequestLang($request);
            $mode          = $request->get('mode', 'pve');
            $profession    = $request->get('profession');
            $speIds        = $request->get('specializations');
            $traitIds      = $request->get('traits');
            $buildTemplate = new BuildTemplate($this->getGwEnvironment($lang));
            return new JsonResponse([
                'template' => $buildTemplate->getTraits($profession, $speIds, $traitIds, $mode),
            ]);
        } catch (\Exception $ex) {
            return $this->createJsonError($ex);
        }
    }

    /**
     *
     * @see https://docs.mongodb.com/php-library/master/tutorial/
     *
     * @Route("/api/chatlink-skins")
     * @param Request $request
     * @return JsonResponse
     */
    public function chatlinkSkinsSearchAction(Request $request)
    {
        try {
            $lang    = $this->getRequestLang($request);
            $query   = (string)$request->get('name');
            $env     = $this->getGwEnvironment($lang);
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
        } catch (\Exception $ex) {
            return $this->createJsonError($ex);
        }
    }

    /**
     *
     * @see https://docs.mongodb.com/php-library/master/tutorial/
     *
     * @Route("/api/chatlink-items")
     * @param Request $request
     * @param null    $type
     * @return JsonResponse
     */
    public function chatlinkItemsSearchAction(Request $request, $type = null)
    {
        try {
            $lang    = $this->getRequestLang($request);
            $query   = (string)$request->get('name');
            $env     = $this->getGwEnvironment($lang);
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
        } catch (\Exception $ex) {
            return $this->createJsonError($ex);
        }
    }

    /**
     *
     * @Route("/api/chatlink-upgrades")
     * @param Request $request
     * @return JsonResponse
     */
    public function chatlinkUpgradesSearchAction(Request $request)
    {
        return $this->chatlinkItemsSearchAction($request, 'UpgradeComponent');
    }

    /**
     * @param Request $request
     * @param string  $name
     * @param null    $default
     * @return int
     * @throws \Exception
     */
    private function getRequestInteger(Request $request, $name, $default = null)
    {
        $int = $request->get($name, $default);
        if ($int === null && $default === null) {
            throw new \Exception("Parameter '$name' is a mandatory integer");
        }
        if (!ctype_digit((string)$int)) {
            throw new \Exception("Parameter '$name' is should be an integer");
        }
        return (int)$int;
    }

    /**
     * @param Request $request
     * @return string
     * @throws \Exception
     */
    private function getRequestLang(Request $request)
    {
        $lang    = $request->get('lang', $this->getTranslator()->getLocale());
        $locales = $this->getParameter('locales');
        if (!in_array($lang, $locales)) {
            throw new \Exception(
                "Language '$lang' not supported'. Allowed languages are " . implode(', ', $locales)
            );
        }
        return $lang;
    }

    /**
     * @param     $error
     * @param int $status
     * @return JsonResponse
     */
    private function createJsonError($error, $status = 200)
    {
        if ($error instanceof \Exception) {
            $error = ['message' => $error->getMessage()];
        }
        if (!is_array($error)) {
            $error = ['message' => $error];
        }
        return new JsonResponse(['error' => $error], $status);
    }

    /**
     * @param $query
     * @return string
     */
    private function queryToRegex($query)
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
     * @param Request $request
     * @return Token|null
     * @throws \Exception
     */
    private function getAuthentifiedToken(Request $request)
    {
        $paramCode = $request->get('code');
        if (!empty($paramCode)) {
            $token = $this->getTokenRepository()->findOneByCode((string)$paramCode);
            if (empty($token)) {
                throw new \Exception('Unknown code.');
            }
            return $token;
        }

        $paramToken = $request->get('token');
        if (!empty($paramToken)) {
            $token = $this->getTokenRepository()->findOneByToken((string)$paramToken);
            if (empty($token)) {
                throw new \Exception('Unknown token.');
            }
            return $token;
        }

        $tokenCookies = $this->getCookieTokens();
        if (count($tokenCookies) == 1) {
            $token = $this->getTokenRepository()->findOneByToken((string)$tokenCookies[0]);
            if (empty($token)) {
                throw new \Exception('Unknown token stored as cookie.');
            }
            return $token;
        }

        return null;
    }

    /**
     * @param Request $request
     * @param bool    $owner
     * @return Token
     * @throws \Exception
     */
    private function getAuthentifiedTokenMandatory(Request $request, $owner = false)
    {
        $token = $this->getAuthentifiedToken($request);
        if (empty($token)) {
            throw new \Exception("Authentification required through 'code' or 'token' parameter");
        }
        if ($owner && !$this->isTokenOwner($token)) {
            throw new \Exception($this->trans('error.not-owner'));
        }
        return $token;
    }


    /**
     * @param $token
     * @return array
     */
    private function getAllowedRights($token)
    {
        if ($token && $this->isTokenOwner($token)) {
            $account    = $this->getAccount($token);
            $characters = $this->getCharacters($account);
            $guilds     = $this->getGuilds($account);
        } else {
            $characters = [];
            $guilds     = [];
        }
        $menu            = new MenuList($this->getTranslator(), $characters, $guilds);
        $allowedRights   = array_keys($menu->getRights());
        $allowedRights[] = 'other.limit_characters';
        $allowedRights[] = 'other.disable_statistics';
        return $allowedRights;
    }

    /**
     * @return string
     */
    public function getViewPrefix()
    {
        return '';
    }
}
