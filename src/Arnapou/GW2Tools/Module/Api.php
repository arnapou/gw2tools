<?php

/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\GW2Tools\Module;

use Arnapou\GW2Tools\ApiClient;
use Arnapou\GW2Tools\Exception\TokenException;
use Arnapou\GW2Tools\FileVault;
use Arnapou\GW2Tools\TokenVault;
use Arnapou\Toolbox\Functions\Directory;
use Arnapou\Toolbox\Http\ResponseJson;
use Arnapou\Toolbox\Http\ResponsePng;

class Api extends \Arnapou\GW2Tools\AbstractModule {

	protected $menu = [
		'account'		 => [
			'label'	 => 'Account',
			'owner'	 => false,
		],
		'characters'	 => [
			'label'	 => 'Characters',
			'owner'	 => false,
		],
		'stuff'			 => [
			'label'	 => 'Stuff stats',
			'owner'	 => false,
		],
		'attributes'	 => [
			'label'	 => 'Attributes',
			'owner'	 => false,
		],
		'bank'			 => [
			'label'	 => 'Bank',
			'owner'	 => true,
		],
		'collectibles'	 => [
			'label'	 => 'Collectibles',
			'owner'	 => true,
		],
	];

	public function configure() {
		parent::configure();

		$regexpCode = '[A-Za-z0-9]{10}';
		$regexpMenu = implode('|', array_keys($this->menu));

		$this->addRoute('', [$this, 'routeHome']);
		$this->addRoute('token-check', [$this, 'routeTokenCheck']);
		$this->addRoute('guild-emblem-{id}.png', [$this, 'routeImageGuildEmblem'])->assert('id', '[A-F0-9-]{35,40}');
		$this->addRoute('profession-{id}.png', [$this, 'routeImageProfession'])->assert('id', '[a-z]+');
		$this->addRoute('crafting-{id}.png', [$this, 'routeImageCrafting'])->assert('id', '[a-z]+');
		$this->addRoute('render-file/{id}.png', [$this, 'routeImageRenderFile'])->assert('id', '[A-F0-9]+/[0-9]+');
		$this->addRoute('{code}/', [$this, 'routeHomeToken'])->assert('code', $regexpCode);
		$this->addRoute('{code}/{menu}/', [$this, 'routeMenu'])->assert('code', $regexpCode)->assert('menu', $regexpMenu);
		$this->addRoute('{code}/{menu}/content.html', [$this, 'routeMenuContent'])->assert('code', $regexpCode)->assert('menu', $regexpMenu);
		$this->addRoute('{code}/character/{name}', [$this, 'routeCharacter'])->assert('code', $regexpCode);
		$this->addRoute('{code}/character/{name}.html', [$this, 'routeCharacterContent'])->assert('code', $regexpCode);
	}

	public function getAccessToken($code) {
		$token = $this->getService()->getRequest()->cookies->get('accesstoken');
		$vault = TokenVault::getInstance();
		if ($vault->exists($token)) {
			if ($code == $vault->get($token)) {
				return $token;
			}
		}
		return null;
	}

	public function getMenu() {
		return $this->menu;
	}

	public function routeHome() {
		return $this->renderPage('home.twig');
	}

	public function routeImageCrafting($id) {
		return $this->renderImage(function(ApiClient $apiClient) use ($id) {
				$map = [
					'armorsmith'	 => 'map_crafting_armorsmith',
					'artificer'		 => 'map_crafting_artificer',
					'chef'			 => 'map_crafting_cook',
					'huntsman'		 => 'map_crafting_huntsman',
					'jeweler'		 => 'map_crafting_jeweler',
					'leatherworker'	 => 'map_crafting_leatherworker',
					'tailor'		 => 'map_crafting_tailor',
					'weaponsmith'	 => 'map_crafting_weaponsmith',
				];
				if (isset($map[$id])) {
					$icon = $apiClient->getFileIcon($map[$id]);
					return FileVault::getVaultCrafting()->getResponse($icon);
				}
			});
	}

	public function routeImageRenderFile($id) {
		return $this->renderImage(function(ApiClient $apiClient) use ($id) {
				$url = 'https://render.guildwars2.com/file/' . $id . '.png';
				return FileVault::getVaultRender()->getResponse($url);
			});
	}

	public function routeImageProfession($id) {
		return $this->renderImage(function(ApiClient $apiClient) use ($id) {
				$icon = $apiClient->getFileIcon('icon_' . $id);
				return FileVault::getVaultProfessions()->getResponse($icon);
			});
	}

