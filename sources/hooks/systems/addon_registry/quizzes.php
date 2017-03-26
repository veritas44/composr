<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    quizzes
 */

/**
 * Hook class.
 */
class Hook_addon_registry_quizzes
{
    /**
     * Get a list of file permissions to set
     *
     * @param  boolean $runtime Whether to include wildcards represented runtime-created chmoddable files
     * @return array File permissions to set
     */
    public function get_chmod_array($runtime = false)
    {
        return array();
    }

    /**
     * Get the version of Composr this addon is for
     *
     * @return float Version number
     */
    public function get_version()
    {
        return cms_version_number();
    }

    /**
     * Get the description of the addon
     *
     * @return string Description of the addon
     */
    public function get_description()
    {
        return 'Construct competitions, surveys, and tests, for members to perform. Highly configurable, and comes with administrative tools to handle the results.';
    }

    /**
     * Get a list of tutorials that apply to this addon
     *
     * @return array List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_quizzes',
        );
    }

    /**
     * Get a mapping of dependency types
     *
     * @return array File permissions to set
     */
    public function get_dependencies()
    {
        return array(
            'requires' => array(),
            'recommends' => array(),
            'conflicts_with' => array(),
        );
    }

    /**
     * Explicitly say which icon should be used
     *
     * @return URLPATH Icon
     */
    public function get_default_icon()
    {
        return 'themes/default/images/icons/48x48/menu/rich_content/quiz.png';
    }

    /**
     * Get a list of files that belong to this addon
     *
     * @return array List of files
     */
    public function get_file_list()
    {
        return array(
            'themes/default/images/icons/24x24/menu/cms/quiz/find_winners.png',
            'themes/default/images/icons/24x24/menu/cms/quiz/quiz_results.png',
            'themes/default/images/icons/48x48/menu/cms/quiz/find_winners.png',
            'themes/default/images/icons/48x48/menu/cms/quiz/quiz_results.png',
            'themes/default/images/icons/24x24/menu/rich_content/quiz.png',
            'themes/default/images/icons/48x48/menu/rich_content/quiz.png',
            'themes/default/images/icons/24x24/menu/cms/quiz/index.html',
            'themes/default/images/icons/48x48/menu/cms/quiz/index.html',
            'sources/hooks/systems/notifications/quiz_results.php',
            'sources/hooks/systems/config/points_ADD_QUIZ.php',
            'sources/hooks/systems/config/quiz_show_stats_count_total_open.php',
            'sources/hooks/systems/meta/quiz.php',
            'sources/hooks/blocks/side_stats/quiz.php',
            'themes/default/templates/QUIZ_ARCHIVE_SCREEN.tpl',
            'themes/default/text/QUIZ_SURVEY_ANSWERS_MAIL.txt',
            'themes/default/text/QUIZ_TEST_ANSWERS_MAIL.txt',
            'sources/hooks/systems/content_meta_aware/quiz.php',
            'sources/hooks/systems/commandr_fs/quizzes.php',
            'sources/hooks/systems/addon_registry/quizzes.php',
            'sources/hooks/modules/admin_import_types/quizzes.php',
            'themes/default/templates/QUIZ_BOX.tpl',
            'themes/default/templates/QUIZ_SCREEN.tpl',
            'themes/default/templates/QUIZ_RESULTS.tpl',
            'themes/default/templates/QUIZ_DONE_SCREEN.tpl',
            'themes/default/templates/QUIZ_RESULT_SCREEN.tpl',
            'themes/default/templates/QUIZ_RESULTS_SCREEN.tpl',
            'themes/default/templates/MEMBER_QUIZ_ENTRIES.tpl',
            'adminzone/pages/modules/admin_quiz.php',
            'cms/pages/modules/cms_quiz.php',
            'lang/EN/quiz.ini',
            'site/pages/modules/quiz.php',
            'sources/hooks/systems/sitemap/quiz.php',
            'sources/hooks/modules/admin_newsletter/quiz.php',
            'sources/hooks/modules/admin_unvalidated/quiz.php',
            'sources/hooks/modules/search/quiz.php',
            'sources/hooks/modules/members/quiz.php',
            'sources/hooks/systems/page_groupings/quiz.php',
            'sources/quiz.php',
            'sources/quiz2.php',
            'sources/hooks/systems/preview/quiz.php',
            'themes/default/css/quizzes.css',
            'sources/hooks/systems/config/search_quiz.php',
        );
    }

