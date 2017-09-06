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
use AppBundle\Repository\TokenRepository;
use Arnapou\GW2Api\Exception\InvalidTokenException;
use Arnapou\GW2Api\Exception\MissingPermissionException;
use Gw2tool\Account;
use Gw2tool\Gw2ApiEnvironmentTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Translator;

abstract class AbstractController extends Controller
{

    use Gw2ApiEnvironmentTrait;

    /**
     *
     * @var array
     */
    private $cookieUsers = null;

    /**
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    /**
     *
     * @param Token $token
     * @return boolean
     */
    protected function isTokenOwner(Token $token)
    {
        foreach ($this->getCookieTokens() as $value) {
            if ($token->getToken() == $value) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param string $token
     * @return Account
     */
    protected function getAccount($token)
    {
        $env = $this->getGwEnvironment()->setAccessToken((string)$token);
        return new Account($env);
    }

    /**
     *
     * @param Account $account
     * @return array
     */
    protected function getCharacters(Account $account)
    {
        $characters = [];
        foreach ($account->getCharacters() as $character) {
            $characters[$character->getName()] = $character;
        }
        ksort($characters);
        return $characters;
    }

    /**
     *
     * @param Account $account
     * @return array
     */
    protected function getGuilds(Account $account)
    {
        $guilds = [];
        foreach ($account->getGuilds(true) as $guild) {
            $guilds[$guild->getId()] = $guild;
        }
        return $guilds;
    }

    /**
     *
     * @param Token $token
     * @return boolean
     */
    public function checkToken(Token $token)
    {
        try {
            $account = $this->getAccount($token);
            $token->setName($account->getName());
            $token->setIsValid(true);
            $token->updateLastaccess();
        } catch (InvalidTokenException $e) {
            $token->setIsValid(false);
        } catch (MissingPermissionException $e) {
            $token->setIsValid(false);
        } catch (\Exception $e) {
            $token->setIsValid(false);
        }
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($token);
        $manager->flush();
        return $token->isValid();
    }

    public function getConnection()
    {
        return $this->getDoctrine()->getConnection();
    }

    /**
     *
     * @return TokenRepository
     */
    public function getTokenRepository()
    {
        return $this->getDoctrine()->getRepository(Token::class);
    }

    /**
     *
     * @return array
     */
    public function getLangs()
    {
        return $this->getParameter('locales');
    }

    /**
     *
     * @return array
     */
    public function getCookieTokens()
    {
        $cookie = $this->getRequest()->cookies->get('accesstoken');
        if (empty($cookie)) {
            return [];
        }
        return explode('|', $cookie);
    }

    /**
     *
     * @return array
     */
    public function getCookieUsers()
    {
        if ($this->cookieUsers === null) {
            $savedAccessToken = $this->getGwEnvironment()->getAccessToken();
            $items = [];
            $repo = $this->getTokenRepository();
            foreach ($this->getCookieTokens() as $token) {
                $object = $repo->findOneByToken($token);
                if ($object && $this->checkToken($object)) {
                    $items[] = $object;
                }
            }
            $this->cookieUsers = $items;
            $this->getGwEnvironment()->setAccessToken($savedAccessToken);
        }
        return $this->cookieUsers;
    }

    /**
     *
     * @return string
     */
    public function getDataPath()
    {
        return $this->get('kernel')->getRootDir() . '/../data';
    }

    protected function render($view, array $parameters = [], Response $response = null)
    {
        $view = $this->getViewPrefix() . $view;
        if (strpos($view, ':') === false) {
            $view = 'AppBundle::' . $view;
        }
        $parameters['lang'] = $this->getTranslator()->getLocale();
        $parameters['module'] = $this;
        return parent::render($view, $parameters, $response);
    }

    /**
     * @return string
     */
    abstract function getViewPrefix();

    /**
     *
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->get('translator');
    }

    /**
     * Translates the given message.
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     *
     * @return string The translated string
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->get('translator')->trans($id, $parameters, $domain, $locale);
    }

    /**
     * Translates the given choice message by choosing a translation according to a number.
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param int         $number     The number to use to find the indice of the message
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     *
     * @return string The translated string
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->getTranslator()->transChoice($id, $number, $parameters, $domain, $locale);
    }

    /**
     *
     * @return string
     */
    public function getEnv()
    {
        return $this->get('kernel')->getEnvironment();
    }
}
