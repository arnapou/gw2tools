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

use Arnapou\GW2Api\Model\Guild;

class ModuleGeneric extends \Arnapou\GW2Tools\AbstractModule {

    public function configure() {
        parent::configure();

        // automatic redirect on the correct language
        $this->addRoute('', [$this, 'routeIndex']);

        // proxy images
        $this->addRoute('guild/{id}.png', [$this, 'routeImageGuild'])->assert('id', '[A-F0-9-]{35,40}');
        $this->addRoute('proxy/{id}.png', [$this, 'routeImageProxy'])->assert('id', '[A-F0-9]+/[0-9]+');
    }

    public function routeIndex() {
        $request = $this->getService()->getRequest();
        $langs   = Translator::getInstance()->getLangs();
        foreach ($request->getLanguages() as $language) {
            $lang = strtolower(substr($language, 0, 2));
            if (in_array($lang, $langs)) {
                return $this->getService()->returnResponseRedirect('./' . $lang . '/');
            }
        }
        return $this->getService()->returnResponseRedirect('./en/');
    }

    /**
     * 
     * @param string $id
     * @return \Arnapou\Toolbox\Http\Response
     */
    public function routeImageProxy($id) {
        try {
            $url = 'https://render.guildwars2.com/file/' . $id . '.png';
            return FileVault::getVaultProxy()->getResponse($url);
        }
        catch (Exception $e) {
            
        }
    }

    /**
     * 
     * @param string $id
     * @return \Arnapou\Toolbox\Http\Response
     */
    public function routeImageGuild($id) {
        try {
            $client = SimpleClient::getInstance();
            $guild  = new Guild($client, $id);

            $url = $guild->getIconLinkGw2Png();
            if ($url) {
                return FileVault::getVaultEmblems()->getResponse($url);
            }
        }
        catch (Exception $e) {
            
        }
    }

}
