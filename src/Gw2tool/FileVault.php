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

use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileVault implements \Iterator
{
    /**
     *
     * @var string
     */
    protected $repository;

    /**
     *
     * @var \RecursiveIteratorIterator
     */
    protected $iterator;

    /**
     *
     * @var string
     */
    protected $currentFilename;

    /**
     *
     * @param string $path
     */
    public function __construct($path)
    {
        if (!is_dir($path)) {
            Fn::createDirectoryIfNotExists($path);
        }
        if (!is_writable($path)) {
            throw new Exception('The FileVault path is not writable.');
        }
        $this->repository = rtrim(rtrim($path, '\\'), '/');
    }

    /**
     *
     * @param string $filename
     * @return string
     */
    public function getVaultFilename($filename)
    {
        $hash = hash('sha256', $filename);
        $ext  = Fn::fileExtension($filename);
        return $this->repository . '/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . substr($hash, 4) . ($ext ? '.' . $ext : '');
    }

    /**
     *
     * @param string $filename
     * @return bool
     */
    public function exists($filename)
    {
        $path = $this->getVaultFilename($filename);
        return is_file($path);
    }

    /**
     *
     * @param string $filename
     * @return string
     */
    public function get($filename)
    {
        $this->checkExists($filename);
        $path = $this->getVaultFilename($filename);
        return file_get_contents($path);
    }

    /**
     *
     * @param type $filename
     * @throws Exception
     */
    protected function checkExists($filename)
    {
        if (!$this->exists($filename)) {
            throw new Exception('The filename does not exists <' . $filename . '>');
        }
    }

    /**
     *
     * @param string $filename
     * @return ResponseFile
     */
    public function getResponse($filename)
    {
        $this->checkExists($filename);
        $path = $this->getVaultFilename($filename);
        return new ResponseFile($path);
    }

    /**
     *
     * @param string $filename
     */
    public function remove($filename)
    {
        $path = $this->getVaultFilename($filename);
        if (is_file($path)) {
            @unlink($path);
        }
        if (is_file($path . '.original-filename')) {
            @unlink($path . '.original-filename');
        }
    }

    /**
     *
     * @param string $filename
     * @param string $content
     * @throws Exception
     */
    public function set($filename, $content)
    {
        $path = $this->getVaultFilename($filename);
        Fn::createDirectoryIfNotExists(\dirname($path));
        if (\is_string($content)) {
            file_put_contents($path, $content, LOCK_EX);
        } elseif (\is_resource($content)) {
            $fh = fopen($path, 'wb');
            stream_copy_to_stream($content, $fh);
            fclose($fh);
        } else {
            throw new Exception('The content is an invalid type (not resource or string).');
        }
        file_put_contents($path . '.original-filename', $filename, LOCK_EX);
    }

    /**
     *
     * @param string $from
     * @param string $to
     */
    public function copy($from, $to)
    {
        $this->checkExists(from);
        $this->remove($to);
        $pathfrom = $this->getVaultFilename($from);
        $handle   = fopen($pathfrom, 'rb');
        $this->set($to, $handle);
        @fclose($handle);
    }

    /**
     *
     * @param string $from
     * @param string $to
     */
    public function rename($from, $to)
    {
        $this->checkExists($from);
        $this->remove($to);
        $this->set($to, '');
        $pathfrom = $this->getVaultFilename($from);
        $pathto   = $this->getVaultFilename($to);
        rename($pathfrom, $pathto);
        $this->remove($from);
    }

    public function rewind()
    {
        $flags             = FilesystemIterator::KEY_AS_PATHNAME;
        $flags             |= FilesystemIterator::SKIP_DOTS;
        $flags             |= FilesystemIterator::CURRENT_AS_FILEINFO;
        $directoryIterator = new RecursiveDirectoryIterator($this->repository, $flags);

        $flags          = RecursiveIteratorIterator::LEAVES_ONLY;
        $this->iterator = new RecursiveIteratorIterator($directoryIterator, $flags);
    }

    public function valid()
    {
        $valid = $this->iterator->valid();
        while ($valid) {
            $filename = $this->iterator->current()->getPathname();
            $ext      = Fn::fileExtension($filename);
            if ($ext !== 'original-filename' && is_file($filename . '.original-filename')) {
                $this->currentFilename = $filename;
                break;
            }
            $this->iterator->next();
            $valid = $this->iterator->valid();
        }
        return $valid;
    }

    public function current()
    {
        return $this->currentFilename;
    }

    public function key()
    {
        return file_get_contents($this->currentFilename . '.original-filename');
    }

    public function next()
    {
        $this->iterator->next();
    }
}
