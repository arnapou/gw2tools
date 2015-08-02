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

class Api extends \Arnapou\GW2Tools\AbstractModule {

	protected $menu = [
		'account'	 => 'Account',
		'characters' => 'Characters',
	];

	public function configure() {
		parent::configure();

		$regexpToken = '[A-F0-9-]{70,80}';
		$regexpMenu = implode('|', array_keys($this->menu));

		$this->addRoute('', [$this, 'routeHome']);
		$this->addRoute('{token}/', [$this, 'routeHomeToken'])->assert('token', $regexpToken);
		$this->addRoute('{token}/{menu}/', [$this, 'routeMenu'])->assert('token', $regexpToken)->assert('menu', $regexpMenu);
		$this->addRoute('{token}/characters/{name}', [$this, 'routeCharacter'])->assert('token', $regexpToken);
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
		$characters = $apiClient->v2_characters();
		$name = rawurldecode($name);
		if (in_array($name, $characters)) {
			$context = [
				'apiclient'	 => $apiClient,
				'char'		 => $apiClient->get_formatted_character($name),
				'account'	 => $apiClient->v2_account(),
				'menu'		 => 'characters',
			];
			return $this->renderPage('menu-characters-detail.twig', $context);
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

}
