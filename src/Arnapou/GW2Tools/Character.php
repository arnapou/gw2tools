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
			return $this->apiClient->getGuild($this->data['guild']);
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
			return [
				'name'	 => $this->data['profession'],
				'icon'	 => '/api/profession-' . strtolower($this->data['profession']) . '.png',
			];
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
			try {
				foreach ($this->data['crafting'] as $craft) {
					if (isset($craft['rating'], $craft['active'], $craft['discipline']) &&
						$craft['rating'] &&
						$craft['active']
					) {
						$items[] = [
							'discipline' => $craft['discipline'],
							'level'		 => $craft['rating'],
							'icon'		 => '/api/crafting-' . strtolower($craft['discipline']) . '.png',
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
			'AR'				 => [
				'WeaponA'		 => 0,
				'WeaponB'		 => 0,
				'WeaponAquatic'	 => 0,
			],
		];
		$unknown = [];
		$equipments = $this->getEquipments();
		if ($equipments) {
			foreach ($equipments as $type => $equipment) {
				if (in_array($type, [
						'Helm', 'Shoulders', 'Coat', 'Gloves', 'Leggings', 'Boots',
						'WeaponA1', 'WeaponA2',
						'Backpack', 'Amulet', 'Ring1', 'Ring2', 'Accessory1', 'Accessory2',
					])) {
					if (isset($equipment['attributes'])) {
						foreach ($equipment['attributes'] as $attribute => $value) {
							if (isset($attributes[$attribute])) {
								$attributes[$attribute] += $value;
							}
						}
					}
					else {
						$unknown[] = $type;
					}
				}
				if (isset($equipment['infusions'])) {
					foreach ($equipment['infusions'] as $infusion) {
						if (isset($infusion['buff'])) {
							if (preg_match('!\+([0-9]+).*agony!i', $infusion['buff'], $m)) {
								$found = false;
								foreach ($attributes['AR'] as $key => $value) {
									if (strpos($type, $key) !== false) {
										$attributes['AR'][$key] += $m[1];
										$found = true;
									}
								}
								if (!$found) {
									foreach ($attributes['AR'] as $key => $value) {
										$attributes['AR'][$key] += $m[1];
									}
								}
							}
						}
					}
				}
			}
		}
		if ($attributes['AR']['WeaponA'] == $attributes['AR']['WeaponB'] &&
			$attributes['AR']['WeaponA'] == $attributes['AR']['WeaponAquatic']
		) {
			$attributes['AR'] = ['AllWeapons' => $attributes['AR']['WeaponA']];
		}

		$attributes['Precision_pct'] = round(($attributes['Precision'] - 916) / 21);
		$attributes['Ferocity_pct'] = round(150 + $attributes['CritDamage'] / 15);

		$attributes['Ferocity'] = $attributes['CritDamage'];
		unset($attributes['CritDamage']);

		$attributes['Condition'] = $attributes['ConditionDamage'];
		unset($attributes['ConditionDamage']);

		$this->computed['attributes'] = $attributes;
		return [
			'unknown'	 => $unknown,
			'list'		 => $attributes,
		];
	}

	public function getInventory() {
		if (isset($this->computed['inventory'])) {
			return $this->computed['inventory'];
		}
		if (isset($this->data['bags'])) {
			try {
				$slots = [];
				foreach ($this->data['bags'] as $bag) {
					if (isset($bag['inventory'])) {
						foreach ($bag['inventory'] as $object) {
							if (empty($object) || isset($object['count']) && $object['count'] != 1 || !isset($object['id'])) {
								continue;
							}
							$slots[] = $object;
						}
					}
				}

				$slots = $this->apiClient->formatSlots($slots);

				$inventory = [];
				foreach ($slots as $slot) {
					if (empty($slot)) {
						continue;
					}
					if (!isset($slot['type']) || !isset($slot['rarity']) || !in_array($slot['rarity'], ['Exotic', 'Ascended', 'Legendary'])) {
						continue;
					}
					$inventory[$slot['type']][] = $slot;
				}
				ksort($inventory);
				$this->computed['inventory'] = $inventory;
				return $inventory;
			}
			catch (\Exception $e) {
				
			}
		}
		return null;
	}

	public function getEquipments() {
		if (isset($this->computed['equipment'])) {
			return $this->computed['equipment'];
		}
		if (isset($this->data['equipment'])) {
			try {
				$slots = $this->apiClient->formatSlots($this->data['equipment']);

				$equipments = [];
				foreach ($slots as $slot) {
					if (!isset($slot['slot']) || in_array($slot['slot'], ['Sickle', 'Axe', 'Pick'])) {
						continue;
					}
					$equipments[$slot['slot']] = $slot;
				}
				$this->computed['equipment'] = $equipments;
				return $equipments;
			}
			catch (\Exception $e) {
//
//				echo '<pre>';
//				echo $e->getMessage() . "\n";
//				echo $e->getTraceAsString() . "\n";
//
//				echo '</pre>';
//				exit;
			}
		}
		return null;
	}

}
