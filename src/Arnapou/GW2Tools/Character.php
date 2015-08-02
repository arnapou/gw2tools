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

class Character {

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

	/**
	 *
	 * @var array
	 */
	protected $data;

	/**
	 *
	 * @var array
	 */
	protected $computed;

	/**
	 *
	 * @var ApiClient
	 */
	protected $apiClient;

	public function __construct(ApiClient $apiClient, $data) {
		$this->apiClient = $apiClient;
		$this->data = $data;
	}

	public function getData() {
		return $this->data;
	}

	/**
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->data['name'];
	}

	/**
	 * 
	 * @return array
	 */
	public function getGuild() {
		if (isset($this->data['guild'])) {
			return [
				'id'	 => $this->data['guild'],
				'name'	 => $this->apiClient->getGuildName($this->data['guild']),
			];
		}
		return null;
	}

	/**
	 * 
	 * @return string
	 */
	public function getCreated() {
		if (isset($this->data['created'])) {
			return gmdate('Y-m-d H:i', strtotime($this->data['created']));
		}
		return null;
	}

	/**
	 * 
	 * @return int
	 */
	public function getDays() {
		if (isset($this->data['created'])) {
			return floor((time() - strtotime($this->data['created'])) / 86400);
		}
		return null;
	}

	/**
	 * 
	 * @return int
	 */
	public function getDeaths() {
		if (isset($this->data['deaths'])) {
			return $this->data['deaths'];
		}
		return null;
	}

	/**
	 * 
	 * @return int
	 */
	public function getRace() {
		if (isset($this->data['race'])) {
			return $this->data['race'];
		}
		return null;
	}

	/**
	 * 
	 * @return int
	 */
	public function getProfession() {
		if (isset($this->data['profession'])) {
			return $this->data['profession'];
		}
		return null;
	}

	/**
	 * 
	 * @return int
	 */
	public function getGender() {
		if (isset($this->data['gender'])) {
			return $this->data['gender'];
		}
		return null;
	}

	/**
	 * 
	 * @return int
	 */
	public function getLevel() {
		if (isset($this->data['level'])) {
			return $this->data['level'];
		}
		return null;
	}

	/**
	 * 
	 * @return int
	 */
	public function getAge() {
		if (isset($this->data['age'])) {
			return floor($this->data['age'] / 3600);
		}
		return null;
	}

	/**
	 * 
	 * @return array
	 */
	public function getCrafting() {
		if (isset($this->computed['crafting'])) {
			return $this->computed['crafting'];
		}
		if (isset($this->data['crafting'])) {
			$items = [];
			$map = [
				'Armorsmith'	 => 'map_crafting_armorsmith',
				'Artificer'		 => 'map_crafting_artificer',
				'Chef'			 => 'map_crafting_cook',
				'Huntsman'		 => 'map_crafting_huntsman',
				'Jewler'		 => 'map_crafting_jeweler',
				'Leatherworker'	 => 'map_crafting_leatherworker',
				'Tailor'		 => 'map_crafting_tailor',
				'Weaponsmith'	 => 'map_crafting_weaponsmith',
			];
			try {
				foreach ($this->data['crafting'] as $craft) {
					if (isset($craft['rating'], $craft['active'], $craft['discipline']) &&
						$craft['rating'] &&
						$craft['active'] &&
						isset($map[$craft['discipline']])
					) {
						$items[] = [
							'discipline' => $craft['discipline'],
							'level'		 => $craft['rating'],
							'icon'		 => $this->apiClient->getFileIcon($map[$craft['discipline']]),
						];
					}
				}
			}
			catch (\Exception $e) {
				
			}
			$this->computed['crafting'] = $items;
			return $items;
		}
		return null;
	}

