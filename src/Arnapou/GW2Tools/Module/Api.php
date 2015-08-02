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

class Api extends \Arnapou\GW2Tools\AbstractModule {

	protected $menu = [
		'account'	 => 'Account',
		'characters' => 'Characters',
		'stuff'		 => 'Stuff stats',
		'attributes' => 'Attributes',
	];

	public function configure() {
		parent::configure();

		$regexpToken = '[A-F0-9-]{70,80}';
		$regexpMenu = implode('|', array_keys($this->menu));

		$this->addRoute('', [$this, 'routeHome']);
		$this->addRoute('{token}/', [$this, 'routeHomeToken'])->assert('token', $regexpToken);
		$this->addRoute('{token}/{menu}/', [$this, 'routeMenu'])->assert('token', $regexpToken)->assert('menu', $regexpMenu);
		$this->addRoute('{token}/{menu}/content.html', [$this, 'routeMenuContent'])->assert('token', $regexpToken)->assert('menu', $regexpMenu);
		$this->addRoute('{token}/character/{name}', [$this, 'routeCharacter'])->assert('token', $regexpToken);
		$this->addRoute('{token}/character/{name}.html', [$this, 'routeCharacterContent'])->assert('token', $regexpToken);
	}

	public function getMenu() {
		return $this->menu;
	}

	public function routeHome() {
		return $this->renderPage('home.twig');
	}

	public function routeHomeToken($token) {
		$this->newApiClient($token);
		return $this->getService()->returnResponseRedirect('./account/');
	}

	public function routeCharacter($token, $name) {
		$apiClient = $this->newApiClient($token);
		$characters = $apiClient->getCharacters();
		$name = rawurldecode($name);
		if (isset($characters[$name])) {
			$context = [
				'apiclient'	 => $apiClient,
				'account'	 => $apiClient->v2_account(),
				'char'		 => $characters[$name],
			];
			return $this->renderPage('menu-character.twig', $context);
		}
	}

	public function routeCharacterContent($token, $name) {
		$apiClient = $this->newApiClient($token);
		$characters = $apiClient->getCharacters();
		$name = rawurldecode($name);
		if (isset($characters[$name])) {
			$context = [
				'apiclient'	 => $apiClient,
				'account'	 => $apiClient->v2_account(),
				'char'		 => $characters[$name],
			];
			return $this->renderPage('content-character.twig', $context);
		}
	}

	public function routeMenu($token, $menu) {
		$apiClient = $this->newApiClient($token);
		$context = [
			'apiclient'	 => $apiClient,
			'account'	 => $apiClient->v2_account(),
			'menu'		 => $menu,
		];
		return $this->renderPage('menu-' . $menu . '.twig', $context);
	}

	public function routeMenuContent($token, $menu) {
		$apiClient = $this->newApiClient($token);
		$context = [
			'apiclient'	 => $apiClient,
			'account'	 => $apiClient->v2_account(),
			'menu'		 => $menu,
		];
		return $this->renderPage('content-' . $menu . '.twig', $context);
	}

}
