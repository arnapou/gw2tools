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

use Arnapou\GW2Tools\Exception\TokenException;

class ApiClient extends \Arnapou\GW2Api\SimpleClient {

	const RETENTION = 864000; // 10 days

	public function setAccessToken($token) {
		parent::setAccessToken($token);

		try {
			$tokeninfo = $this->v2_tokeninfo();
			if (!isset($tokeninfo['id']) || !isset($tokeninfo['permissions'])) {
				throw new TokenException('Invalid token');
			}
			foreach (['account', 'characters', 'inventories'] as $permission) {
				if (!in_array($permission, $tokeninfo['permissions'])) {
					throw new TokenException('The token is missing permission "' . $permission . '"');
				}
			}
		}
		catch (\Exception $e) {
			throw new TokenException($e->getMessage());
		}
	}

	public function getCharacterNames() {
		return $this->v2_characters();
	}

	public function getCharacters() {
		$items = $this->v2_characters($this->v2_characters());
		usort($items, function($a, $b) {
			return strcmp($a['created'], $b['created']);
		});
		$characters = [];
		foreach ($items as $item) {
			$characters[$item['name']] = new Character($this, $item);
		}
		return $characters;
	}

	public function getFileIcon($id) {
		try {
			$file = $this->clientV2->apiFiles($id)->execute(self::RETENTION)->getData();
			if (is_array($file) && isset($file[0]) && isset($file[0]['icon'])) {
				return $file[0]['icon'];
			}
		}
		catch (\Exception $ex) {
			
		}
		return null;
	}

	public function getWorldName($id) {
		try {
			$data = $this->clientV2->apiWorlds($id)->execute(self::RETENTION)->getData();
			if (isset($data[0], $data[0]['name'])) {
				return $data[0]['name'];
			}
		}
		catch (\Exception $ex) {
			
		}
		return null;
	}

	public function getGuildName($id) {
		try {
			$data = $this->clientV1->apiGuildDetails($id)->execute(self::RETENTION)->getData();
			if (isset($data['guild_name']) && isset($data['tag'])) {
				return $data['guild_name'] . ' [' . $data['tag'] . ']';
			}
		}
		catch (\Exception $ex) {
			
		}
		return null;
	}

	public function getItem($id) {
		try {
			return $this->clientV2->apiItems($id)->execute(self::RETENTION)->getData()[0];
		}
		catch (\Exception $ex) {
			
		}
		return null;
	}

	protected function smartCaching($method, $cachePrefix, $ids) {
		if (empty($ids)) {
			return [];
		}
		try {
			if (is_array($ids)) {
				$cache = $this->clientV2->getRequestManager()->getCache();
				$objectsFromCache = [];
				$idsToRequest = [];
				foreach ($ids as $id) {
					$result = $cache->get($cachePrefix . ':' . $id);
					if (is_array($result)) {
						$objectsFromCache[$id] = $result;
					}
					else {
						$idsToRequest[] = $id;
					}
				}
				$return = $objectsFromCache;
				if (!empty($idsToRequest)) {
					$objects = $this->clientV2->$method($idsToRequest)->execute(self::RETENTION)->getData();
					foreach ($objects as $object) {
						if (isset($object['id'])) {
							$cache->set($cachePrefix . ':' . $object['id'], $object, self::RETENTION);
							$return[$object['id']] = $object;
						}
					}
				}
				return $return;
			}
			else {
				$return = [];
				$objects = $this->clientV2->$method($ids)->execute(self::RETENTION)->getData();
				foreach ($objects as $object) {
					$return[$object['id']] = $object;
				}
				return $return;
			}
		}
		catch (\Exception $e) {
			
		}
		return [];
	}

	public function getItems($ids) {
		return $this->smartCaching('apiItems', __METHOD__, $ids);
	}

	public function getSkin($id) {
		try {
			return $this->clientV2->apiSkins($id)->execute(self::RETENTION)->getData()[0];
		}
		catch (\Exception $ex) {
			
		}
		return [];
	}

	public function getSkins($ids) {
		return $this->smartCaching('apiSkins', __METHOD__, $ids);
	}

}
