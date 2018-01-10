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
 * Hook class.
 */
class Hook_spam_heuristics_header_absence
{
    /**
     * Find the confidence score for a particular spam heuristic as applied to the current context.
     *
     * @param  string $post_data Confidence score
     * @return integer Confidence score
     */
    public function assess_confidence($post_data)
    {
        $score = intval(get_option('spam_heuristic_confidence_header_absence'));
        if ($score == 0) {
            return 0;
        }

        $headers = array(
            'HTTP_ACCEPT',
            'HTTP_ACCEPT_ENCODING',
            'HTTP_ACCEPT_LANGUAGE',
            'HTTP_USER_AGENT',
            'HTTP_COOKIE',
            'HTTP_CONNECTION',
        );
        foreach ($headers as $header) {
            if (empty($_SERVER[$header])) {
                return $score;
            }
        }

        return 0;
    }
}
