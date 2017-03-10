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
use Arnapou\GW2Api\Exception\InvalidTokenException;
use Arnapou\GW2Api\Exception\MissingPermissionException;
use Gw2tool\MenuList;
use Gw2tool\Statistics;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiController extends AbstractController {

    /**
     * 
     * @Route("/api/save-rights")
     * 
     * @param Request $request
     */
    public function saveRightsAction(Request $request) {
        $return = [];
        try {
            $token   = $this->getOwnerTokenFromCode($request->get('code'));
            $manager = $this->getDoctrine()->getManager();
            $rights  = $request->get('rights');
            if (!is_array($rights) || empty($rights)) {
                $rights = [];
            }
            $account         = $this->getAccount($token);
            $menu            = new MenuList($this->getTranslator(), $this->getCharacters($account), $this->getGuilds($account));
            $allowedRights   = array_keys($menu->getRights());
            $allowedRights[] = 'other.limit_characters';
            $allowedRights[] = 'other.disable_statistics';
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

            $return['ok']      = true;
            $return['message'] = $this->trans('global.saved_preferences');
        }
        catch (\Exception $ex) {
            $return['error'] = $ex->getMessage();
        }
        return new JsonResponse($return);
    }

    /**
     * 
     * @Route("/api/token-replace")
     * 
     * @param Request $request
     */
    public function tokenReplaceAction(Request $request) {
        $return = [];
        try {
            $newtoken = trim($request->get('token'));
            $token    = $this->getOwnerTokenFromCode($request->get('code'));
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

            $return['ok']     = true;
            $return['tokens'] = array_unique($tokens);
        }
        catch (InvalidTokenException $ex) {
            $return['error'] = $this->trans('error.invalid-token');
        }
        catch (MissingPermissionException $ex) {
            $return['error'] = $this->trans('error.invalid-token');
        }
        catch (\Exception $ex) {
            $return['error'] = $ex->getMessage();
        }
        return new JsonResponse($return);
    }

    /**
     * 
     * @Route("/api/token-delete")
     * 
     * @param Request $request
     */
    public function tokenDeleteAction(Request $request) {
        $return = [];
        try {
            $token   = $this->getOwnerTokenFromCode($request->get('code'));
            $manager = $this->getDoctrine()->getManager();

            $tokens = [];
            foreach ($this->getCookieTokens() as $value) {
                if ($value !== $token->getToken()) {
                    $tokens[] = $value;
                }
            }

            $manager->remove($token);
            $manager->flush();

            $return['ok']     = true;
            $return['tokens'] = array_unique($tokens);
        }
        catch (\Exception $ex) {
            $return['error'] = $ex->getMessage();
        }
        return new JsonResponse($return);
    }

    /**
     * 
     * @Route("/api/token-check")
     * 
     * @param Request $request
     */
    public function tokenCheckAction(Request $request) {
        $return = [];
        try {
            $paramToken = trim($request->get('token'));
            if (empty($paramToken)) {
                throw new InvalidTokenException('No token was provided.');
            }
            elseif (!preg_match('!^[A-F0-9-]{70,80}$!', $paramToken)) {
                throw new InvalidTokenException('Invalid token.');
            }
            $repo        = $this->getTokenRepository();
            $entityToken = $repo->findOneByToken($paramToken);
            if (empty($entityToken)) {
                $entityToken = $repo->newToken($paramToken);
            }
            if (!$this->checkToken($entityToken)) {
                throw new InvalidTokenException('Invalid token.');
            }

            $tokens = [$entityToken->getToken()];
            foreach ($this->getCookieTokens() as $token) {
                $object = $repo->findOneByToken($token);
                if ($object && $this->checkToken($object)) {
                    $tokens[] = $object->getToken();
                }
            }

            $return['code']   = $entityToken->getCode();
            $return['tokens'] = array_unique($tokens);
        }
        catch (InvalidTokenException $e) {
            $return['error'] = $e->getMessage();
        }
        catch (MissingPermissionException $e) {
            $return['error'] = 'The token is missing "' . $e->getMessage() . '" permission.';
        }
        return new JsonResponse($return);
    }

    /**
     * 
     * @param string $code
     * @return Token
     */
    protected function getOwnerTokenFromCode($code) {
        $token = $this->getTokenRepository()->findOneByCode((string) $code);
        if (empty($token)) {
            throw new \Exception('Unknown code.');
        }
        if (!$this->isTokenOwner($token)) {
            throw new \Exception($this->trans('error.not-owner'));
        }
        return $token;
    }

}
