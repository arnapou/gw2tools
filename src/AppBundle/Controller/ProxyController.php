<?php
/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use Arnapou\GW2Api\Core\ClientVersion1;
use Arnapou\GW2Api\Core\Curl;
use Gw2tool\FileVault;
use Gw2tool\ResponseFile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ProxyController extends AbstractController
{
    /**
     *
     * @param FileVault $vault
     * @param string    $url
     * @param int   $retention
     * @return ResponseFile
     */
    protected function getVaultResponseFile(FileVault $vault, $url, $retention)
    {
        if ($vault->exists($url)) {
            $filepath = $vault->getVaultFilename($url);
            if (time() - filemtime($filepath) < 86400 * $retention) {
                return $vault->getResponse($url);
            }
        }
        $curl = new Curl();
        $curl->setUrl($url);
        $curl->setTimeout(10);
        $response = $curl->execute();
        if ($response->getInfoHttpCode() == 200) {
            $content = $response->getContent();
            $vault->set($url, $content);
            return $vault->getResponse($url);
        }
        return null;
    }

    /**
     *
     * @return int
     */
    public function getGuildEmblemSize()
    {
        return $this->getParameter('guild.emblem.size');
    }

    /**
     *
     * @Route("/proxy/file/{signature}/{file}.{format}", requirements={
     *     "signature": "[a-fA-F0-9]+",
     *     "file": "[0-9]+",
     *     "format": "jpg|png"
     * })
     *
     * @param string  $signature
     * @param string  $file
     * @param string  $format
     * @param Request $request
     * @return ResponseFile
     */
    public function fileAction($signature, $file, $format, Request $request)
    {
        try {
            $url       = 'https://render.guildwars2.com/file/' . $signature . '/' . $file . '.' . $format;
            $retention = $this->getParameter('proxy.file.retention');
            $vault     = new FileVault($this->getDataPath() . '/proxy/file');
            $response = $this->getVaultResponseFile($vault, $url, $retention);
        } catch (\Exception $e) {
            $response = null;
        }
        return $response ?: new RedirectResponse($url);
    }

    /**
     *
     * @Route("/proxy/guild/{id}.svg", requirements={"id": "[a-fA-F0-9-]+"})
     *
     * @param string  $id
     * @param Request $request
     * @return ResponseFile
     */
    public function guildEmblemSvgAction($id, Request $request)
    {
        try {
            $client = $this->getGwEnvironment()->getClientVersion1();
            $infos  = $client->apiGuildDetails($id);
            if (\is_array($infos) && isset($infos['guild_name'])) {
                $slug      = strtolower(str_replace(' ', '-', $infos['guild_name']));
                $url       = 'http://guilds.gw2w2w.com/guilds/' . rawurlencode($slug) . '/' . $this->getGuildEmblemSize() . '.svg';
                $retention = $this->getParameter('proxy.guild.emblem.retention');
                $vault     = new FileVault($this->getDataPath() . '/proxy/guild');
                $response  = $this->getVaultResponseFile($vault, $url, $retention);
                if ($response) {
                    $response->setFileCacheTime(86400);
                }
                return $response;
            }
        } catch (\Exception $e) {
        }
    }

    /**
     *
     * @Route("/proxy/guild/{id}.png", requirements={"id": "[a-fA-F0-9-]+"})
     *
     * @param string  $id
     * @param Request $request
     * @return ResponseFile
     */
    public function guildEmblemPngAction($id, Request $request)
    {
        try {
            $client = new ClientVersion1($this->getGwEnvironment());
            $infos  = $client->apiGuildDetails($id);
            if (\is_array($infos) && isset($infos['guild_name'])) {
                $url       = 'http://data.gw2.fr/guild-emblem/name/' . rawurlencode($infos['guild_name']) . '/' . $this->getGuildEmblemSize() . '.png';
                $retention = $this->getParameter('proxy.guild.emblem.retention');
                $vault     = new FileVault($this->getDataPath() . '/proxy/guild');
                $response  = $this->getVaultResponseFile($vault, $url, $retention);
                if ($response) {
                    $response->setFileCacheTime(86400);
                }
                return $response;
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * @return string
     */
    public function getViewPrefix()
    {
        return '';
    }
}
