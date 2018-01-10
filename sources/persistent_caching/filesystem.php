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
 * Cache driver class.
 */
class Persistent_caching_filesystem
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        require_code('files');

        global $PC_FC_CACHE;
        $PC_FC_CACHE = array();
    }

    /**
     * Instruction to load up the objects list.
     *
     * @return array The list of objects
     */
    public function load_objects_list()
    {
        /* No concurrency
        if ($this->objects_list === null) {
            $this->objects_list = $this->get('PERSISTENT_CACHE_OBJECTS');
            if ($this->objects_list === null) {
                $this->objects_list = array();
            }
        }
        return $this->objects_list;*/

        $objects_list = $this->get('PERSISTENT_CACHE_OBJECTS');
        if (!is_array($objects_list)) {
            $objects_list = array();
        }
        return $objects_list;
    }

    /**
     * Get data from the persistent cache.
     *
     * @param  string $key Key
     * @param  ?TIME $min_cache_date Minimum timestamp that entries from the cache may hold (null: don't care)
     * @return ?mixed The data (null: not found / null entry)
     */
    public function get($key, $min_cache_date = null)
    {
        if ($key != 'PERSISTENT_CACHE_OBJECTS'/*this key is too volatile with concurrency*/) {
            global $PC_FC_CACHE;
            if ($min_cache_date === null && isset($PC_FC_CACHE[$key])) {
                return $PC_FC_CACHE[$key];
            }
        }

        //@header('X-Persistent-Cache: caches/persistent/' . md5($key) . '.gcd');

        clearstatcache();

        $myfile = @fopen(get_custom_file_base() . '/caches/persistent/' . md5($key) . '.gcd', 'rb');
        if ($myfile === false) {
            return null;
        }
        if ($min_cache_date !== null) { // Code runs here as we know file exists at this point
            if (filemtime(get_custom_file_base() . '/caches/persistent/' . md5($key) . '.gcd') < $min_cache_date) {
                fclose($myfile);
                return null;
            }
        }
        flock($myfile, LOCK_SH);
        $contents = '';
        while (!feof($myfile)) {
            $contents .= fread($myfile, 32768);
        }

        $ret = @unserialize($contents);

        flock($myfile, LOCK_UN);
        fclose($myfile);

        $PC_FC_CACHE[$key] = $ret;

        return $ret;
    }

    /**
     * Put data into the persistent cache.
     *
     * @param  string $key Key
     * @param  mixed $data The data
     * @param  integer $flags Various flags (parameter not used)
     * @param  ?integer $expire_secs The expiration time in seconds (null: no expiry)
     */
    public function set($key, $data, $flags = 0, $expire_secs = null)
    {
        global $PC_FC_CACHE;
        $PC_FC_CACHE[$key] = $data;

        if ($key !== 'PERSISTENT_CACHE_OBJECTS') {
            // Update list of persistent-objects
            $objects_list = $this->load_objects_list();
            if (!array_key_exists($key, $objects_list)) {
                $objects_list[$key] = true;
                $this->set('PERSISTENT_CACHE_OBJECTS', $objects_list);
            }
        }

        require_code('files');
        $path = get_custom_file_base() . '/caches/persistent/' . md5($key) . '.gcd';
        $to_write = serialize($data);
        cms_file_put_contents_safe($path, $to_write, FILE_WRITE_FIX_PERMISSIONS);
    }

    /**
     * Delete data from the persistent cache.
     *
     * @param  string $key Key
     */
    public function delete($key)
    {
        if ($key !== 'PERSISTENT_CACHE_OBJECTS') {
            // Update list of persistent-objects
            $objects_list = $this->load_objects_list();
            unset($objects_list[$key]);
            $this->set('PERSISTENT_CACHE_OBJECTS', $objects_list);
        }

        // Ideally we'd lock while we delete, but it's not stable (and the workaround would be too slow for our efficiency context). So some people reading may get errors while we're clearing the cache. Fortunately this is a rare op to perform.
        @unlink(get_custom_file_base() . '/caches/persistent/' . md5($key) . '.gcd');

        global $PC_FC_CACHE;
        unset($PC_FC_CACHE[$key]);
    }

    /**
     * Remove all data from the persistent cache.
     */
    public function flush()
    {
        // Update list of persistent-objects
        $objects_list = array();
        $this->set('PERSISTENT_CACHE_OBJECTS', $objects_list);

        $d = opendir(get_custom_file_base() . '/caches/persistent');
        while (($e = readdir($d)) !== false) {
            if (substr($e, -4) == '.gcd') {
                // Ideally we'd lock while we delete, but it's not stable (and the workaround would be too slow for our efficiency context). So some people reading may get errors while we're clearing the cache. Fortunately this is a rare op to perform.
                @unlink(get_custom_file_base() . '/caches/persistent/' . $e);
            }
        }
        closedir($d);

        global $PC_FC_CACHE;
        $PC_FC_CACHE = array();
    }
}
