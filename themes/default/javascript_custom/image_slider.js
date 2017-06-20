(function ($cms) {
    'use strict';

    $cms.templates.blockMainImageSlider = function blockMainImageSlider(params) {
        window.jQuery('#skitter_' + params.rand).skitter({
            auto_play: true,
            controls: true,
            dots: true,
            enable_navigation_keys: true,
            interval: params.mill,
            numbers_align: 'center',
            preview: true,
            progressbar: false,
            theme: 'clean',
            thumbs: false
        });
    };
}(window.$cms));
