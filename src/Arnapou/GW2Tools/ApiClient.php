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

class ApiClient extends \Arnapou\GW2Api\SimpleClient {

	protected $stats = [
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

	public function craft_icon($discipline) {
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
		if (isset($map[$discipline])) {
			$file = $this->clientV2->apiFiles($map[$discipline])->execute(7 * 86400)->getData();
			if (is_array($file) && isset($file[0]) && isset($file[0]['icon'])) {
				return $file[0]['icon'];
			}
		}
		return null;
	}

	public function guild_name($id) {
		$data = $this->clientV1->apiGuildDetails($id)->execute(7 * 86400)->getData();
		if (isset($data['guild_name']) && isset($data['tag'])) {
			return $data['guild_name'] . ' [' . $data['tag'] . ']';
		}
		return null;
	}

	public function get_items($ids) {
		try {
			return $this->clientV2->apiItems($ids)->execute(7 * 86400)->getData();
		}
		catch (\Exception $ex) {
			return [];
		}
	}

	public function get_skins($ids) {
		try {
			return $this->clientV2->apiSkins($ids)->execute(7 * 86400)->getData();
		}
		catch (\Exception $ex) {
			return [];
		}
	}

	public function get_formatted_character($name) {
		$char = $this->get_character($name);


		$char['stats'] = [
			'Power'				 => 1000,
			'Precision'			 => 1000,
			'Toughness'			 => 1000,
			'Vitality'			 => 1000,
			'CritDamage'		 => 0,
			'ConditionDamage'	 => 0,
			'Healing'			 => 0,
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
					$equipment['upgrades'] = $this->get_items($equipment['upgrades']);
				}
				if (isset($equipment['infusions'])) {
					$equipment['infusions'] = $this->get_items($equipment['infusions']);
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

		return $char;
	}

	public function get_character($name) {
		$characters = $this->v2_characters($this->v2_characters());
		foreach ($characters as $character) {
			if ($character['name'] == $name) {
				return $character;
			}
		}
		return null;
	}

}