	public function getAttributes() {
		if (isset($this->computed['attributes'])) {
			return $this->computed['attributes'];
		}
		$attributes = [
			'Power'				 => 1000,
			'Precision'			 => 1000,
			'Toughness'			 => 1000,
			'Vitality'			 => 1000,
			'CritDamage'		 => 0,
			'ConditionDamage'	 => 0,
			'Healing'			 => 0,
			'RA'				 => [
				'WeaponA'		 => 0,
				'WeaponB'		 => 0,
				'WeaponAquatic'	 => 0,
			],
		];
		$equipments = $this->getEquipments();
		if ($equipments) {
			foreach ($equipments as $type => $equipment) {
				if (isset($equipment['attributes'])) {
					foreach ($equipment['attributes'] as $attribute => $value) {
						if (isset($attributes[$attribute])) {
							$attributes[$attribute] += $value;
						}
					}
				}
				if (isset($equipment['infusions'])) {
					foreach ($equipment['infusions'] as $infusion) {
						if (isset($infusion['buff'])) {
							if (preg_match('!\+([0-9]+).*agony!i', $infusion['buff'], $m)) {
								$found = false;
								foreach ($attributes['RA'] as $key => $value) {
									if (strpos($type, $key) !== false) {
										$attributes['RA'][$key] += $m[1];
										$found = true;
									}
								}
								if (!$found) {
									foreach ($attributes['RA'] as $key => $value) {
										$attributes['RA'][$key] += $m[1];
									}
								}
							}
						}
					}
				}
			}
		}
		if ($attributes['RA']['WeaponA'] == $attributes['RA']['WeaponB'] &&
			$attributes['RA']['WeaponA'] == $attributes['RA']['WeaponAquatic']
		) {
			$attributes['RA'] = ['AllWeapons' => $attributes['RA']['WeaponA']];
		}

		$attributes['Precision_pct'] = round(($attributes['Precision'] - 916) / 21);
		$attributes['Ferocity_pct'] = round(150 + $attributes['CritDamage'] / 15);
		$attributes['Ferocity'] = $attributes['CritDamage'];
		unset($attributes['CritDamage']);
		$attributes['Condition'] = $attributes['ConditionDamage'];
		unset($attributes['ConditionDamage']);

		$this->computed['attributes'] = $attributes;
		return $attributes;
	}

	protected function getItem($id) {
		if (!isset($this->computed['item.' . $id])) {
			$this->computed['item.' . $id] = $this->formatItem($this->apiClient->getItem($id));
		}
		return $this->computed['item.' . $id];
	}