    /**
     * Get mapping between template names and the method of this class that can render a preview of them
     *
     * @return array The mapping
     */
    public function tpl_previews()
    {
        return array(
            'templates/QUIZ_RESULTS_SCREEN.tpl' => 'administrative__quiz_results_screen',
            'templates/QUIZ_RESULT_SCREEN.tpl' => 'administrative__quiz_result_screen',
            'templates/QUIZ_RESULTS.tpl' => 'quiz_results',
            'templates/QUIZ_BOX.tpl' => 'quiz_archive_screen',
            'templates/QUIZ_ARCHIVE_SCREEN.tpl' => 'quiz_archive_screen',
            'templates/QUIZ_SCREEN.tpl' => 'quiz_screen',
            'text/QUIZ_SURVEY_ANSWERS_MAIL.txt' => 'quiz_survey_answers_mail',
            'text/QUIZ_TEST_ANSWERS_MAIL.txt' => 'quiz_test_answers_mail',
            'templates/QUIZ_DONE_SCREEN.tpl' => 'quiz_done_screen',
            'templates/MEMBER_QUIZ_ENTRIES.tpl' => 'member_quiz_entries',
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__quiz_results_screen()
    {
        $fields = new Tempcode();
        foreach (placeholder_array() as $k => $v) {
            $fields->attach(do_lorem_template('MAP_TABLE_FIELD', array(
                'ABBR' => '',
                'NAME' => lorem_phrase(),
                'VALUE' => lorem_phrase(),
            )));
        }
        $summary = do_lorem_template('MAP_TABLE', array(
            'WIDTH' => placeholder_number(),
            'FIELDS' => $fields,
        ));

        return array(
            lorem_globalise(do_lorem_template('QUIZ_RESULTS_SCREEN', array(
                'TITLE' => lorem_title(),
                'SUMMARY' => $summary,
                'RESULTS' => placeholder_table(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__administrative__quiz_result_screen()
    {
        $given_answers_arr = array();
        foreach (array(true, false, null) as $was_correct) {
            $given_answers_arr[] = array(
                'QUESTION' => lorem_phrase(),
                'GIVEN_ANSWER' => lorem_phrase(),
                'WAS_CORRECT' => $was_correct,
                'CORRECT_ANSWER' => lorem_phrase(),
                'CORRECT_EXPLANATION' => lorem_paragraph_html(),
            );
        }

        return array(
            lorem_globalise(do_lorem_template('QUIZ_RESULT_SCREEN', array(
                'TITLE' => lorem_title(),
                'USERNAME' => lorem_phrase(),
                'MEMBER_URL' => placeholder_url(),
                'DATE' => placeholder_date(),
                '_DATE' => placeholder_date_raw(),
                'ENTRY_ID' => placeholder_id(),
                'QUIZ_NAME' => lorem_phrase(),
                'GIVEN_ANSWERS_ARR' => $given_answers_arr,
                'PASSED' => true,
                'TYPE' => lorem_phrase(),
                '_TYPE' => 'TEST',
                'MARKS' => placeholder_number(),
                'POTENTIAL_EXTRA_MARKS' => placeholder_number(),
                'OUT_OF' => placeholder_number(),
                'MARKS_RANGE' => placeholder_number(),
                'PERCENTAGE_RANGE' => placeholder_number(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__quiz_results()
    {
        $given_answers_arr = array();
        foreach (array(true, false, null) as $was_correct) {
            $given_answers_arr[] = array(
                'QUESTION' => lorem_phrase(),
                'GIVEN_ANSWER' => lorem_phrase(),
                'WAS_CORRECT' => $was_correct,
                'CORRECT_ANSWER' => lorem_phrase(),
                'CORRECT_EXPLANATION' => lorem_paragraph_html(),
            );
        }

        return array(
            lorem_globalise(do_lorem_template('QUIZ_RESULTS', array(
                'GIVEN_ANSWERS_ARR' => $given_answers_arr,
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__quiz_archive_screen()
    {
        $content_tests = new Tempcode();
        $content_competitions = new Tempcode();
        $content_surveys = new Tempcode();
        foreach (placeholder_array() as $k => $v) {
            $link = do_lorem_template('QUIZ_BOX', array(
                'TYPE' => lorem_word(),
                'DATE' => placeholder_date(),
                'URL' => placeholder_url(),
                'NAME' => lorem_phrase(),
                'START_TEXT' => lorem_phrase(),
                'TIMEOUT' => placeholder_number(),
                'REDO_TIME' => placeholder_number(),
                '_TYPE' => lorem_word(),
                'POINTS' => placeholder_id(),
                'GIVE_CONTEXT' => true,
            ));
        }
        $content_surveys->attach($link);
        $content_tests->attach($link);
        $content_competitions->attach($link);

        return array(
            lorem_globalise(do_lorem_template('QUIZ_ARCHIVE_SCREEN', array(
                'TITLE' => lorem_title(),
                'CONTENT_SURVEYS' => $content_surveys,
                'CONTENT_COMPETITIONS' => $content_competitions,
                'CONTENT_TESTS' => $content_tests,
                'PAGINATION' => placeholder_pagination(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__quiz_screen()
    {
        //This is for getting the $cms.doAjaxRequest() javascript function.
        require_javascript('ajax');

        $warning_details = do_lorem_template('WARNING_BOX', array(
            'WARNING' => lorem_phrase(),
        ));

        return array(
            lorem_globalise(do_lorem_template('QUIZ_SCREEN', array(
                'TAGS' => lorem_word_html(),
                'ID' => placeholder_id(),
                'WARNING_DETAILS' => $warning_details,
                'URL' => placeholder_url(),
                'TITLE' => lorem_title(),
                'START_TEXT' => lorem_sentence_html(),
                'FIELDS' => placeholder_fields(),
                'TIMEOUT' => '5',
                'EDIT_URL' => placeholder_url(),
                'ALL_REQUIRED' => false,
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__quiz_survey_answers_mail()
    {
        $given_answers = new Tempcode();
        foreach (placeholder_array() as $k => $v) {
            $given_answers->attach(lorem_phrase());
        }

        $given_answers_arr = array();
        foreach (array(true, false, null) as $was_correct) {
            $given_answers_arr[] = array(
                'QUESTION' => lorem_phrase(),
                'GIVEN_ANSWER' => lorem_phrase(),
                'WAS_CORRECT' => $was_correct,
                'CORRECT_ANSWER' => lorem_phrase(),
                'CORRECT_EXPLANATION' => lorem_paragraph_html(),
            );
        }

        return array(
            lorem_globalise(do_lorem_template('QUIZ_SURVEY_ANSWERS_MAIL', array(
                'ENTRY_ID' => placeholder_id(),
                'QUIZ_NAME' => lorem_phrase(),
                'GIVEN_ANSWERS_ARR' => $given_answers_arr,
                'GIVEN_ANSWERS' => $given_answers,
                'MEMBER_PROFILE_URL' => placeholder_url(),
                'USERNAME' => lorem_phrase(),
                'FORUM_DRIVER' => null,
            ), null, false, null, '.txt', 'text'), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__quiz_test_answers_mail()
    {
        $unknowns = new Tempcode();
        foreach (placeholder_array() as $k => $v) {
            $unknowns->attach(lorem_phrase());
        }

        $corrections = new Tempcode();
        foreach (placeholder_array() as $k => $v) {
            $corrections->attach(lorem_phrase());
        }

        $given_answers = new Tempcode();
        foreach (placeholder_array() as $k => $v) {
            $given_answers->attach(lorem_phrase());
        }

        $given_answers_arr = array();
        foreach (array(true, false, null) as $was_correct) {
            $given_answers_arr[] = array(
                'QUESTION' => lorem_phrase(),
                'GIVEN_ANSWER' => lorem_phrase(),
                'WAS_CORRECT' => $was_correct,
                'CORRECT_ANSWER' => lorem_phrase(),
                'CORRECT_EXPLANATION' => lorem_paragraph_html(),
            );
        }

        return array(
            lorem_globalise(do_lorem_template('QUIZ_TEST_ANSWERS_MAIL', array(
                'ENTRY_ID' => placeholder_id(),
                'QUIZ_NAME' => lorem_phrase(),
                'GIVEN_ANSWERS_ARR' => $given_answers_arr,
                'GIVEN_ANSWERS' => $given_answers,
                'UNKNOWNS' => $unknowns,
                'CORRECTIONS' => $corrections,
                'RESULT' => lorem_phrase(),
                'USERNAME' => lorem_phrase(),
            ), null, false, null, '.txt', 'text'), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__quiz_done_screen()
    {
        $given_answers_arr = array();
        foreach (array(true, false, null) as $was_correct) {
            $given_answers_arr[] = array(
                'QUESTION' => lorem_phrase(),
                'GIVEN_ANSWER' => lorem_phrase(),
                'WAS_CORRECT' => $was_correct,
                'CORRECT_ANSWER' => lorem_phrase(),
                'CORRECT_EXPLANATION' => lorem_paragraph_html(),
            );
        }

        return array(
            lorem_globalise(do_lorem_template('QUIZ_DONE_SCREEN', array(
                'TITLE' => lorem_title(),
                'ENTRY_ID' => placeholder_id(),
                'QUIZ_NAME' => lorem_phrase(),
                'GIVEN_ANSWERS_ARR' => $given_answers_arr,
                'CORRECTIONS' => lorem_phrase(),
                'PASSED' => true,
                'POINTS_DIFFERENCE' => placeholder_number(),
                'RESULT' => lorem_phrase(),
                'TYPE' => do_lang('SURVEY'),
                '_TYPE' => 'SURVEY',
                'MESSAGE' => lorem_phrase(),
                'REVEAL_ANSWERS' => true,
                'MARKS' => placeholder_number(),
                'POTENTIAL_EXTRA_MARKS' => placeholder_number(),
                'OUT_OF' => placeholder_number(),
                'MARKS_RANGE' => placeholder_number(),
                'PERCENTAGE_RANGE' => placeholder_number(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__member_quiz_entries()
    {
        $categories = array();
        $categories[do_lang('OTHER')] = array(
            'QUIZZES' => array(),
            'RUNNING_MARKS' => placeholder_number(),
            'RUNNING_OUT_OF' => placeholder_number(),
            'RUNNING_PERCENTAGE' => placeholder_number(),
            'RUNNING_MARKS__CREDIT' => placeholder_number(),
            'RUNNING_OUT_OF__CREDIT' => placeholder_number(),
            'RUNNING_PERCENTAGE__CREDIT' => placeholder_number(),
        );
        $categories[do_lang('OTHER')]['QUIZZES'][] = array(
            'QUIZ_NAME' => lorem_phrase(),
            'QUIZ_START_TEXT' => lorem_paragraph_html(),
            'QUIZ_ID' => placeholder_id(),
            'QUIZ_URL' => placeholder_url(),
            'ENTRY_ID' => placeholder_id(),
            'ENTRY_DATE' => placeholder_date(),
            '_ENTRY_DATE' => placeholder_date_raw(),
            'OUT_OF' => placeholder_number(),
            'MARKS_RANGE' => placeholder_number(),
            'PERCENTAGE_RANGE' => placeholder_number(),
            'PASSED' => true,
            'POINTS' => placeholder_number(),
        );

        return array(
            lorem_globalise(do_lorem_template('MEMBER_QUIZ_ENTRIES', array(
                'CATEGORIES' => $categories,
                'MEMBER_ID' => placeholder_id(),
                'SORTING' => placeholder_table(),
                'DELETE_URL' => placeholder_url(),
            )), null, '', true)
        );
    }
}
