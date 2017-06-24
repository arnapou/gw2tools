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

use Gw2tool\Exception\JsonException;

class Fn
{

    /**
     * 
     * @param string $path
     */
    private function __construct()
    {
        
    }

    /**
     * 
     * @param string $chars
     * @param integer $length
     * @return string
     */
    static function randomString($chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890', $length = 10)
    {
        $nbchars = strlen($chars);
        $string  = '';
        while ($length--) {
            $string .= $chars[mt_rand(0, $nbchars - 1)];
        }
        return $string;
    }

    /**
     * 
     * @param string $json
     * @return array
     */
    static function jsonDecode($json)
    {
        $json = trim($json);
        if ($json === '' || ($json[0] !== '{' && $json[0] !== '[' && $json[0] !== '"')) {
            throw new JsonException('Json not valid');
        }
        $array         = \json_decode($json, true);
        $jsonLastError = json_last_error();
        if ($jsonLastError !== JSON_ERROR_NONE) {
            $errors = array(
                JSON_ERROR_DEPTH            => 'Max depth reached.',
                JSON_ERROR_STATE_MISMATCH   => 'Mismatch modes or underflow.',
                JSON_ERROR_CTRL_CHAR        => 'Character control error.',
                JSON_ERROR_SYNTAX           => 'Malformed JSON.',
                JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, probably charset problem.',
                JSON_ERROR_RECURSION        => 'Recursion detected.',
                JSON_ERROR_INF_OR_NAN       => 'Inf or NaN',
                JSON_ERROR_UNSUPPORTED_TYPE => 'Unsupported type.',
            );
            throw new JsonException('Json error : ' . (isset($errors[$jsonLastError]) ? $errors[$jsonLastError] : 'Unknown error'));
        }
        return $array;
    }

    /**
     * Return mime type of specified file
     * @param string $filename
     * @return string
     */
    static function fileMimeType($filename)
    {
        static $types;
        static $mode;

        // avoid multiple calls to function_exists
        // store the result in $mode static variable
        if (!isset($mode)) {
            if (function_exists('mime_content_type') && file_exists($filename)) {
                $mode = 1;
            } elseif (function_exists('finfo_file') && file_exists($filename)) {
                $mode = 2;
            } else {
                $mode = 3;
            }
        }

        // try to get mime type
        $mimeType = '';
        if ($mode == 1) {
            $mimeType = mime_content_type($filename);
        } elseif ($mode == 2) {
            $finfo    = finfo_open(FILEINFO_MIME);
            $mimeType = finfo_file($finfo, $filename);
            finfo_close($finfo);
        }
        if (!empty($mimeType)) {
            return $mimeType;
        }

        // default mecanism
        if (!isset($types)) {
            $types = array(
                // application
                'ai'        => 'application/postscript',
                'asd'       => 'application/astound',
                'asn'       => 'application/astound',
                'atom'      => 'application/atom+xml',
                'bcpio'     => 'application/x-bcpio',
                'bin'       => 'application/octet-stream',
                'cab'       => 'application/x-shockwave-flash',
                'cdf'       => 'application/x-netcdf',
                'chm'       => 'application/mshelp',
                'class'     => 'application/octet-stream',
                'com'       => 'application/octet-stream',
                'cpio'      => 'application/x-cpio',
                'cpt'       => 'application/mac-compactpro',
                'csh'       => 'application/x-csh',
                'dcr'       => 'application/x-director',
                'dir'       => 'application/x-director',
                'dll'       => 'application/octet-stream',
                'dmg'       => 'application/octet-stream',
                'dms'       => 'application/octet-stream',
                'doc'       => 'application/msword',
                'dot'       => 'application/msword',
                'dtd'       => 'application/xml-dtd',
                'dvi'       => 'application/x-dvi',
                'dwg'       => 'application/acad',
                'dxf'       => 'application/dxf',
                'dxr'       => 'application/x-director',
                'eps'       => 'application/postscript',
                'evy'       => 'application/x-envoy',
                'exe'       => 'application/octet-stream',
                'ez'        => 'application/andrew-inset',
                'gram'      => 'application/srgs',
                'grxml'     => 'application/srgs+xml',
                'gtar'      => 'application/x-gtar',
                'gz'        => 'application/gzip',
                'hdf'       => 'application/x-hdf',
                'hlp'       => 'application/mshelp',
                'hqx'       => 'application/mac-binhex40',
                'jnlp'      => 'application/x-java-jnlp-file',
                'js'        => 'application/x-javascript',
                'latex'     => 'application/x-latex',
                'lha'       => 'application/octet-stream',
                'lzh'       => 'application/octet-stream',
                'man'       => 'application/x-troff-man',
                'man troff' => 'application/x-troff-man',
                'mathml'    => 'application/mathml+xml',
                'mbd'       => 'application/mbedlet',
                'me'        => 'application/x-troff-me',
                'mif'       => 'application/mif',
                'ms'        => 'application/x-troff-ms',
                'nc'        => 'application/x-netcdf',
                'nsc'       => 'application/x-nschat',
                'oda'       => 'application/oda',
                'ogg'       => 'application/ogg',
                'pdf'       => 'application/pdf',
                'pgn'       => 'application/x-chess-pgn',
                'php'       => 'application/x-httpd-php',
                'phtml'     => 'application/x-httpd-php',
                'pot'       => 'application/mspowerpoint',
                'pps'       => 'application/mspowerpoint',
                'ppt'       => 'application/vnd.ms-powerpoint',
                'ppz'       => 'application/mspowerpoint',
                'ps'        => 'application/postscript',
                'ptlk'      => 'application/listenup',
                'rdf'       => 'application/rdf+xml',
                'rm'        => 'application/vnd.rn-realmedia',
                'roff'      => 'application/x-troff',
                'rtc'       => 'application/rtc',
                'rtf'       => 'application/rtf',
                'sca'       => 'application/x-supercard',
                'sh'        => 'application/x-sh',
                'shar'      => 'application/x-shar',
                'sit'       => 'application/x-stuffit',
                'skd'       => 'application/x-koan',
                'skm'       => 'application/x-koan',
                'skp'       => 'application/x-koan',
                'skt'       => 'application/x-koan',
                'smi'       => 'application/smil',
                'smil'      => 'application/smil',
                'smp'       => 'application/studiom',
                'so'        => 'application/octet-stream',
                'spl'       => 'application/futuresplash',
                'spl'       => 'application/x-futuresplash',
                'spr'       => 'application/x-sprite',
                'sprite'    => 'application/x-sprite',
                'src'       => 'application/x-wais-source',
                'sv4cpio'   => 'application/x-sv4cpio',
                'sv4crc'    => 'application/x-sv4crc',
                'swf'       => 'application/x-shockwave-flash',
                't'         => 'application/x-troff',
                'tar'       => 'application/x-tar',
                'tbk'       => 'application/toolbook',
                'tcl'       => 'application/x-tcl',
                'tex'       => 'application/x-tex',
                'texi'      => 'application/x-texinfo',
                'texinfo'   => 'application/x-texinfo',
                'tr'        => 'application/x-troff',
                'troff'     => 'application/x-troff',
                'tsp'       => 'application/dsptype',
                'ustar'     => 'application/x-ustar',
                'vcd'       => 'application/x-cdlink',
                'vmd'       => 'application/vocaltec-media-desc',
                'vmf'       => 'application/vocaltec-media-file',
                'vxml'      => 'application/voicexml+xml',
                'wbmxl'     => 'application/vnd.wap.wbxml',
                'wmlc'      => 'application/vnd.wap.wmlc',
                'wmlsc'     => 'application/vnd.wap.wmlscriptc',
                'xht'       => 'application/xhtml+xml',
                'xhtml'     => 'application/xhtml+xml',
                'xla'       => 'application/msexcel',
                'xls'       => 'application/vnd.ms-excel',
                'xml'       => 'application/xml',
                'xsl'       => 'application/xml',
                'xslt'      => 'application/xslt+xml',
                'xul'       => 'application/vnd.mozilla.xul+xml',
                'z'         => 'application/x-compress',
                'zip'       => 'application/zip',
                // audio
                'aif'       => 'audio/x-aiff',
                'aifc'      => 'audio/x-aiff',
                'aiff'      => 'audio/x-aiff',
                'au'        => 'audio/basic',
                'cht'       => 'audio/x-dspeeh',
                'dus'       => 'audio/x-dspeeh',
                'es'        => 'audio/echospeech',
                'kar'       => 'audio/midi',
                'm3u'       => 'audio/x-mpegurl',
                'm4a'       => 'audio/mp4a-latm',
                'm4b'       => 'audio/mp4a-latm',
                'm4p'       => 'audio/mp4a-latm',
                'mid'       => 'audio/midi',
                'midi'      => 'audio/midi',
                'mp2'       => 'audio/mpeg',
                'mp3'       => 'audio/mpeg',
                'mpga'      => 'audio/mpeg',
                'ra'        => 'audio/x-pn-realaudio',
                'ram'       => 'audio/x-pn-realaudio',
                'rpm'       => 'audio/x-pn-realaudio-plugin',
                'snd'       => 'audio/basic',
                'stream'    => 'audio/x-qt-stream',
                'tsi'       => 'audio/tsplayer',
                'vox'       => 'audio/voxware',
                'wav'       => 'audio/x-wav',
                // chemical
                'pdb'       => 'chemical/x-pdb',
                'xyz'       => 'chemical/x-xyz',
                // drawing
                'dwf'       => 'drawing/x-dwf',
                // image
                'bmp'       => 'image/bmp',
                'cgm'       => 'image/cgm',
                'cod'       => 'image/cis-cod',
                'djv'       => 'image/vnd.djvu',
                'djvu'      => 'image/vnd.djvu',
                'fh4'       => 'image/x-freehand',
                'fh5'       => 'image/x-freehand',
                'fhc'       => 'image/x-freehand',
                'fif'       => 'image/fif',
                'gif'       => 'image/gif',
                'ico'       => 'image/x-icon',
                'ief'       => 'image/ief',
                'jp2'       => 'image/jp2',
                'jpeg'      => 'image/jpeg',
                'jpe'       => 'image/jpeg',
                'jpg'       => 'image/jpeg',
                'mac'       => 'image/x-macpaint',
                'mcf'       => 'image/vasa',
                'pbm'       => 'image/x-portable-bitmap',
                'pct'       => 'image/pict',
                'pgm'       => 'image/x-portable-graymap',
                'pic'       => 'image/pict',
                'pict'      => 'image/pict',
                'png'       => 'image/png',
                'pnm'       => 'image/x-portable-anymap',
                'pntg'      => 'image/x-macpaint',
                'pnt'       => 'image/x-macpaint',
                'ppm'       => 'image/x-portable-pixmap',
                'qtif'      => 'image/x-quicktime',
                'qti'       => 'image/x-quicktime',
                'ras'       => 'image/cmu-raster',
                'rgb'       => 'image/x-rgb',
                'svg'       => 'image/svg+xml',
                'tiff'      => 'image/tiff',
                'tif'       => 'image/tiff',
                'wbmp'      => 'image/vnd.wap.wbmp',
                'xbm'       => 'image/x-xbitmap',
                'xpm'       => 'image/x-xpixmap',
                'xwd'       => 'image/x-windowdump',
                // model
                'iges'      => 'model/iges',
                'igs'       => 'model/iges',
                'mesh'      => 'model/mesh',
                'msh'       => 'model/mesh',
                'silo'      => 'model/mesh',
                'vrml'      => 'model/vrml',
                'wrl'       => 'model/vrml',
                // text
                'asc'       => 'text/plain',
                'css'       => 'text/css',
                'csv'       => 'text/comma-separated-values',
                'etx'       => 'text/x-setext',
                'html'      => 'text/html',
                'htm'       => 'text/html',
                'ics'       => 'text/calendar',
                'ifb'       => 'text/calendar',
                'js'        => 'text/javascript',
                'rtf'       => 'text/rtf',
                'rtx'       => 'text/richtext',
                'sgml'      => 'text/sgml',
                'sgm'       => 'text/sgml',
                'shtml'     => 'text/html',
                'spc'       => 'text/x-speech',
                'sql'       => 'text/plain',
                'talk'      => 'text/x-speech',
                'tsv'       => 'text/tab-separated-values',
                'txt'       => 'text/plain',
                'wmls'      => 'text/vnd.wap.wmlscript',
                'wml'       => 'text/vnd.wap.wml',
                // video
                'avi'       => 'video/x-msvideo',
                'dif'       => 'video/x-dv',
                'dv'        => 'video/x-dv',
                'm4u'       => 'video/vnd.mpegurl',
                'movie'     => 'video/x-sgi-movie',
                'mov'       => 'video/quicktime',
                'mp4'       => 'video/mp4',
                'mpeg'      => 'video/mpeg',
                'mpe'       => 'video/mpeg',
                'mpg'       => 'video/mpeg',
                'mxu'       => 'video/vnd.mpegurl',
                'qt'        => 'video/quicktime',
                'vivo'      => 'video/vnd.vivo',
                '*viv'      => 'video/vnd.vivo',
                // workbook
                'vts'       => 'workbook/formulaone',
                'vtts'      => 'workbook/formulaone',
                // x-conference
                'ice'       => 'x-conference/x-cooltalk',
                // x-world
                '3dmf'      => 'x-world/x-3dmf',
                '3dm'       => 'x-world/x-3dmf',
                '3qd3d'     => 'x-world/x-3dmf',
                'qd3'       => 'x-world/x-3dmf',
                'wrl'       => 'x-world/x-vrml',
            );
        }

        $ext = strtolower(self::fileExtension($filename));
        if (array_key_exists($ext, $types)) {
            return $types[$ext];
        }
        return 'multipart/mime';
    }

    /**
     * Create folder with full path if needed
     * @param string $path
     * @param octal $mode
     */
    static function createDirectoryIfNotExists($path, $mode = 0777)
    {
        if (!file_exists($path)) {
            @mkdir($path, $mode, true);
        }
    }

    /**
     * Return file extension
     * @param string $filename
     * @return string
     */
    static function fileExtension($filename)
    {
        $ext = '';
        $i   = strrpos($filename, '.');
        if ($i !== false) {
            $ext = substr($filename, $i + 1, strlen($filename) - $i - 1);
        }
        return $ext;
    }
}
