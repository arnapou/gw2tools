<?php

/*
 * This file is part of the Arnapou GW2Tools package.
 *
 * (c) Arnaud Buathier <arnaud@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gw2tool;

use SplFileInfo;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class ResponseFile extends \Symfony\Component\HttpFoundation\Response {

    /**
     *
     * @var SplFileInfo
     */
    protected $file = null;

    /**
     *
     * @param string $path
     * @param string $filename
     * @param int $cachetime time in seconds (1 year by default)
     */
    public function __construct($path, $filename = null, $cachetime = 31536000) {
        parent::__construct();

        if (!is_file($path)) {
            throw new FileNotFoundException($path);
        }
        $this->file = new SplFileInfo($path);
        if (empty($filename)) {
            $filename = $this->file->getBasename();
        }

        // last modified
        $lastModified     = $this->file->getMTime();
        $dateLastModified = new \DateTime();
        $dateLastModified->setTimestamp($lastModified);
        $this->setLastModified($dateLastModified);

        // etag
        $etag = dechex($lastModified);
        $this->setEtag($etag);

        $this->setFileCacheTime($cachetime);

        $mimeType = Fn::fileMimeType($filename);

        // headers
        $this->setContentLength($this->file->getSize());
        $this->setContentType($mimeType);
        $this->setContentDispositionInline($filename);
    }

    /**
     *
     * @param int $time en seconds
     */
    public function setFileCacheTime($time) {
        // max age
        $maxAge = $time;
        $this->setMaxAge($maxAge);

        // expires
        $dateExpire = new \DateTime();
        $dateExpire->setTimestamp($this->getLastModified()->getTimestamp() + $maxAge);
        $this->setExpires($dateExpire);
    }

    public function send() {
        if (function_exists('http_match_etag') && function_exists('http_match_modified')) {
            $lastModified = $this->getLastModified()->getTimestamp();
            if (http_match_etag($this->getEtag()) || http_match_modified($lastModified)) {
                $this->setNotModified();
                $this->sendHeaders();
                exit;
            }
        }
        $this->sendHeaders();
        if (empty($this->content)) {
            readfile($this->file->getPathname());
        }
        else {
            $this->sendContent();
        }
    }

    public function sendHeaders() {
        if (headers_sent()) {
            return $this;
        }
        header('Pragma:', true);
        parent::sendHeaders();
    }

    /**
     *
     * @param string $filename
     * @return Response 
     */
    public function setContentDispositionInline($filename) {
        $filename = str_replace(array('\\', '/', ':'), '_', $filename);
        $this->headers->set('Content-Disposition', 'inline; filename="' . $filename . '"');
        return $this;
    }

    /**
     * 
     * @param string $filename
     * @return Response 
     */
    public function setContentDispositionAttachment($filename) {
        $filename = str_replace(array('\\', '/', ':'), '_', $filename);
        $this->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $this;
    }

    /**
     *
     * @param string $filename
     * @return Response 
     */
    public function forceDownload($filename) {
        $this->setContentType('application/force-download');
        $this->setContentDispositionAttachment($filename);
        return $this;
    }

    /**
     *
     * @return Response 
     */
    public function setNoCache() {
        $expire = new \DateTime();
        $expire->setDate(1980, 01, 01);
        $expire->setTime(0, 0, 0);

        $this->setExpires($expire);
        $this->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->headers->set('Pragma', 'no-cache');
        return $this;
    }

    /**
     *
     * @param int $length
     * @return Response 
     */
    public function setContentLength($length) {
        $this->headers->set('Content-Length', $length);
        return $this;
    }

    /**
     * 
     * @param string $type
     * @return Response
     */
    public function setContentType($type) {
        $this->headers->set('Content-Type', $type);
        return $this;
    }

    /**
     * 
     * @return string
     */
    public function getStatusText() {
        return $this->statusText;
    }

}