	public function routeImageGuildEmblem($id) {
		return $this->renderImage(function(ApiClient $apiClient) use ($id) {
				$guild = $apiClient->getGuild($id);
				if ($guild) {
					$url = 'http://data.gw2.fr/guild-emblem/name/' . rawurlencode($guild['name']) . '/128.png';
					return FileVault::getVaultEmblems()->getResponse($url);
				}
			});
	}

	public function routeHomeToken($code) {
		$this->apiClientFromCode($code);
		return $this->getService()->returnResponseRedirect('./account/');
	}

	public function routeCharacter($code, $name) {
		try {
			$apiClient = $this->apiClientFromCode($code);
			if ($apiClient) {
				$characters = $apiClient->getCharacters();
				$name = rawurldecode($name);
				if (isset($characters[$name])) {
					$context = [
						'apiclient'	 => $apiClient,
						'account'	 => $apiClient->v2_account() + ['token' => $this->getAccessToken($code)],
						'char'		 => $characters[$name],
					];
					return $this->renderPage('character/page.twig', $context);
				}
			}
		}
		catch (TokenException $e) {
			return $this->renderPage('home.twig', ['token_error' => $e->getMessage()]);
		}
	}

	public function routeCharacterContent($code, $name) {
		try {
			$apiClient = $this->apiClientFromCode($code);
			if ($apiClient) {
				$characters = $apiClient->getCharacters();
				$name = rawurldecode($name);
				if (isset($characters[$name])) {
					$context = [
						'apiclient'	 => $apiClient,
						'account'	 => $apiClient->v2_account() + ['token' => $this->getAccessToken($code)],
						'char'		 => $characters[$name],
					];
					return $this->renderPage('character/content.twig', $context);
				}
			}
		}
		catch (TokenException $e) {
			return $this->renderPage('token-error.twig', ['token_error' => $e->getMessage()]);
		}
	}

	public function routeMenu($code, $menu) {
		try {
			$apiClient = $this->apiClientFromCode($code);
			if ($apiClient) {
				$context = [
					'apiclient'	 => $apiClient,
					'account'	 => $apiClient->v2_account() + ['token' => $this->getAccessToken($code)],
					'menu'		 => $menu,
				];
				return $this->renderPage($menu . '/page.twig', $context);
			}
		}
		catch (TokenException $e) {
			return $this->renderPage('home.twig', ['token_error' => $e->getMessage()]);
		}
	}

	public function routeMenuContent($code, $menu) {
		try {
			$apiClient = $this->apiClientFromCode($code);
			if ($apiClient) {
				$context = [
					'apiclient'	 => $apiClient,
					'account'	 => $apiClient->v2_account() + ['token' => $this->getAccessToken($code)],
					'menu'		 => $menu,
				];
				return $this->renderPage($menu . '/content.twig', $context);
			}
		}
		catch (TokenException $e) {
			return $this->renderPage('token-error.twig', ['token_error' => $e->getMessage()]);
		}
	}

	public function routeTokenCheck() {
		$data = [];
		try {
			$token = $this->getService()->getRequest()->get('token');
			if (empty($token)) {
				throw new TokenException('No token was provided.');
			}
			elseif (!preg_match('!^[A-F0-9-]{70,80}$!', $token)) {
				throw new TokenException('Invalid token.');
			}
			$vault = TokenVault::getInstance();

			$apiClient = $this->apiClientFromToken($token); // if no error is raised, the token is valid

			if ($vault->exists($token)) {
				$code = $vault->get($token);
			}
			else {
				$code = $vault->newKey();
				$vault->set($code, $token);
			}

			$data['code'] = $code;
		}
		catch (TokenException $e) {
			$data['error'] = $e->getMessage();
		}
		return new ResponseJson($data);
	}

	/**
	 * 
	 * @return ApiClient
	 */
	protected function apiClientFromCode($code) {
		$vault = TokenVault::getInstance();
		if (!$vault->exists($code)) {
			return null;
		}
		return $this->apiClientFromToken($vault->get($code));
	}

	/**
	 * 
	 * @return ApiClient
	 */
	protected function apiClientFromToken($token = null) {
		$path = $this->getService()->getPathCache() . '/gw2api';
		Directory::createIfNotExists($path);

		$client = ApiClient::EN($path);

//		// debug purpose
//		$client->getClientV2()->getRequestManager()->getEventListener()->bind('onRequest', function($event) {
//			$line = round($event['time'] * 1000) . " ms \t\t" . $event['uri'];
//			file_put_contents(__DIR__ . '/../../../../requests.log', $line . "\n", FILE_APPEND);
//		});

		if ($token) {
			$client->setAccessToken($token);
		}
		return $client;
	}

	protected function renderImage($callback) {
		try {
			$apiClient = $this->apiClientFromToken();
			if ($apiClient) {
				return $callback($apiClient);
			}
		}
		catch (\Exception $e) {
			
		}
	}

}