	protected function formatItem($item) {
		if (empty($item)) {
			return null;
		}
		$return = [
			'id' => $item['id'],
		];
		foreach (['level', 'rarity', 'name', 'icon'] as $key) {
			if (isset($item[$key])) {
				$return[$key] = $item[$key];
			}
		}
		if (isset($item['details'])) {
			if (isset($item['details']['type'])) {
				$return['type'] = $item['details']['type'];
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
						if(isset($return['stats'])){
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

	public function getEquipments() {
		if (isset($this->computed['equipment'])) {
			return $this->computed['equipment'];
		}
		if (isset($this->data['equipment'])) {
			try {
				$objects = [];
				$skins = [];
				foreach ($this->data['equipment'] as $equipment) {
					$objects[] = $equipment['id'];
					if (isset($equipment['skin'])) {
						$skins[] = $equipment['skin'];
					}
				}
				$objects = $this->apiClient->getItems($objects);
				$skins = $this->apiClient->getSkins($skins);

				$equipments = [];
				foreach ($this->data['equipment'] as $equipment) {
					if (isset($objects[$equipment['id']])) {
						$obj = $this->formatItem($objects[$equipment['id']]);
						$ids[] = $equipment['id'];
						if (isset($equipment['upgrades'])) {
							$upgrades = [];
							foreach ($equipment['upgrades'] as $upgrade) {
								$upgrades[] = $this->getItem($upgrade);
							}
							$obj['upgrades'] = $upgrades;
						}
						if (isset($equipment['infusions'])) {
							$infusions = [];
							foreach ($equipment['infusions'] as $infusion) {
								$infusions[] = $this->getItem($infusion);
							}
							$obj['infusions'] = $infusions;
						}
						if (isset($equipment['skin']) && isset($skins[$equipment['skin']])) {
							$skin = $this->formatItem($skins[$equipment['skin']]);
							if (isset($skin['icon'])) {
								$obj['icon'] = $skin['icon'];
							}
							if (isset($skin['name'])) {
								$obj['name'] = $skin['name'];
							}
						}
						$equipments[$equipment['slot']] = $obj;
					}
				}
				$this->computed['equipment'] = $equipments;
				return $equipments;
			}
			catch (\Exception $e) {
				
			}
		}
		return null;
	}

	public function format_character($char) {

		$char['stats'] = [
			'Power'				 => 1000,
			'Precision'			 => 1000,
			'Toughness'			 => 1000,
			'Vitality'			 => 1000,
			'CritDamage'		 => 0,
			'ConditionDamage'	 => 0,
			'Healing'			 => 0,
			'RA'				 => 0,
		];
		$ids = [];
		$skins = [];
		$equipments = [];
		foreach ($char['equipment'] as &$equipment) {
			if (!empty($equipment['id']) && isset($equipment['slot'])) {
				if (isset($equipment['skin'])) {
					$skins[] = $equipment['skin'];
				}
				$ids[] = $equipment['id'];
				if (isset($equipment['upgrades'])) {
					$equipment['upgrades'] = $this->get_items($equipment['upgrades'], true);
				}
				if (isset($equipment['infusions'])) {
					$equipment['infusions'] = $this->get_items($equipment['infusions'], true);
					foreach ($equipment['infusions'] as $infusion) {
						if (isset($infusion['details'], $infusion['details']['infix_upgrade'], $infusion['details']['infix_upgrade']['buff'])) {
							if (preg_match('!\+([0-9]+).*agony!i', $infusion['details']['infix_upgrade']['buff']['description'], $m)) {
								$char['stats']['RA'] += $m[1];
							}
						}
					}
				}
				$equipments[$equipment['slot']] = $equipment;
			}
		}
		if (!empty($ids)) {
			sort($ids);
			$items = [];
			foreach ($this->get_items($ids) as $item) {
				$items[$item['id']] = $item;
			}
			foreach ($equipments as &$equipment) {
				if (isset($items[$equipment['id']])) {
					$equipment['item'] = $items[$equipment['id']];
				}
			}
		}
		if (!empty($skins)) {
			sort($skins);
			$items = [];
			foreach ($this->get_skins($skins) as $item) {
				$items[$item['id']] = $item;
			}
			foreach ($equipments as &$equipment) {
				if (isset($equipment['skin'])) {
					$equipment['skin'] = $items[$equipment['skin']];
				}
			}
		}
		foreach ($equipments as &$equipment) {
			if (isset($equipment['item'], $equipment['item']['details'], $equipment['item']['details']['infix_upgrade'], $equipment['item']['details']['infix_upgrade']['attributes'])) {
				$attributes = $equipment['item']['details']['infix_upgrade']['attributes'];
				foreach ($attributes as $attribute) {
					if (isset($char['stats'][$attribute['attribute']])) {
						$char['stats'][$attribute['attribute']] += $attribute['modifier'];
					}
				}
				if (count($attributes) >= 7) {
					$equipment['stats_name'] = 'Celestial';
				}
				else {
					usort($attributes, function($a, $b) {
						if ($a['modifier'] == $b['modifier']) {
							return 0;
						}
						return $a['modifier'] < $b['modifier'] ? 1 : -1;
					});
					$stats = implode('/', array_map(function($a) {
							return $a['attribute'];
						}, $attributes));
					if (isset($this->stats[$stats])) {
						$equipment['stats_name'] = $this->stats[$stats];
					}
					$equipment['stats'] = $stats;
				}
			}
		}
		$char['equipment'] = $equipments;
		$char['stats']['Precision_pct'] = round(($char['stats']['Precision'] - 916) / 21);
		$char['stats']['CritDamage_pct'] = round(150 + $char['stats']['CritDamage'] / 15);

		return $char;
	}

}
