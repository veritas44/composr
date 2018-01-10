<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */

/**
 * Find available mime types.
 *
 * @param  boolean $as_admin Whether there are admin privileges, to render dangerous media types (client-side risk only)
 * @return array The MIME types
 */
function get_mime_types($as_admin)
{
    $mime_types = array(
        // Plain text
        '1st' => 'text/plain',
        'txt' => 'text/plain',
        '' => 'text/plain', // No file type implies a plain text file, e.g. README

        // Documents
        'pdf' => 'application/pdf',
        'rtf' => 'text/rtf',
        'ps' => 'application/postscript',
        'html' => $as_admin ? 'text/html' : 'application/octet-stream',
        'htm' => $as_admin ? 'text/html' : 'application/octet-stream',

        // Open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odp' => 'application/vnd.oasis.opendocument.presentation',

        // Microsoft office
        'doc' => 'application/msword',
        'mdb' => 'application/x-msaccess',
        'xls' => 'application/vnd.ms-excel',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'docb' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'docm' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xlsb' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xlsm' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

        // iWork
        'pages' => 'application/x-iwork-pages-sffpages',
        'numbers' => 'application/x-iwork-pages-sffnumbers',
        'keynote' => 'application/x-iwork-pages-sffkey',

        // XML
        'xml' => $as_admin ? 'text/xml' : 'application/octet-stream',
        'rss' => $as_admin ? 'application/rss+xml' : 'application/octet-stream',
        'atom' => $as_admin ? 'application/atom+xml' : 'application/octet-stream',

        // Presentations/Animations/3D
        'ppt' => 'application/powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'pptb' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'pptm' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'svg' => $as_admin ? 'image/svg+xml' : 'application/octet-stream',
        'wrl' => 'model/vrml',
        'vrml' => 'model/vrml',
        'swf' => $as_admin ? 'application/x-shockwave-flash' : 'application/octet-stream',

        // Images
        'png' => 'image/png',
        'gif' => 'image/gif',
        'jpg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'psd' => 'image/x-photoshop',
        'webp' => 'image/webp',

        // Non/badly compressed images
        'bmp' => 'image/x-MS-bmp',
        'tga' => 'image/x-targa',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'ico' => 'image/vnd.microsoft.icon',

        // Movies
        'avi' => 'video/mpeg', //'video/x-ms-asf' works with the plugin on Windows Firefox but nothing else, //'video/x-msvideo' is correct but does not get recognised by Microsoft Firefox WMV plugin and confuses RealMedia Player if it sees data transferred under that mime type,
        'mp2' => 'video/mpeg',
        'mpv2' => 'video/mpeg',
        'm2v' => 'video/mpeg',
        'mpa' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mpe' => 'video/mpeg',
        '3g2' => 'video/3gpp',
        '3gp' => 'video/3gpp',
        '3gp2' => 'video/3gpp',
        '3gpp' => 'video/3gpp',
        '3p' => 'video/3gpp',
        'f4v' => 'video/mp4',
        'mp4' => 'video/mp4',
        'm4v' => 'video/mp4',
        'mpeg' => 'video/mpeg',
        'ogv' => 'video/ogg',
        'webm' => 'video/webm',

        // Proprietary movie formats
        'mov' => 'video/mp4', // in the past may have been 'video/quicktime',
        'qt' => 'video/quicktime',
        'wmv' => 'video/x-ms-wmv',
        'ram' => 'audio/x-pn-realaudio',
        'rm' => 'audio/x-pn-realaudio',
        'asf' => 'video/x-ms-asf',

        // Audio
        'ra' => 'audio/x-pn-realaudio-plugin',
        'wma' => 'audio/x-ms-wma',
        'wav' => 'audio/x-wav',
        'mp3' => 'audio/mpeg',
        'ogg' => 'audio/ogg',
        'mid' => 'audio/midi',
        'aif' => 'audio/x-aiff',
        'aifc' => 'audio/x-aiff',
        'aiff' => 'audio/x-aiff',

        // File sharing
        'torrent' => 'application/x-bittorrent',

        // Misc data
        'dat' => 'application/octet-stream',
    );
    if (file_exists(get_file_base() . '/data/jwplayer.flash.swf')) {
        $mime_types['flv'] = 'video/x-flv';
    }

    return $mime_types;
}

/**
 * Find the mime type for the given file extension. It does not take into account whether the file type has been white-listed or not, and returns a binary download mime type for any unknown extensions.
 *
 * @param  string $extension The file extension (no dot)
 * @param  boolean $as_admin Whether there are admin privileges, to render dangerous media types (client-side risk only)
 * @return string The MIME type
 */
function get_mime_type($extension, $as_admin)
{
    $extension = strtolower($extension);

    $mime_types = get_mime_types($as_admin);

    if (array_key_exists($extension, $mime_types)) {
        return $mime_types[$extension];
    }

    return 'application/octet-stream';
}
