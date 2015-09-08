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

class Translator extends \Arnapou\Toolbox\Translation\Translator {

    /**
     *
     * @var Translator
     */
    static protected $instance;

    /**
     *
     * @var Translator
     */
    static protected $langs = [
        'en',
        'fr',
        'de',
        'es',
    ];

    /**
     * 
     * @return Translator
     */
    static public function getInstance() {
        if (!isset(self::$instance)) {
            $translator = new self();
            foreach (self::$langs as $lang) {
                $translator->addDictionary($lang, __DIR__ . '/lang/lang.' . $lang . '.php');
            }
            self::$instance = $translator;
        }
        return self::$instance;
    }

    /**
     * 
     * @return array
     */
    public function getLangs() {
        return self::$langs;
    }

}
