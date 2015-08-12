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
	const IMG_NOTHING = '/assets/images/nothing.png';

	/**
	 *
	 * @var array
	 */
	protected static $STATS = [
		'Power/Precision/CritDamage'			 => "Berserker's",
		'Power/CritDamage/Precision'			 => "Berserker's",
		'Power/Healing/Precision'				 => "Zealot's",
		'Power/Precision/Healing'				 => "Zealot's",
		'Power/Toughness/Vitality'				 => "Soldier's",
		'Power/Vitality/Toughness'				 => "Soldier's",
		'Power/CritDamage/Vitality'				 => "Valkyrie",
		'Power/Vitality/CritDamage'				 => "Valkyrie",
		'Precision/Toughness/Power'				 => "Captain's",
		'Precision/Power/Toughness'				 => "Captain's",
		'Precision/ConditionDamage/Power'		 => "Rampager's",
		'Precision/Power/ConditionDamage'		 => "Rampager's",
		'Precision/CritDamage/Power'			 => "Assassin's",
		'Precision/Power/CritDamage'			 => "Assassin's",
		'Toughness/Precision/Power'				 => "Knight's",
		'Toughness/Power/Precision'				 => "Knight's",
		'Toughness/Power/CritDamage'			 => "Cavalier's",
		'Toughness/CritDamage/Power'			 => "Cavalier's",
		'Toughness/Healing/Vitality'			 => "Nomad's",
		'Toughness/Vitality/Healing'			 => "Nomad's",
		'Toughness/Healing/ConditionDamage'		 => "Settler's",
		'Toughness/ConditionDamage/Healing'		 => "Settler's",
		'Vitality/Toughness/Power'				 => "Sentinel's",
		'Vitality/Toughness/Power'				 => "Sentinel's",
		'Vitality/Healing/ConditionDamage'		 => "Shaman's",
		'Vitality/ConditionDamage/Healing'		 => "Shaman's",
		'ConditionDamage/Precision/Power'		 => "Sinister",
		'ConditionDamage/Power/Precision'		 => "Sinister",
		'ConditionDamage/Vitality/Power'		 => "Carrion",
		'ConditionDamage/Power/Vitality'		 => "Carrion",
		'ConditionDamage/Toughness/Precision'	 => "Rabid",
		'ConditionDamage/Precision/Toughness'	 => "Rabid",
		'ConditionDamage/Vitality/Toughness'	 => "Dire",
		'ConditionDamage/Toughness/Vitality'	 => "Dire",
		'Healing/Toughness/Power'				 => "Cleric's",
		'Healing/Power/Toughness'				 => "Cleric's",
		'Healing/Vitality/Precision'			 => "Magi",
		'Healing/Precision/Vitality'			 => "Magi",
		'Healing/ConditionDamage/Toughness'		 => "Apothecary's",
		'Healing/Toughness/ConditionDamage'		 => "Apothecary's",
	];

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

	public function getPrices($ids) {
		return $this->smartCaching('apiCommercePrices', __METHOD__, $ids, 1800);
	}

	public function getCharacterNames() {
		$names = $this->v2_characters();
		sort($names);
		return $names;
	}

	public function getCharacters() {
		$items = $this->v2_characters($this->v2_characters());
		usort($items, function($a, $b) {
			return $a['age'] == $b['age'] ? 0 : ($a['age'] < $b['age'] ? 1 : -1);
		});
		$characters = [];
		foreach ($items as $item) {
			$characters[$item['name']] = new Character($this, $item);
		}
		return $characters;
	}

	public function getCollectibles() {
		try {
			$categories = $this->v2_materials($this->v2_materials());
			$ids = [];
			foreach ($categories as $category) {
				foreach ($category['items'] as $id) {
					$ids[] = $id;
				}
			}
			$objects = $this->getItems($ids);
			$prices = $this->getPrices($ids);

			$materials = [];
			foreach ($categories as $category) {
				$items = [];
				foreach ($category['items'] as $id) {
					$items[$id] = isset($objects[$id]) ? $objects[$id] : null;
					if (isset($prices[$id])) {
						$items[$id]['price'] = [
							'min_unit'	 => $prices[$id]['buys']['unit_price'],
							'max_unit'	 => $prices[$id]['sells']['unit_price'],
						];
					}
				}
				$materials[$category['id']] = [
					'id'	 => $category['id'],
					'name'	 => $category['name'],
					'items'	 => $items,
				];
			}

			foreach ($this->v2_account_materials() as $item) {
				if (isset($item['category'], $item['count'], $item['id'])) {
					if (isset($materials[$item['category']])) {
						if (array_key_exists($item['id'], $materials[$item['category']]['items'])) {
							$materials[$item['category']]['items'][$item['id']]['count'] = $item['count'];
						}
					}
				}
			}

			foreach ($materials as &$category) {
				$sum_min = 0;
				$sum_max = 0;
				foreach ($category['items'] as &$item) {
					if (isset($item['count'], $item['price']) && $item['count'] > 0) {
						$sum_min += $item['count'] * $item['price']['min_unit'];
						$sum_max += $item['count'] * $item['price']['max_unit'];
						$item['price']['min'] = $item['count'] * $item['price']['min_unit'];
						$item['price']['max'] = $item['count'] * $item['price']['max_unit'];
						$item['price']['title'] = $this->getAmount($item['price']['min']) . ' - ' . $this->getAmount($item['price']['max'])
							. ' / ' . $this->getAmount($item['price']['min_unit']) . ' - ' . $this->getAmount($item['price']['max_unit']);
					}
				}
				$category['sum_min'] = $sum_min;
				$category['sum_max'] = $sum_max;
			}

			return $materials;
		}
		catch (\Exception $ex) {
			
		}
		return null;
	}

	public function getAmount($value) {
		$g = floor($value / 10000);
		$s = floor($value / 100) % 100;
		$c = $value % 100;
		if ($g) {
			return $g . 'g ' . $s . 's ' . $c . 'c';
		}
		elseif ($s) {
			return $s . 's ' . $c . 'c';
		}
		else {
			return $c . 'c';
		}
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

	public function getGuild($id) {
		try {
			$data = $this->clientV1->apiGuildDetails($id)->execute(self::RETENTION)->getData();
			if (isset($data['guild_name']) && isset($data['tag'])) {
				return [
					'id'		 => $id,
					'name'		 => $data['guild_name'],
					'tag'		 => $data['tag'],
					'icon'		 => isset($data['emblem']) ? '/api/guild-emblem-' . $id . '.png' : self::IMG_NOTHING,
					'fullname'	 => $data['guild_name'] . ' [' . $data['tag'] . ']',
				];
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

	protected function smartCaching($method, $cachePrefix, $ids, $retention = null) {
		if (empty($ids)) {
			return [];
		}
		if ($retention === null) {
			$retention = self::RETENTION;
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
					$chunks = array_chunk($idsToRequest, 100);
					$objects = [];
					foreach ($chunks as $chunk) {
						$objects = $this->clientV2->$method($chunk)->execute($retention)->getData();
						foreach ($objects as $object) {
							if (isset($object['id'])) {
								$cache->set($cachePrefix . ':' . $object['id'], $object, $retention);
								$return[$object['id']] = $object;
							}
						}
					}
				}
				return $return;
			}
			else {
				$return = [];
				$objects = $this->clientV2->$method($ids)->execute($retention)->getAllData();
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

	public function formatItem($item) {
		if (empty($item)) {
			return null;
		}
		$return = [
			'id' => $item['id'],
		];
		foreach (['level', 'rarity', 'name'] as $key) {
			if (isset($item[$key])) {
				$return[$key] = $item[$key];
			}
		}
		if (!empty($item['icon'])) {
			$return['icon'] = preg_replace('!^.*file/(.*?)\.png$!i', '/api/render-file/$1.png', $item['icon']);
		}
		else {
			$return['icon'] = self::IMG_NOTHING;
		}
		if (isset($item['details'])) {
			foreach (['type', 'defense', 'weight_class'] as $key) {
				if (isset($item['details'][$key])) {
					$return[$key] = $item['details'][$key];
				}
			}
			if (isset($item['details']['infix_upgrade'])) {
				if (isset($item['details']['infix_upgrade']['buff'])) {
					if (isset($item['details']['infix_upgrade']['buff']['description'])) {
						$return['buff'] = $item['details']['infix_upgrade']['buff']['description'];
					}
				}
				if (isset($item['details']['infix_upgrade']['attributes'])) {
					$attributes = $item['details']['infix_upgrade']['attributes'];
					if (!empty($attributes)) {
						usort($attributes, function($a, $b) {
							if ($a['modifier'] == $b['modifier']) {
								return 0;
							}
							return $a['modifier'] < $b['modifier'] ? 1 : -1;
						});
						$tmp = [];
						foreach ($attributes as $attribute) {
							$tmp[$attribute['attribute']] = $attribute['modifier'];
						}
						$attributes = $tmp;
						if (count($attributes) >= 7) {
							$return['stats_name'] = 'Celestial';
						}
						else {
							$return['stats'] = implode('/', array_keys($attributes));
							if (isset(self::$STATS[$return['stats']])) {
								$return['stats_name'] = self::$STATS[$return['stats']];
							}
						}
						if (isset($return['stats'])) {
							$return['stats'] = str_replace('CritDamage', 'Ferocity', $return['stats']);
							$return['stats'] = str_replace('ConditionDamage', 'Condition', $return['stats']);
						}
						$return['attributes'] = $attributes;
					}
				}
			}
		}
		return $return;
	}

}
