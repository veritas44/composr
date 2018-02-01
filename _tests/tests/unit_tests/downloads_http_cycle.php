<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2018

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    testing_platform
 */

/**
 * Composr test case class (unit testing).
 */
class downloads_http_cycle_test_set extends cms_test_case
{
    public function setUp()
    {
        parent::setUp();

        $this->establish_admin_session();
    }

    public function testUpload()
    {
        require_code('uploads');

        $url = build_url(array('page' => 'cms_downloads', 'type' => '_add', 'keep_fatalistic' => 1), 'cms');
        $post_params = array(
            'name' => 'Test' . uniqid('', true),
            'csrf_token' => get_session_id(),
            'category_id' => strval(db_get_first_id()),
            'author' => 'Test',
            'description' => '',
            'additional_details' => '',
            'url_redirect' => '',
            'validated' => '1',
        );
        $files = array(
            'file__upload' => get_file_base() . '/data/images/donate.png',
        );
        $data = http_get_contents($url->evaluate(), array('trigger_error' => false, 'post_params' => $post_params, 'cookies' => array(get_session_cookie() => get_session_id()), 'files' => $files));
        $this->assertTrue(is_string($data));
    }

    public function testDownload()
    {
        $max_download_id = $GLOBALS['SITE_DB']->query_select_value_if_there('download_downloads', 'MAX(id)');
        if ($max_download_id === null) {
            return;
        }
        $url = find_script('dload') . '?id=' . strval($max_download_id);
        $result = cms_http_request($url, array('cookies' => array(get_session_cookie() => get_session_id())));
        $this->assertTrue($result->data == file_get_contents(get_file_base() . '/data/images/donate.png'));
        $this->assertTrue($result->download_mime_type == 'application/octet-stream');
        $this->assertTrue($result->filename == 'donate.png');
    }
}