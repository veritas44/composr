<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2017

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core_rich_media
 */

/**
 * Hook class.
 */
class Hook_config_oembed_manual_patterns
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array The details (null: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'OEMBED_MANUAL_PATTERNS',
            'type' => 'text',
            'category' => 'FEATURE',
            'group' => 'MEDIA',
            'explanation' => 'CONFIG_OPTION_oembed_manual_patterns',
            'shared_hosting_restricted' => '0',
            'list_options' => '',
            'order_in_category_group' => 5,
            'required' => false,

            'addon' => 'core_rich_media',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string The default value (null: option is disabled)
     */
    public function get_default()
    {
        $default = '';

        $default .= "(https?://(www\.)?youtube\.com/watch\?v=.*|https?://youtu\.be/\..*) = http://www.youtube.com/oembed\n";
        $default .= "(https?://vimeo\.com/\d+) = http://vimeo.com/api/oembed.{format}\n";
        $default .= "(https?://(www\.)?dailymotion\.com/video/.*|https?://dai\.ly/.*) = http://www.dailymotion.com/services/oembed\n";
        $default .= "(https?://www\.slideshare\.net/.*/.*) = http://www.slideshare.net/api/oembed/2\n";
        $default .= "(https?://(www\.)?scribd\.com/doc/.*) = http://www.scribd.com/services/oembed\n";
        $default .= "(https?://.*\.flickr\.com/photos/.*|https?://flic\.kr/p/.*) = http://www.flickr.com/services/oembed\n";
        $default .= "(https?://instagram\.com/p/.*) = http://api.instagram.com/oembed\n";
        $default .= "(http?://i.*\.photobucket\.com/albums/.*|https?://gi.*\.photobucket\.com/groups/.*) = http://photobucket.com/oembed\n"; // NB: With this one you need to be really careful to get the *image* URL. Page URLs do not work, it's confusing!
        //$default .= "(https?://twitter\.yfrog\.com/.*) = http://www.yfrog.com/api/oembed\n"; Does not work
        $default .= "(https?://soundcloud\.com/.*/.*) = http://soundcloud.com/oembed\n";
        $default .= "(https?://twitter\.com/.*/status/\d+) = https://api.twitter.com/1/statuses/oembed.{format}\n";
        $default .= "(https?://(www\.)?ustream\.tv/.*) = http://www.ustream.tv/oembed\n";

        $default .= "(https?://twitpic\.com/.*) = http://noembed.com/embed\n";
        /*
        These are not very good - we may as well just do Open Graph on them ourselves rather than relying on noembed
        $default .= "(https?://www\.amazon\.com/.*) = http://noembed.com/embed\n";
        */

        $default .= "(https?://www\.imdb\.com/title/.*|http://twitpic\.com/.*) = http://api.embed.ly/1/oembed\n";
        $default .= "(https?://\w+\.wiki(pedia|media)\.org/wiki/.*) = http://api.embed.ly/1/oembed\n";
        $default .= "(https?://edition\.cnn\.com/.*) = http://api.embed.ly/1/oembed\n";
        $default .= "(https?://maps\.google\.(co\.uk|com)/.*) = http://api.embed.ly/1/oembed\n";
        $default .= "(https?://www\.google\.(co\.uk|com)/maps/.*) = http://api.embed.ly/1/oembed\n";
        $default .= "(https?://xkcd\.com/\d+/?) = http://api.embed.ly/1/oembed\n";
        $default .= "(https?://imgur\.com/.*) = http://api.embed.ly/1/oembed\n";
        $default .= "(https?://tinypic.com/.*) = http://api.embed.ly/1/oembed\n";
        $default .= "(https?://www\.justin\.tv/.*) = http://api.embed.ly/1/oembed\n";
        /*
        Embedly requires an API key for high usage. It's pretty good though. To use an API key, do e.g.:
        $default .= "(http://www\.imdb\.com/title/.*|http://twitpic\.com/.*) = http://api.embed.ly/1/oembed?key=xxxxxx\n";
        */

        // Facebook may give "Security Check Required" when trying to auto-detect, so hard-code
        $default .= "(https?://(www\.)?facebook\.com/.*) = https://www.facebook.com/plugins/page/oembed.json/\n";

        return $default;

        // NB: To put everything through...
        //  embed.ly, you would do ".* = http://api.embed.ly/1/oembed"
        //  Noembed, you would do ".* = http://noembed.com/embed"
        // iframely is interesting. It is self-hosted.
        // Largely though, Composr contains equivalent features to these products.
    }
}
