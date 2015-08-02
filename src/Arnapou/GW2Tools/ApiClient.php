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

	const RETENTION = 864000; // 10 days

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

	public function getItems($ids) {
		try {
			if (!empty($ids)) {
				$objects = $this->clientV2->apiItems($ids)->execute(self::RETENTION)->getData();
				$return = [];
				foreach ($objects as $object) {
					$return[$object['id']] = $object;
				}
				return $return;
			}
		}
		catch (\Exception $ex) {
			
		}
		return [];
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
		try {
			if (!empty($ids)) {
				$objects = $this->clientV2->apiSkins($ids)->execute(self::RETENTION)->getData();
				$return = [];
				foreach ($objects as $object) {
					$return[$object['id']] = $object;
				}
				return $return;
			}
		}
		catch (\Exception $ex) {
			
		}
		return [];
	}

}
