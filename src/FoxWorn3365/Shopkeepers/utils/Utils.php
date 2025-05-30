<?php

/*
 * Shopkeepers for PocketMine-MP
 * Add custom shopkeepers to your PocketMine-MP server!
 * 
 * Copyright (C) 2023-now FoxWorn3365
 * Relased under GNU General Public License v3.0 (https://github.com/FoxWorn3365/Shopkeepers/blob/main/LICENSE)
 * You can find the license file in the root folder of the project inside the LICENSE file!
 * If not, see https://www.gnu.org/licenses/
 * 
 * Useful links:
 * - GitHub: https://github.com/FoxWorn3365/Shopkeepers
 * - Contribution guidelines: https://github.com/FoxWorn3365/Shopkeepers#contributing
 * - Author GitHub: https://github.com/FoxWorn3365
 * 
 * Current file: /utils/Draw.php
 * STATIC CLASS
 * Description: The simplest file: id:meta to item parser
 */

namespace FoxWorn3365\Shopkeepers\utils;

use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

use FoxWorn3365\Shopkeepers\Core;

final class Utils {
    public static function getItem(string $itemid) : mixed {
		try {
			return LegacyStringToItemParser::getInstance()->parse(trim($itemid));
		} catch (LegacyStringToItemParserException) {
			return VanillaItems::FLINT_AND_STEEL();
		}
    }

    public static function getIntItem(int $id, int $meta = 0) : mixed {
        $itemid = "{$id}:{$meta}";
        try {
			return LegacyStringToItemParser::getInstance()->parse(trim($itemid));
		} catch (LegacyStringToItemParserException) {
			return VanillaItems::FLINT_AND_STEEL();
		}
    }

	public static function errorLogger(string $data_dir, string $severity, string $reason) : void {
		if (file_exists("{$data_dir}error.txt")) {
			$stream = file_get_contents("{$data_dir}error.txt");
		} else {
			$stream = "";
		}
		$stream .= "\n" . date("[d/m/Y - H:i:s]") . "[#{$severity}]: {$reason}";
		file_put_contents("{$data_dir}error.txt", $stream);
	}

	public static function integrityChecker(string $data_dir) : void {
		foreach (glob("{$data_dir}*.json") as $file) {
			$content = file_get_contents($file);
			if (empty($content) || $content == " ") {
				self::errorLogger($data_dir, "ERROR", "Empty or invalid file in {$file}!");
				// Remove the dangerous file
				@unlink($file);
			} elseif (json_decode($content) === false || json_decode($content) === null) {
				self::errorLogger($data_dir, "ERROR", "Invalid JSON in file {$file}!");
				// Remove the dangerous file
				@unlink($file);
			} else {
				// Correct the file
				self::shopTypeChecker($data_dir, json_decode($content), $file);
			}
		}

		// Now remove empty values from the .entities.json
		if (file_exists("{$data_dir}.entities.json")) {
			file_put_contents("{$data_dir}.entities.json", json_encode(self::clearArray(json_decode(file_get_contents("{$data_dir}.entities.json")))));
		}
		// Perfect, ready to go!
	}

