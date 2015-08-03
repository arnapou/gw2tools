<?php

/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\GW2Tools;


abstract class AbstractModule extends \Arnapou\Toolbox\Http\Service\AbstractModule {

	protected function renderPage($template, $context = []) {
		$context['config'] = $this->getService()->getConfig();
		$context['module'] = $this;
		$context['service'] = $this->getService();
		return $this->getService()->getTwig()->render($template, $context);
	}

	/**
	 * 
	 * @return ApiClient
	 */
	protected function newApiClient($token) {
		$client = ApiClient::EN($this->getService()->getPathCache() . '/gw2api');
		$client->setAccessToken($token);
		try {
			$account = $client->v2_account();
			if (!isset($account['id'])) {
				die('INVALID TOKEN');
			}
		}
		catch (\Exception $ex) {
			die('INVALID TOKEN');
		}
		return $client;
	}

}