	public static function shopTypeChecker(string $data_dir, object $object, string $file) : void {
		$end = clone $object;
		foreach ($object as $name => $shop_a) {
			$shop = clone $shop_a;
			if (gettype($shop->admin) !== 'boolean') {
				self::errorLogger($data_dir, "NOTICE", "Value of 'admin' inside shop '{$name}', file '{$file}' is not a boolean! Corrected");
				$shop->admin = false;
			}

			if (gettype($shop->namevisible) !== 'boolean') {
				self::errorLogger($data_dir, "NOTICE", "Value of 'namevisible' inside shop '{$name}', file '{$file}' is not a boolean! Corrected");
				$shop->namevisible = false;
			}

			if (gettype($shop->items) !== 'array') {
				// Oh shit is not array!
				if (gettype($shop->items) === 'object') {
					self::errorLogger($data_dir, "NOTICE", "Value of 'items' inside shop '{$name}', file '{$file}' is an object! Corrected");
					$it = [];
					foreach ($shop->items as $item) {
						$it[] = $item;
					}
					$shop->items = $it;
					//var_dump($shop->items);
				} else {
					self::errorLogger($data_dir, "WARNING", "Value of 'items' inside shop '{$name}', file '{$file}' is not a correct value! Neutralized");
					$shop->items = [];
				}
			}

			if (gettype($shop->inventory) !== 'array') {
				// Oh shit is not array!
				if (gettype($shop->inventory) === 'object') {
					self::errorLogger($data_dir, "NOTICE", "Value of 'inventory' inside shop '{$name}', file '{$file}' is an object! Corrected");
					$it = [];
					foreach ($shop->inventory as $item) {
						$it[] = $item;
					}
					$shop->inventory = $it;
				} else {
					self::errorLogger($data_dir, "WARNING", "Value of 'inventory' inside shop '{$name}', file '{$file}' is not a correct value! Neutralized");
					$shop->inventory = [];
				}
			}

			// Validate and add if not present the history camp
			if (@$shop->history === null) {
				$shop->history = "";
			} elseif (gettype($shop->history) !== 'string') {
				$shop->history = "";
			}

			// Validate and add if not present the enabled camp
			if (@$shop->enabled === null) {
				$shop->enabled = true;
			} elseif (gettype($shop->enabled) !== 'bool') {
				$shop->enabled = true;
			}

			// Update the shop
			$end->{$name} = $shop;
		}
		file_put_contents($file, json_encode($end));
	}

	public static function comparator(Item $buy, int $sellcount, array $items) : string {
		foreach ($items as $item) {
			if (SerializedItem::decode($item->buy)->equals($buy) && SerializedItem::decode($item->sell)->getCount() === $sellcount) {
				return $item->sell;
			}
		}
	}

	public static function entityFixer(string $data_dir, object|array $data) : void {
		if (gettype($data) !== 'array') {
			if (gettype($data) === 'object') {
				file_put_contents($data_dir, (array)$data);
			}
		}
	}

	public static function fixArray(object $oldArray) : array {
		$data = [];
		foreach ($oldArray as $_k => $value) {
			$data[] = $value;
		}

		return $data;
	}

	public static function randomizer(int $lenght) : int {
		$buffer = "";
		for ($a = 0; $a < $lenght; $a++) {
			$buffer .= rand(0, 9);
		}
		return (int)$buffer;
	}

	public static function clearArray(array $array) : array {
		$return = [];
		foreach ($array as $element) {
			if ($element !== null) {
				$return[] = $element;
			}
		}
		return $return;
	}

	public static function getLatest(): object {
		$ch = curl_init("https://api.github.com/repos/FoxWorn3365/Shopkeepers/releases/latest");
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => [
				'User-Agent: FoxWorn3365.Shopkeepers.plugin',
				'Accept: application/vnd.github+json',
			],
		]);

		$raw = curl_exec($ch);
		if ($raw === false) {
			$err = curl_error($ch);
			curl_close($ch);
			throw new \RuntimeException("GitHub request failed: {$err}");
		}
		curl_close($ch);

		$data = json_decode($raw);
		if (!is_object($data) || !isset($data->tag_name)) {
			// Fallback to current version if something went wrong
			return (object)['tag_name' => Core::GIT_LAST_RELASE_TAG];
		}

		// Strip any suffix (e.g. “-beta”)
		if (str_contains($data->tag_name, '-')) {
			[$clean] = explode('-', $data->tag_name, 2);
			$data->tag_name = $clean;
		}

		return $data;
	}

	public static function isLatest(?object $data = null): bool {
		if ($data === null) {
			$data = self::getLatest();
		}

		return Core::GIT_LAST_RELASE_TAG === ($data->tag_name ?? '');
	}
}
