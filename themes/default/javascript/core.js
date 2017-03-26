(function ($cms) {
    'use strict';

    window.$cmsReady.push(function () {
        $cms.attachBehaviors(document);
    });

    $cms.defineBehaviors(/** @lends $cms.behaviors */{
        // Implementation for [data-require-javascript="[<scripts>...]"]
        initializeRequireJavascript: {
            priority: 10000,
            attach: function (context) {
                var promises = [];

                $cms.dom.$$$(context, '[data-require-javascript]').forEach(function (el) {
                    var scripts = arrVal($cms.dom.data(el, 'requireJavascript'));

                    if (scripts.length) {
                        promises.push($cms.requireJavascript(scripts));
                    }
                });

                return Promise.all(promises);
            }
        },

        // Implementation for [data-view]
        initializeViews: {
            attach: function (context) {
                $cms.dom.$$$(context, '[data-view]').forEach(function (el) {
                    var params = objVal($cms.dom.data(el, 'viewParams')),
                        view, viewOptions = { el: el };

                    try {
                        view = new $cms.views[el.dataset.view](params, viewOptions);
                        $cms.viewInstances[$cms.uid(view)] = view;
                    } catch (ex) {
                        $cms.error('$cms.behaviors.initializeViews.attach(): Exception thrown while initializing view "' + el.dataset.view + '" for', el, ex);
                    }
                });
            }
        },

        // Implementation for [data-tpl]
        initializeTemplates: {
            attach: function (context) {
                $cms.dom.$$$(context, '[data-tpl]').forEach(function (el) {
                    var template = el.dataset.tpl,
                        params = objVal($cms.dom.data(el, 'tplParams'));

                    try {
                        $cms.templates[template].call(el, params, el);
                    } catch (ex) {
                        $cms.error('$cms.behaviors.initializeTemplates.attach(): Exception thrown while calling the template function "' + template + '" for', el, ex);
                    }
                });
            }
        },

        initializeAnchors: {
            attach: function (context) {
                var anchors = $cms.dom.$$$(context, 'a'),
                    hasBaseEl = !!document.querySelector('base');

                anchors.forEach(function (anchor) {
                    var href = anchor.getAttribute('href') || '';
                    // So we can change base tag especially when on debug mode
                    if (hasBaseEl && href.startsWith('#') && (href !== '#!')) {
                        anchor.setAttribute('href', window.location.href.replace(/#.*$/, '') + href);
                    }

                    if ($cms.$CONFIG_OPTION.js_overlays) {
                        // Lightboxes
                        if (anchor.rel && anchor.rel.includes('lightbox')) {
                            anchor.title = anchor.title.replace('{!LINK_NEW_WINDOW;^}', '').trim();
                        }

                        // Convert <a> title attributes into composr tooltips
                        if (!anchor.classList.contains('no_tooltip')) {
                            convert_tooltip(anchor);
                        }
                    }

                    if ($cms.$VALUE_OPTION.js_keep_params) {
                        // Keep parameters need propagating
                        if (anchor.href && anchor.href.startsWith($cms.$BASE_URL_S)) {
                            anchor.href += keep_stub_with_context(anchor.href);
                        }
                    }
                });
            }
        },

        initializeForms: {
            attach: function (context) {
                var forms = $cms.dom.$$$(context, 'form');

                forms.forEach(function (form) {
                    // HTML editor
                    if (window.load_html_edit !== undefined) {
                        load_html_edit(form);
                    }

                    // Remove tooltips from forms as they are for screenreader accessibility only
                    form.title = '';

                    // Convert form element title attributes into composr tooltips
                    if ($cms.$CONFIG_OPTION.js_overlays) {
                        // Convert title attributes into composr tooltips
                        var elements = form.elements, j;

                        for (j = 0; j < elements.length; j++) {
                            if ((elements[j].title !== undefined) && (elements[j]['original-title'] === undefined/*check tipsy not used*/) && !elements[j].classList.contains('no_tooltip')) {
                                convert_tooltip(elements[j]);
                            }
                        }

                        elements = form.querySelectorAll('input[type="image"][title]'); // JS DOM does not include type="image" ones in form.elements
                        for (j = 0; j < elements.length; j++) {
                            if ((elements[j]['original-title'] === undefined/*check tipsy not used*/) && !elements[j].classList.contains('no_tooltip')) {
                                convert_tooltip(elements[j]);
                            }
                        }
                    }

                    if ($cms.$VALUE_OPTION.js_keep_params) {
                        /* Keep parameters need propagating */
                        if (form.action && form.action.startsWith($cms.$BASE_URL_S)) {
                            form.action += keep_stub_with_context(form.action);
                        }
                    }

                    // This "proves" that JS is running, which is an anti-spam heuristic (bots rarely have working JS)
                    if (typeof form.elements['csrf_token'] != 'undefined' && typeof form.elements['js_token'] == 'undefined') {
                        var js_token = document.createElement('input');
                        js_token.name = 'js_token';
                        js_token.value = form.elements['csrf_token'].value.split("").reverse().join(""); // Reverse the CSRF token for our JS token
                        js_token.type = 'hidden';
                        form.appendChild(js_token);
                    }
                });
            }
        },

        initializeInputs: {
            attach: function (context) {
                var inputs = $cms.dom.$$$(context, 'input');

                inputs.forEach(function (input) {
                    if (input.type === 'checkbox') {
                        // Implementatioin for input[data-cms-unchecked-is-indeterminate]
                        if (input.dataset.cmsUncheckedIsIndeterminate != null) {
                            input.indeterminate = !input.checked;
                        }
                    }
                });
            }
        },

        // Convert img title attributes into composr tooltips
        imageTooltips: {
            attach: function (context) {
                if (!$cms.$CONFIG_OPTION.js_overlays) {
                    return;
                }

                $cms.dom.$$$(context, 'img:not([data-cms-rich-tooltip])').forEach(function (img) {
                    convert_tooltip(img);
                });
            }
        },

        // Implementation for [data-cms-select2]
        select2Plugin: {
            attach: function (context) {
                if (!window.jQuery || !window.jQuery.fn.select2) {
                    return;
                }

                var els = $cms.dom.$$$(context, '[data-cms-select2]');

                // Select2 plugin hook
                els.forEach(function (el) {
                    var options = objVal($cms.dom.data(el, 'cmsSelect2'));
                    window.jQuery(el).select2(options);
                });
            }
        },

        // Implementation for img[data-gd-text]
        gdTextImages: {
            attach: function (context) {
                var els = $cms.dom.$$$(context, 'img[data-gd-text]');

                els.forEach(function (img) {
                    gdImageTransform(img);
                });
            }
        },

        // Implementation for [data-toggleable-tray]
        toggleableTray: {
            attach: function (context) {
                var els = $cms.dom.$$$(context, '[data-toggleable-tray]');

                els.forEach(function (el) {
                    var options = objVal($cms.dom.data(el, 'toggleableTray')),
                        tray = new $cms.views.ToggleableTray(options, { el: el });
                });
            }
        }
    });

    function keep_stub_with_context(context) {
        context || (context = '');

        var starting = !context || !context.includes('?');

        var to_add = '', i,
            bits = (window.location.search || '?').substr(1).split('&'),
            gapSymbol;

        for (i = 0; i < bits.length; i++) {
            if (bits[i].startsWith('keep_')) {
                if (!context || (!context.includes('?' + bits[i]) && !context.includes('&' + bits[i]))) {
                    gapSymbol = ((to_add === '') && starting) ? '?' : '&';
                    to_add += gapSymbol + bits[i];
                }
            }
        }

        return to_add;
    }

    /**
     * @memberof $cms.views
     * @class
     * @extends $cms.View
     * */
    $cms.views.Global = function Global() {
        Global.base(this, 'constructor', arguments);

        if ($cms.$CONFIG_OPTION.detect_javascript) {
            this.detectJavascript();
        }

        if ($cms.dom.$('#global_messages_2')) {
            var m1 = $cms.dom.$('#global_messages');
            if (!m1) {
                return;
            }
            var m2 = $cms.dom.$('#global_messages_2');
            $cms.dom.appendHtml(m1, $cms.dom.html(m2));
            m2.parentNode.removeChild(m2);
        }

        if ($cms.usp.get('wide_print') && ($cms.usp.get('wide_print') !== '0')) {
            try {
                window.print();
            } catch (ignore) {}
        }

        if (($cms.$ZONE === 'adminzone') && $cms.$CONFIG_OPTION.background_template_compilation) {
            var page = $cms.filter.url($cms.$PAGE);
            $cms.loadSnippet('background_template_compilation&page=' + page, '', function () {
            });
        }

        if (((window === window.top) && !window.opener) || (window.name === '')) {
            window.name = '_site_opener';
        }

        // Are we dealing with a touch device?
        if ($cms.isTouchEnabled) {
            document.body.classList.add('touch_enabled');
        }

        if ($cms.$HAS_PRIVILEGE.sees_javascript_error_alerts) {
            this.initialiseErrorMechanism();
        }

        // Dynamic images need preloading
        var preloader = new Image();
        preloader.src = $cms.img('{$IMG;,loading}');

        // Tell the server we have JavaScript, so do not degrade things for reasons of compatibility - plus also set other things the server would like to know
        if ($cms.$CONFIG_OPTION.detect_javascript) {
            set_cookie('js_on', 1, 120);
        }

        if ($cms.$CONFIG_OPTION.is_on_timezone_detection) {
            if (!window.parent || (window.parent === window)) {
                set_cookie('client_time', (new Date()).toString(), 120);
                set_cookie('client_time_ref', $cms.$FROM_TIMESTAMP, 120);
            }
        }

        // Mouse/keyboard listening
        window.mouse_x = 0;
        window.mouse_y = 0;

        this.stuckNavs();

        // If back button pressed back from an AJAX-generated page variant we need to refresh page because we aren't doing full JS state management
        window.onpopstate = function () {
            window.setTimeout(function () {
                if (!window.location.hash && window.has_js_state) {
                    window.location.reload();
                }
            });
        };

        // Monitor pasting, for anti-spam reasons
        window.addEventListener('paste', function (event) {
            var clipboard_data = event.clipboardData || window.clipboardData;
            var pasted_data = clipboard_data.getData('Text');
            if (pasted_data && pasted_data.length > $cms.$CONFIG_OPTION.spam_heuristic_pasting) {
                $cms.setPostDataFlag('paste');
            }
        });

        window.page_loaded = true;

        var view = this;
        /* Tidying up after the page is rendered */
        window.$cmsLoad.push(function () {
            // When images etc have loaded
            // Move the help panel if needed
            if ($cms.$CONFIG_OPTION.fixed_width || (get_window_width() > 990)) {
                return;
            }

            var panel_right = view.$('#panel_right');
            if (!panel_right) {
                return;
            }

            var helperPanel = panel_right.querySelector('.global_helper_panel');
            if (!helperPanel) {
                return;
            }

            var middle = panel_right.parentNode.querySelector('.global_middle');
            if (!middle) {
                return;
            }

            middle.style.marginRight = '0';
            var boxes = panel_right.querySelectorAll('.standardbox_curved'), i;
            for (i = 0; i < boxes.length; i++) {
                boxes[i].style.width = 'auto';
            }
            panel_right.classList.add('horiz_helper_panel');
            panel_right.parentNode.removeChild(panel_right);
            middle.parentNode.appendChild(panel_right);
            $cms.dom.$('#helper_panel_toggle').style.display = 'none';
            helperPanel.style.minHeight = '0';
        });

        if ($cms.$IS_STAFF) {
            this.loadStuffStaff()
        }
    };

    $cms.inherits($cms.views.Global, $cms.View, /**@lends $cms.views.Global#*/{
        events: function () {
            return {
                // Show a confirmation dialog for clicks on a link (is higher up for priority)
                'click [data-cms-confirm-click]': 'confirmClick',

                'click [data-click-eval]': 'clickEval',

                'click [data-click-alert]': 'showModalAlert',
                'click [data-keypress-alert]': 'showModalAlert',

                // Prevent url change for clicks on anchor tags with a placeholder href
                'click a[href$="#!"]': 'preventDefault',
                // Prevent form submission for forms with a placeholder action
                'submit form[action$="#!"]': 'preventDefault',
                // Prevent-default for JS-activated elements (which may have noscript fallbacks as default actions)
                'submit [data-click-pd]': 'clickPreventDefault',
                'submit [data-submit-pd]': 'submitPreventDefault',

                // Simulated href for non <a> elements
                'click [data-cms-href]': 'cmsHref',

                'click [data-click-forward]': 'clickForward',

                // Toggle classes on mouseover/out
                'mouseover [data-mouseover-class]': 'mouseoverClass',
                'mouseout [data-mouseout-class]': 'mouseoutClass',

                // Disable button after click
                'click [data-disable-on-click]': 'disableButton',

                // Submit form when the change event is fired on an input element
                'change [data-change-submit-form]': 'changeSubmitForm',

                // Disable form buttons
                'submit form[data-disable-buttons-on-submit]': 'disableFormButtons',

                // mod_security workaround
                'submit form[data-submit-modsecurity-workaround]': 'submitModsecurityWorkaround',

                // Prevents input of matching characters
                'input input[data-cms-invalid-pattern]': 'invalidPattern',
                'keydown input[data-cms-invalid-pattern]': 'invalidPattern',
                'keypress input[data-cms-invalid-pattern]': 'invalidPattern',

                'change textarea[data-textarea-auto-height]': 'textareaAutoHeight',
                'keyup textarea[data-textarea-auto-height]': 'textareaAutoHeight',

                // Open page in overlay
                'click [data-open-as-overlay]': 'openOverlay',

                'click [data-click-faux-open]': 'clickFauxOpen',

                // Lightboxes
                'click a[rel*="lightbox"]': 'lightBoxes',

                // Go back in browser history
                'click [data-cms-btn-go-back]': 'goBackInHistory',

                'mouseover [data-mouseover-activate-tooltip]': 'mouseoverActivateTooltip',
                'focus [data-focus-activate-tooltip]': 'focusActivateTooltip',

                'blur [data-blur-deactivate-tooltip]': 'blurDeactivateTooltip',

                // "Rich semantic tooltips"
                'click [data-cms-rich-tooltip]': 'activateRichTooltip',
                'mouseover [data-cms-rich-tooltip]': 'activateRichTooltip',
                'keypress [data-cms-rich-tooltip]': 'activateRichTooltip',

                'change input[data-cms-unchecked-is-indeterminate]': 'uncheckedIsIndeterminate',

                'click [data-click-ga-track]': 'gaTrackClick',

                // Toggle tray
                'click [data-click-tray-toggle]': 'clickTrayToggle',
                'click [data-click-tray-accordion-toggle]': 'clickTrayAccordionToggle',

                /* Footer links */
                'click .js-click-load-software-chat': 'loadSoftwareChat',

                'submit .js-submit-staff-actions-select': 'staffActionsSelect',

                'keypress .js-input-su-keypress-enter-submit-form': 'inputSuKeypress',

                'click .js-global-click-load-realtime-rain': 'loadRealtimeRain',

                'click .js-global-click-load-commandr': 'loadCommandr'

            };
        },

        stuckNavs: function () {
            // Pinning to top if scroll out
            var stuck_navs = $cms.dom.$$('.stuck_nav');

            if (!stuck_navs.length) {
                return;
            }

            $cms.dom.on(window, 'scroll', function () {
                for (var i = 0; i < stuck_navs.length; i++) {
                    var stuck_nav = stuck_navs[i],
                        stuck_nav_height = (stuck_nav.real_height === undefined) ? $cms.dom.contentHeight(stuck_nav) : stuck_nav.real_height;

                    stuck_nav.real_height = stuck_nav_height;
                    var pos_y = find_pos_y(stuck_nav.parentNode, true),
                        footer_height = document.querySelector('footer').offsetHeight,
                        panel_bottom = $cms.dom.$id('panel_bottom');

                    if (panel_bottom) {
                        footer_height += panel_bottom.offsetHeight;
                    }
                    panel_bottom = $cms.dom.$id('global_messages_2');
                    if (panel_bottom) {
                        footer_height += panel_bottom.offsetHeight;
                    }
                    if (stuck_nav_height < get_window_height() - footer_height) {// If there's space in the window to make it "float" between header/footer
                        var extra_height = (window.pageYOffset - pos_y);
                        if (extra_height > 0) {
                            var width = $cms.dom.contentWidth(stuck_nav);
                            var height = $cms.dom.contentHeight(stuck_nav);
                            var stuck_nav_width = $cms.dom.contentWidth(stuck_nav);
                            if (!window.getComputedStyle(stuck_nav).getPropertyValue('width')) {// May be centered or something, we should be careful
                                stuck_nav.parentNode.style.width = width + 'px';
                            }
                            stuck_nav.parentNode.style.height = height + 'px';
                            stuck_nav.style.position = 'fixed';
                            stuck_nav.style.top = '0px';
                            stuck_nav.style.zIndex = '1000';
                            stuck_nav.style.width = stuck_nav_width + 'px';
                        } else {
                            stuck_nav.parentNode.style.width = '';
                            stuck_nav.parentNode.style.height = '';
                            stuck_nav.style.position = '';
                            stuck_nav.style.top = '';
                            stuck_nav.style.width = '';
                        }
                    } else {
                        stuck_nav.parentNode.style.width = '';
                        stuck_nav.parentNode.style.height = '';
                        stuck_nav.style.position = '';
                        stuck_nav.style.top = '';
                        stuck_nav.style.width = '';
                    }
                }
            });
        },

        // Implementation for [data-cms-confirm-click="<Message>"]
        confirmClick: function (e, clicked) {
            var view = this, message,
                uid = $cms.uid(clicked);

            // Stores an element's `uid`
            this._confirmedClick || (this._confirmedClick = null);

            if (uid === this._confirmedClick) {
                // Confirmed, let it through
                this._confirmedClick = null;
                return;
            }

            e.preventDefault();
            message = clicked.dataset.cmsConfirmClick;
            $cms.ui.confirm(message, function (result) {
                if (result) {
                    view._confirmedClick = uid;
                    clicked.click();
                }
            });
        },

        // Implementation for [data-click-eval="<code to eval>"]
        clickEval: function (e, target) {
            var code = strVal(target.dataset.clickEval);

            if (code) {
                window.eval.call(target, code);
            }
        },

        // Implementation for [data-click-alert] and [data-keypress-alert]
        showModalAlert: function (e, target) {
            var options = objVal($cms.dom.data(target, e.type + 'Alert'), 'notice');
            $cms.ui.alert(options.notice);
        },

        preventDefault: function (e) {
            e.preventDefault();
        },

        // Implementation for [data-click-pd]
        clickPreventDefault: function (e, el) {
            if (el.dataset.clickPd !== '0') {
                e.preventDefault();
            }
        },

        // Implementation for [data-submit-pd]
        submitPreventDefault: function (e, form) {
            if (form.dataset.submitPd !== '0') {
                e.preventDefault();
            }
        },

        // Implementation for [data-cms-href="<URL>"]
        cmsHref: function (e, el) {
            var anchorClicked = !!$cms.dom.closest(e.target, 'a', el);

            // Make sure a child <a> element wasn't clicked and default wasn't prevented
            if (!anchorClicked && !e.defaultPrevented) {
                $cms.navigate(el);
            }
        },

        // Implementation for [data-click-forward="{ child: '.some-selector' }"]
        clickForward: function (e, el) {
            var options = objVal($cms.dom.data(el, 'clickForward'), 'child'),
                child = strVal(options.child), // Selector for target child element
                except = strVal(options.except), // Optional selector for excluded elements to let pass-through
                childEl = $cms.dom.$(el, child);

            if (!childEl) {
                // Nothing to do
                return;
            }

            if (!childEl.contains(e.target) && (!except || !$cms.dom.closest(e.target, except, el.parentElement))) {
                // ^ Make sure the child isn't the current event's target already, and check for excluded elements to let pass-through
                e.preventDefault();
                $cms.dom.trigger(childEl, 'click');
            }
        },

        // Implementation for [data-mouseover-class="{ 'some-class' : 1|0 }"]
        mouseoverClass: function (e, target) {
            var classes = objVal($cms.dom.data(target, 'mouseoverClass')), key, bool;

            if (!e.relatedTarget || !target.contains(e.relatedTarget)) {
                for (key in classes) {
                    bool = !!classes[key] && (classes[key] !== '0');
                    target.classList.toggle(key, bool);
                }
            }
        },

        // Implementation for [data-mouseout-class="{ 'some-class' : 1|0 }"]
        mouseoutClass: function (e, target) {
            var classes = objVal($cms.dom.data(target, 'mouseoutClass')), key, bool;

            if (!e.relatedTarget || !target.contains(e.relatedTarget)) {
                for (key in classes) {
                    bool = !!classes[key] && (classes[key] !== '0');
                    target.classList.toggle(key, bool);
                }
            }
        },

        // Implementation for [data-disable-on-click]
        disableButton: function (e, target) {
            $cms.ui.disableButton(target);
        },

        // Implementation for [data-change-submit-form]
        changeSubmitForm: function (e, input) {
            if (input.form != null) {
                input.form.submit();
            }
        },

        // Implementation for form[data-disable-buttons-on-submit]
        disableFormButtons: function (e, target) {
            $cms.ui.disableFormButtons(target);
        },

        // Implementation for form[data-submit-modsecurity-workaround]
        submitModsecurityWorkaround: function (e, form) {
            e.preventDefault();
            $cms.form.modsecurityWorkaround(form);
        },

        // Implementation for input[data-cms-invalid-pattern]
        invalidPattern: function (e, input) {
            var pattern = input.dataset.cmsInvalidPattern, regex;

            this._invalidPatternCache || (this._invalidPatternCache = {});

            regex = this._invalidPatternCache[pattern] || (this._invalidPatternCache[pattern] = new RegExp(pattern, 'g'));

            if (e.type === 'input') {
                if (input.value.length === 0) {
                    input.value = ''; // value.length is also 0 if invalid value is entered for input[type=number] et al., clear that
                } else if (regex.test(input.value)) {
                    input.value = input.value.replace(regex, '');
                }
            } else if ($cms.dom.keyOutput(e, regex)) { // keydown/keypress event
                // pattern matched, prevent input
                e.preventDefault();
            }
        },

        // Implementation for textarea[data-textarea-auto-height]
        textareaAutoHeight: function (e, textarea) {
            if ($cms.$MOBILE) {
                return;
            }

            manage_scroll_height(textarea);
        },

        // Implementation for [data-open-as-overlay]
        openOverlay: function (e, el) {
            var options, url = (el.href === undefined) ? el.action : el.href;

            if (!($cms.$CONFIG_OPTION.js_overlays)) {
                return;
            }

            if (/:\/\/(.[^/]+)/.exec(url)[1] !== window.location.hostname) {
                return; // Cannot overlay, different domain
            }

            e.preventDefault();

            options = objVal($cms.dom.data(el, 'openAsOverlay'));
            options.el = el;

            openLinkAsOverlay(options);
        },

        // Implementation for [data-click-faux-open]
        clickFauxOpen: function (e, el) {
            var args = arrVal($cms.dom.data(el, 'clickFauxOpen'));
            $cms.ui.open.apply(undefined, args);
        },

        // Implementation for `click a[rel*="lightbox"]`
        lightBoxes: function (e, el) {
            if (!($cms.$CONFIG_OPTION.js_overlays)) {
                return;
            }

            e.preventDefault();

            if (el.querySelector('img, video')) {
                openImageIntoLightbox(el);
            } else {
                openLinkAsOverlay({ el: el });
            }

            function openImageIntoLightbox(el) {
                var has_full_button = (el.firstElementChild === null) || (el.href !== el.firstElementChild.src);
                $cms.ui.openImageIntoLightbox(el.href, ((el.cms_tooltip_title !== undefined) ? el.cms_tooltip_title : el.title), null, null, has_full_button);
            }
        },

        goBackInHistory: function () {
            window.history.back();
        },

        // Implementation for [data-mouseover-activate-tooltip]
        mouseoverActivateTooltip: function (e, el) {
            var args = arrVal($cms.dom.data(el, 'mouseoverActivateTooltip'), true);

            args.unshift(el, e);

            try {
                $cms.ui.activateTooltip.apply(undefined, args);
            } catch (ex) {
                $cms.error('$cms.views.Global#mouseoverActivateTooltip(): Exception thrown by $cms.ui.activateTooltip()', ex, 'called with args:', args);
            }
        },

        // Implementation for [data-focus-activate-tooltip]
        focusActivateTooltip: function (e, el) {
            var args = arrVal($cms.dom.data(el, 'focusActivateTooltip'), true);

            args.unshift(el, e);

            try {
                $cms.ui.activateTooltip.apply(undefined, args);
            } catch (ex) {
                $cms.error('$cms.views.Global#focusActivateTooltip(): Exception thrown by $cms.ui.activateTooltip()', ex, 'called with args:', args);
            }
        },

        // Implementation for [data-blur-deactivate-tooltip]
        blurDeactivateTooltip: function (e, el) {
            $cms.ui.deactivateTooltip(el);
        },

        activateRichTooltip: function (e, el) {
            if (el.ttitle === undefined) {
                el.ttitle = el.title;
            }

            var args = [el, e, el.ttitle, 'auto', null, null, false, true, false, false, window, !!el.have_links];

            try {
                $cms.ui.activateTooltip.apply(undefined, args);
            } catch (ex) {
                $cms.error('$cms.views.Global#activateRichTooltip(): Exception thrown by $cms.ui.activateTooltip()', ex, 'called with args:', args);
            }
        },

        // Implementatioin for input[data-cms-unchecked-is-indeterminate]
        uncheckedIsIndeterminate: function (e, input) {
            if (!input.checked) {
                input.indeterminate = true;
            }
        },

        // Implementation for [data-click-ga-track]
        gaTrackClick: function (e, clicked) {
            var options = objVal($cms.dom.data(clicked, 'clickGaTrack'));

            e.preventDefault();
            $cms.gaTrack(clicked, options.category, options.action);
        },

        // Implementation for [data-click-tray-toggle="<TRAY ID>"]
        clickTrayToggle: function (e, clicked) {
            var trayId = strVal(clicked.dataset.clickTrayToggle),
                trayEl = $cms.dom.$('#' + trayId),
                trayCookie;

            if (!trayEl) {
                return
            }

            trayCookie = strVal(trayEl.dataset.trayCookie);

            if (trayCookie) {
                set_cookie('tray_' + trayCookie, $cms.dom.isDisplayed(trayEl) ? 'closed' : 'open');
            }

            $cms.toggleableTray(trayEl);
        },

        // Implementation for [data-click-tray-accordion-toggle]
        clickTrayAccordionToggle: function () {

        },

        // Detecting of JavaScript support
        detectJavascript: function () {
            var url = window.location.href,
                append = '?';

            if ($cms.$JS_ON || $cms.usp.get('keep_has_js') || url.includes('upgrader.php') || url.includes('webdav.php')) {
                return;
            }

            if (window.location.search.length === 0) {
                if (!url.includes('.htm') && !url.includes('.php')) {
                    append = 'index.php?';

                    if (!url.endsWith('/')) {
                        append = '/' + append;
                    }
                }
            } else {
                append = '&';
            }

            append += 'keep_has_js=1';

            if ($cms.$DEV_MODE) {
                append += '&keep_devtest=1';
            }

            // Redirect with JS on, and then hopefully we can remove keep_has_js after one click. This code only happens if JS is marked off, no infinite loops can happen.
            window.location = url + append;
        },

        /* SOFTWARE CHAT */
        loadSoftwareChat: function () {
            var url = 'https://kiwiirc.com/client/irc.kiwiirc.com/?nick=';
            if ($cms.$USERNAME !== 'admin') {
                url += encodeURIComponent($cms.$USERNAME.replace(/[^a-zA-Z0-9\_\-\\\[\]\{\}\^`|]/g, ''));
            } else {
                url += encodeURIComponent($cms.$SITE_NAME.replace(/[^a-zA-Z0-9\_\-\\\[\]\{\}\^`|]/g, ''));
            }
            url += '#composrcms';

            var SOFTWARE_CHAT_EXTRA = '{!SOFTWARE_CHAT_EXTRA;^}'.replace(/\{1\}/, $cms.filter.html(window.location.href.replace($cms.$BASE_URL, 'http://baseurl')));
            var html = '\
    <div class="software_chat">\
        <h2>{!CMS_COMMUNITY_HELP}</h2>\
        <ul class="spaced_list">' + SOFTWARE_CHAT_EXTRA + '</ul>\
        <p class="associated_link associated_links_block_group">\
            <a title="{!SOFTWARE_CHAT_STANDALONE} {!LINK_NEW_WINDOW;^}" target="_blank" href="' + $cms.filter.html(url) + '">{!SOFTWARE_CHAT_STANDALONE}</a>\
            <a href="#!" class="js-click-load-software-chat">{!HIDE}</a>\
        </p>\
    </div>\
    <iframe class="software_chat_iframe" style="border: 0" src="' + $cms.filter.html(url) + '"></iframe>';

            var box = $cms.dom.$('#software_chat_box'), img;
            if (box) {
                box.parentNode.removeChild(box);

                img = $cms.dom.$('#software_chat_img');
                clear_transition_and_set_opacity(img, 1.0);
            } else {
                var width = 950,
                    height = 550;

                box = $cms.dom.create('div', {
                    id: 'software_chat_box',
                    css: {
                        width: width + 'px',
                        height: height + 'px',
                        background: '#EEE',
                        color: '#000',
                        padding: '5px',
                        border: '3px solid #AAA',
                        position: 'absolute',
                        zIndex: 2000,
                        left: (get_window_width() - width) / 2 + 'px',
                        top: 100 + 'px'
                    },
                    html: html
                });

                document.body.appendChild(box);

                smooth_scroll(0);

                img = $cms.dom.$('#software_chat_img');
                clear_transition_and_set_opacity(img, 0.5);
            }
        },

        /* STAFF ACTIONS LINKS */
        staffActionsSelect: function (e, form) {
            var ob = form.elements.special_page_type;

            var val = ob.options[ob.selectedIndex].value;
            if (val !== 'view') {
                if (form.elements.cache !== undefined) {
                    form.elements.cache.value = (val.substring(val.length - 4, val.length) == '.css') ? '1' : '0';
                }

                var window_name = 'cms_dev_tools' + Math.floor(Math.random() * 10000);
                var window_options;
                if (val == 'templates') {
                    window_options = 'width=' + window.screen.availWidth + ',height=' + window.screen.availHeight + ',scrollbars=yes';

                    window.setTimeout(function () { // Do a refresh with magic markers, in a comfortable few seconds
                        var old_url = window.location.href;
                        if (old_url.indexOf('keep_template_magic_markers=1') == -1) {
                            window.location.href = old_url + ((old_url.indexOf('?') == -1) ? '?' : '&') + 'keep_template_magic_markers=1&cache_blocks=0&cache_comcode_pages=0';
                        }
                    }, 10000);
                } else {
                    window_options = 'width=1020,height=700,scrollbars=yes';
                }
                var test = window.open('', window_name, window_options);

                if (test) {
                    form.setAttribute('target', test.name);
                }
            }
        },

        inputSuKeypress: function (e, input) {
            if ($cms.dom.keyPressed(e, 'Enter')) {
                input.form.submit();
            }
        },

        loadRealtimeRain: function () {
            if (window.load_realtime_rain) {
                load_realtime_rain();
            }
        },

        loadCommandr: function () {
            if (window.load_commandr) {
                load_commandr();
            }
        },

        loadStuffStaff: function () {
            var loc = window.location.href;

            // Navigation loading screen
            if ($cms.$CONFIG_OPTION.enable_animations) {
                if ((window.parent === window) && !loc.includes('js_cache=1') && (loc.includes('/cms/') || loc.includes('/adminzone/'))) {
                    window.addEventListener('beforeunload', function () {
                        staff_unload_action();
                    });
                }
            }

            // Theme image editing hovers
            var els = $cms.dom.$$('*:not(.no_theme_img_click)'), i, el, isImage;
            for (i = 0; i < els.length; i++) {
                el = els[i];
                isImage = (el.localName === 'img') || ((el.localName === 'input') && (el.type === 'image')) || $cms.dom.css(el, 'background-image').includes('url');

                if (isImage) {
                    $cms.dom.on(el, {
                        mouseover: handle_image_mouse_over,
                        mouseout: handle_image_mouse_out,
                        click: handle_image_click
                    });
                }
            }

            /* Thumbnail tooltips */
            if ($cms.$DEV_MODE || loc.replace($cms.$BASE_URL_NOHTTP, '').includes('/cms/')) {
                var urlPatterns = $cms.staffTooltipsUrlPatterns,
                    links, pattern, hook, patternRgx;

                links = $cms.dom.$$('td a');
                for (pattern in urlPatterns) {
                    hook = urlPatterns[pattern];
                    patternRgx = new RegExp(pattern);

                    links.forEach(function (link) {
                        if (link.href && !link.onmouseover) {
                            var id = link.href.match(patternRgx);
                            if (id) {
                                apply_comcode_tooltip(hook, id, link);
                            }
                        }
                    });
                }
            }

            /* Screen transition, for staff */
            function staff_unload_action() {
                undo_staff_unload_action();

                // If clicking a download link then don't show the animation
                if (document.activeElement && document.activeElement.href !== undefined && document.activeElement.href != null) {
                    var url = document.activeElement.href.replace(/.*:\/\/[^\/:]+/, '');
                    if (url.includes('download') || url.includes('export')) {
                        return;
                    }
                }

                // If doing a meta refresh then don't show the animation
                if (document.querySelector('meta[http-equiv="Refresh"]')) {
                    return;
                }

                // Show the animation
                var bi = $cms.dom.$id('main_website_inner');
                if (bi) {
                    bi.classList.add('site_unloading');
                    fade_transition(bi, 20, 30, -4);
                }
                var div = document.createElement('div');
                div.className = 'unload_action';
                div.style.width = '100%';
                div.style.top = (get_window_height() / 2 - 160) + 'px';
                div.style.position = 'fixed';
                div.style.zIndex = 10000;
                div.style.textAlign = 'center';
                $cms.dom.html(div, '<div aria-busy="true" class="loading_box box"><h2>{!LOADING;^}</h2><img id="loading_image" alt="" src="{$IMG_INLINE*;,loading}" /></div>');
                window.setTimeout(function () {
                    // Stupid workaround for Google Chrome not loading an image on unload even if in cache
                    if ($cms.dom.$id('loading_image')) {
                        $cms.dom.$id('loading_image').src += '';
                    }
                }, 100);
                document.body.appendChild(div);

                // Allow unloading of the animation
                $cms.dom.on(window, 'pageshow keydown click', undo_staff_unload_action)
            }

            /*
             TOOLTIPS FOR THUMBNAILS TO CONTENT, AS DISPLAYED IN CMS ZONE
             */

            function apply_comcode_tooltip(hook, id, link) {
                link.addEventListener('mouseout', function () {
                    $cms.ui.deactivateTooltip(link);
                });
                link.addEventListener('mousemove', function (event) {
                    $cms.ui.repositionTooltip(link, event, false, false, null, true);
                });
                link.addEventListener('mouseover', function (event) {
                    var id_chopped = id[1];
                    if (id[2] !== undefined) {
                        id_chopped += ':' + id[2];
                    }
                    var comcode = '[block="' + hook + '" id="' + decodeURIComponent(id_chopped) + '" no_links="1"]main_content[/block]';
                    if (link.rendered_tooltip === undefined) {
                        link.is_over = true;

                        do_ajax_request(maintain_theme_in_link('{$FIND_SCRIPT_NOHTTP;,comcode_convert}?css=1&javascript=1&raw_output=1&box_title={!PREVIEW;&}' + keep_stub()), function (ajax_result_frame) {
                            if (ajax_result_frame && ajax_result_frame.responseText) {
                                link.rendered_tooltip = ajax_result_frame.responseText;
                            }
                            if (link.rendered_tooltip !== undefined) {
                                if (link.is_over) {
                                    $cms.ui.activateTooltip(link, event, link.rendered_tooltip, '400px', null, null, false, false, false, true);
                                }
                            }
                        }, 'data=' + encodeURIComponent(comcode));
                    } else {
                        $cms.ui.activateTooltip(link, event, link.rendered_tooltip, '400px', null, null, false, false, false, true);
                    }
                });
            }

            /*
             THEME IMAGE CLICKING
             */
            function handle_image_mouse_over(event) {
                var target = event.target;
                if (target.previousSibling && (target.previousSibling.className !== undefined) && (target.previousSibling.className.indexOf !== undefined) && (target.previousSibling.className.indexOf('magic_image_edit_link') != -1)) {
                    return;
                }
                if (target.offsetWidth < 130) {
                    return;
                }

                var src = (target.src === undefined) ? $cms.dom.css(target, 'background-image') : target.src;

                if ((target.src === undefined) && (!event.ctrlKey) && (!event.metaKey) && (!event.altKey)) {
                    return;  // Needs ctrl key for background images
                }
                if (!src.includes('/themes/') || window.location.href.includes('admin_themes')) {
                    return;
                }

                if ($cms.$CONFIG_OPTION.enable_theme_img_buttons) {
                    // Remove other edit links
                    var old = document.querySelectorAll('.magic_image_edit_link');
                    for (var i = old.length - 1; i >= 0; i--) {
                        old[i].parentNode.removeChild(old[i]);
                    }

                    // Add edit button
                    var ml = document.createElement('input');
                    ml.onclick = function (event) {
                        handle_image_click(event, target, true);
                    };
                    ml.type = 'button';
                    ml.id = 'editimg_' + target.id;
                    ml.value = '{!themes:EDIT_THEME_IMAGE;^}';
                    ml.className = 'magic_image_edit_link button_micro';
                    ml.style.position = 'absolute';
                    ml.style.left = find_pos_x(target) + 'px';
                    ml.style.top = find_pos_y(target) + 'px';
                    ml.style.zIndex = 3000;
                    ml.style.display = 'none';
                    target.parentNode.insertBefore(ml, target);

                    if (target.mo_link)
                        window.clearTimeout(target.mo_link);
                    target.mo_link = window.setTimeout(function () {
                        if (ml) ml.style.display = 'block';
                    }, 2000);
                }

                window.old_status_img = window.status;
                window.status = '{!SPECIAL_CLICK_TO_EDIT;^}';
            }

            function handle_image_mouse_out(event) {
                var target = event.target;

                if ($cms.$CONFIG_OPTION.enable_theme_img_buttons) {
                    if (target.previousSibling && (target.previousSibling.className !== undefined) && (target.previousSibling.className.indexOf !== undefined) && (target.previousSibling.className.indexOf('magic_image_edit_link') != -1)) {
                        if ((target.mo_link !== undefined) && (target.mo_link)) // Clear timed display of new edit button
                        {
                            window.clearTimeout(target.mo_link);
                            target.mo_link = null;
                        }

                        // Time removal of edit button
                        if (target.mo_link)
                            window.clearTimeout(target.mo_link);
                        target.mo_link = window.setTimeout(function () {
                            if ((target.edit_window === undefined) || (!target.edit_window) || (target.edit_window.closed)) {
                                if (target.previousSibling && (target.previousSibling.className !== undefined) && (target.previousSibling.className.indexOf !== undefined) && (target.previousSibling.className.indexOf('magic_image_edit_link') != -1))
                                    target.parentNode.removeChild(target.previousSibling);
                            }
                        }, 3000);
                    }
                }

                if (window.old_status_img === undefined) {
                    window.old_status_img = '';
                }
                window.status = window.old_status_img;
            }

            function handle_image_click(event, ob, force) {
                ob || (ob = this);

                var src = ob.origsrc ? ob.origsrc : ((ob.src === undefined) ? $cms.dom.css(ob, 'background-image').replace(/.*url\(['"]?(.*)['"]?\).*/, '$1') : ob.src);
                if (src && (force || (magic_keypress(event)))) {
                    // Bubbling needs to be stopped because shift+click will open a new window on some lower event handler (in firefox anyway)
                    event.stopPropagation();

                    if (event.preventDefault !== undefined) event.preventDefault();

                    if (src.includes('{$BASE_URL_NOHTTP;^}/themes/')) {
                        ob.edit_window = window.open('{$BASE_URL;,0}/adminzone/index.php?page=admin_themes&type=edit_image&lang=' + encodeURIComponent($cms.$LANG) + '&theme=' + encodeURIComponent($cms.$THEME) + '&url=' + encodeURIComponent(src.replace('{$BASE_URL;,0}/', '')) + keep_stub(), 'edit_theme_image_' + ob.id);
                    } else {
                        $cms.ui.alert('{!NOT_THEME_IMAGE;^}');
                    }

                    return false;
                }

                return true;
            }

        },

        /* Staff JS error display */
        initialiseErrorMechanism: function () {
            window.onerror = function (msg, file, code) {
                if (msg.includes === undefined) {
                    return null;
                }

                if (window.document.readyState !== 'complete') {
                    // Probably not loaded yet
                    return null;
                }

                if (
                    (msg.includes('AJAX_REQUESTS is not defined')) || // Intermittent during page out-clicks
                        // Internet Explorer false positives
                    (((msg.includes("'null' is not an object")) || (msg.includes("'undefined' is not a function"))) && ((file === undefined) || (file === 'undefined'))) || // Weird errors coming from outside
                    (((code === 0) || (code === '0')) && (msg.includes('Script error.'))) || // Too generic, can be caused by user's connection error

                        // Firefox false positives
                    (msg.includes("attempt to run compile-and-go script on a cleared scope")) || // Intermittent buggyness
                    (msg.includes('UnnamedClass.toString')) || // Weirdness
                    (msg.includes('ASSERT: ')) || // Something too generic
                    ((file) && (file.includes('TODO: FIXME'))) || // Something too generic / Can be caused by extensions
                    (msg.includes('TODO: FIXME')) || // Something too generic / Can be caused by extensions
                    (msg.includes('Location.toString')) || // Buggy extensions may generate
                    (msg.includes('Error loading script')) || // User's connection error
                    (msg.includes('NS_ERROR_FAILURE')) || // Usually an internal error

                        // Google Chrome false positives
                    (msg.includes('can only be used in extension processes')) || // Can come up with MeasureIt
                    (msg.includes('extension.')) || // E.g. "Uncaught Error: Invocation of form extension.getURL() doesn't match definition extension.getURL(string path) schema_generated_bindings"

                    false // Just to allow above lines to be reordered
                )
                    return null; // Comes up on due to various Firefox/extension/etc bugs

                if (!window.done_one_error) {
                    window.done_one_error = true;
                    var alert = '{!JAVASCRIPT_ERROR;^}\n\n' + code + ': ' + msg + '\n' + file;
                    if (window.document.body) {// i.e. if loaded
                        $cms.ui.alert(alert, null, '{!ERROR_OCCURRED;^}');
                    }
                }
                return false;
            };

            window.addEventListener('beforeunload', function () {
                window.onerror = null;
            });
        }
    });

    $cms.views.ToggleableTray = ToggleableTray;
    /**
     * @memberof $cms.views
     * @class
     * @extends $cms.View
     */
    function ToggleableTray() {
        ToggleableTray.base(this, 'constructor', arguments);

        this.contentEl = this.$('.toggleable_tray');
        this.trayCookie = strVal(this.el.dataset.trayCookie);

        if (this.trayCookie) {
            this.handleTrayCookie(this.trayCookie);
        }
    }

    $cms.inherits(ToggleableTray, $cms.View, /** @lends $cms.views.ToggleableTray# */{
        /**@method*/
        events: function () {
            return {
                'click .js-btn-tray-toggle': 'toggle',
                'click .js-btn-tray-accordion': 'toggleAccordionItems'
            };
        },

        /**@method*/
        toggle: function () {
            if (this.trayCookie) {
                set_cookie('tray_' + this.trayCookie, $cms.dom.isDisplayed(this.el) ? 'closed' : 'open');
            }

            $cms.toggleableTray(this.el);
        },

        /**@method*/
        accordion: function (el) {
            var nodes = $cms.dom.$$(el.parentNode.parentNode, '.toggleable_tray');

            nodes.forEach(function (node) {
                if ((node.parentNode !== el) && $cms.dom.isDisplayed(node) && node.parentNode.classList.contains('js-tray-accordion-item')) {
                    $cms.toggleableTray(node, true);
                }
            });

            $cms.toggleableTray(el);
        },

        /**@method*/
        toggleAccordionItems: function (e, btn) {
            var accordionItem = $cms.dom.closest(btn, '.js-tray-accordion-item');

            if (accordionItem) {
                this.accordion(accordionItem);
            }
        },

        /**@method*/
        handleTrayCookie: function () {
            var cookieValue = read_cookie('tray_' + this.trayCookie);

            if (($cms.dom.notDisplayed(this.contentEl) && (cookieValue === 'open')) || ($cms.dom.isDisplayed(this.contentEl) && (cookieValue === 'closed'))) {
                $cms.toggleableTray(this.contentEl, true);
            }
        }
    });

    $cms.toggleableTray = toggleableTray;
    // Toggle a ToggleableTray
    function toggleableTray(el, noAnimateHeight) {
        var $IMG_expand = '{$IMG;,1x/trays/expand}',
            $IMG_expand2 = '{$IMG;,1x/trays/expand2}',
            $IMG_contract = '{$IMG;,1x/trays/contract}',
            $IMG_contract2 = '{$IMG;,1x/trays/contract2}';

        if (!el) {
            return;
        }

        //@TODO: Implement slide-up/down animation which is triggered by this boolean
        //noAnimateHeight = $cms.$CONFIG_OPTION.enable_animations ? !!noAnimateHeight : true;

        if (!el.classList.contains('toggleable_tray')) {// Suspicious, maybe we need to probe deeper
            el = $cms.dom.$(el, '.toggleable_tray') || el;
        }

        if (!el) {
            return;
        }

        var pic = $cms.dom.$(el.parentNode, '.toggleable_tray_button img') || $cms.dom.$('img#e_' + el.id);

        el.setAttribute('aria-expanded', 'true');

        if ($cms.dom.notDisplayed(el)) {
            $cms.dom.fadeIn(el);

            if (pic) {
                set_tray_theme_image('expand', 'contract', $IMG_expand, $IMG_contract, $IMG_contract2);
            }
        } else {
            $cms.dom.hide(el);

            if (pic) {
                set_tray_theme_image('contract', 'expand', $IMG_contract, $IMG_expand, $IMG_expand2);
                pic.setAttribute('alt', pic.getAttribute('alt').replace('{!CONTRACT;^}', '{!EXPAND;^}'));
                pic.title = '{!EXPAND;^}';
            }
        }

        trigger_resize(true);

        // Execution ends here

        var isThemeWizard = !!(pic && pic.src && pic.src.includes('themewizard.php'));
        function set_tray_theme_image(before_theme_img, after_theme_img, before1_url, after1_url, after2_url) {
            var is_1 = matches_theme_image(pic.src, before1_url);

            if (is_1) {
                if (isThemeWizard) {
                    pic.src = pic.src.replace(before_theme_img, after_theme_img);
                } else {
                    pic.src = $cms.img(after1_url);
                }
            } else {
                if (isThemeWizard) {
                    pic.src = pic.src.replace(before_theme_img + '2', after_theme_img + '2');
                } else {
                    pic.src = $cms.img(after2_url);
                }
            }
        }
    }

    $cms.functions.abstractFileManagerGetAfmForm = function abstractFileManagerGetAfmForm() {
        var usesFtp = document.getElementById('uses_ftp');
        if (!usesFtp) {
            return;
        }

        ftp_ticker();
        usesFtp.onclick = ftp_ticker;

        function ftp_ticker() {
            var form = usesFtp.form;
            form.elements.ftp_domain.disabled = !usesFtp.checked;
            form.elements.ftp_directory.disabled = !usesFtp.checked;
            form.elements.ftp_username.disabled = !usesFtp.checked;
            form.elements.ftp_password.disabled = !usesFtp.checked;
            form.elements.remember_password.disabled = !usesFtp.checked;
        }
    };

    $cms.templates.forumsEmbed = function () {
        var frame = this;
        window.setInterval(function () {
            resize_frame(frame.name);
        }, 500);
    };

    $cms.templates.massSelectFormButtons = function (params) {
        var delBtn = this,
            form = delBtn.form;

        $cms.dom.on(delBtn, 'click', function () {
            confirm_delete(form, true, function () {
                var idEl = $cms.dom.$id('id'),
                    ids = (idEl.value === '') ? [] : idEl.value.split(',');

                for (var i = 0; i < ids.length; i++) {
                    prepareMassSelectMarker('', params.type, ids[i], true);
                }

                form.method = 'post';
                form.action = params.actionUrl;
                form.target = '_top';
                form.submit();
            });
        });

        $cms.dom.$id('id').fakeonchange = initialiseButtonVisibility;
        initialiseButtonVisibility();

        function initialiseButtonVisibility() {
            var id = $cms.dom.$('#id'),
                ids = (id.value === '') ? [] : id.value.split(/,/);

            $cms.dom.$('#submit_button').disabled = (ids.length !== 1);
            $cms.dom.$('#mass_select_button').disabled = (ids.length === 0);
        }
    };

    $cms.templates.massSelectDeleteForm = function () {
        var form = this;
        $cms.dom.on(form, 'submit', function (e) {
            e.preventDefault();
            confirm_delete(form, true);
        });
    };

    $cms.templates.groupMemberTimeoutManageScreen = function groupMemberTimeoutManageScreen(params, container) {
        $cms.dom.on(container, 'focus', '.js-focus-update-ajax-member-list', function (e, input) {
            if (input.value === '') {
                $cms.form.updateAjaxMemberList(input, null, true, e);
            }
        });

        $cms.dom.on(container, 'keyup', '.js-keyup-update-ajax-member-list', function (e, input) {
            $cms.form.updateAjaxMemberList(input, null, false, e)
        });
    };

    $cms.templates.uploadSyndicationSetupScreen = function (params) {
        var win_parent = window.parent || window.opener,
            id = 'upload_syndicate__' + params.hook + '__' + params.name,
            el = win_parent.document.getElementById(id);

        el.checked = true;

        var win = window;
        window.setTimeout(function () {
            if (win.faux_close !== undefined) {
                win.faux_close();
            } else {
                win.close();
            }
        }, 4000);
    };

    $cms.templates.loginScreen = function loginScreen(params, container) {
        if ((document.activeElement != null) || (document.activeElement !== $cms.dom.$('#password'))) {
            try {
                $cms.dom.$('#login_username').focus();
            } catch (ignore) {}
        }

        $cms.dom.on(container, 'click', '.js-click-checkbox-remember-me-confirm', function (e, checkbox) {
            if (checkbox.checked) {
                $cms.ui.confirm('{!REMEMBER_ME_COOKIE;}', function (answer) {
                    if (!answer) {
                        checkbox.checked = false;
                    }
                });
            }
        });

        $cms.dom.on(container, 'submit', '.js-submit-check-login-username-field', function (e, form) {
            if ($cms.form.checkFieldForBlankness(form.elements.login_username)) {
                $cms.ui.disableFormButtons(form);
            } else {
                e.preventDefault();
            }
        });
    };

    $cms.templates.blockTopLogin = function (blockTopLogin, container) {
        $cms.dom.on(container, 'submit', '.js-form-top-login', function (e, form) {
            if ($cms.form.checkFieldForBlankness(form.elements.login_username)) {
                $cms.ui.disableFormButtons(form);
            } else {
                e.preventDefault();
            }
        });

        $cms.dom.on(container, 'click', '.js-click-confirm-remember-me', function (e, checkbox) {
            if (checkbox.checked) {
                $cms.ui.confirm('{!REMEMBER_ME_COOKIE;}', function (answer) {
                    if (!answer) {
                        checkbox.checked = false;
                    }
                });
            }
        });
    };

    $cms.templates.ipBanScreen = function (params, container) {
        var textarea = commandrLs.querySelector('#bans');
        manage_scroll_height(textarea);

        if (!$cms.$MOBILE) {
            $cms.dom.on(container, 'keyup', '#bans', function (e, textarea) {
                manage_scroll_height(textarea);
            });
        }
    };

    $cms.templates.jsBlock = function jsBlock(params) {
        $cms.callBlock(params.blockCallUrl, '', $cms.dom.$id(params.jsBlockId), false, null, false, null, false, false);
    };

    $cms.templates.massSelectMarker = function (params) {
        var container = this;

        $cms.dom.on(container, 'click', '.js-chb-prepare-mass-select', function (e, checkbox) {
            prepareMassSelectMarker(params.supportMassSelect, params.type, params.id, checkbox.checked);
        });
    };


    $cms.templates.blockTopPersonalStats = function () {
        var container = this;

        $cms.dom.on(container, 'click', '.js-click-toggle-top-personal-stats', function (e) {
            if (toggle_top_personal_stats(e) === false) {
                e.preventDefault();
            }
        });
    };

    $cms.templates.blockSidePersonalStatsNo = function blockSidePersonalStatsNo(params, container) {
        $cms.dom.on(container, 'submit', '.js-submit-check-login-username-field', function (e, form) {
            if ($cms.form.checkFieldForBlankness(form.elements.login_username)) {
                $cms.ui.disableFormButtons(form);
            } else {
                e.preventDefault();
            }
        });

        $cms.dom.on(container, 'click', '.js-click-checkbox-remember-me-confirm', function (e, checkbox) {
            if (checkbox.checked) {
                $cms.ui.confirm('{!REMEMBER_ME_COOKIE;}', function (answer) {
                    if (!answer) {
                        checkbox.checked = false;
                    }
                });
            }
        });
    };

    function gdImageTransform(el) {
        /* GD text maybe can do with transforms */
        var span = document.createElement('span');
        if (typeof span.style.writingMode === 'string') {// IE (which has buggy rotation space reservation, but a decent writing-mode instead)
            el.style.display = 'none';
            span.style.writingMode = 'tb-lr';
            if (span.style.writingMode !== 'tb-lr') {
                span.style.writingMode = 'vertical-lr';
            }
            span.style.webkitWritingMode = 'vertical-lr';
            span.style.whiteSpace = 'nowrap';
            span.textContent = el.alt;
            el.parentNode.insertBefore(span, el);
        } else if (typeof span.style.transform === 'string') {
            el.style.display = 'none';
            $cms.dom.css(span, {
                transform: 'rotate(90deg)',
                transformOrigin: 'bottom left',
                top: '-1em',
                left: '0.5em',
                position: 'relative',
                display: 'inline-block',
                whiteSpace: 'nowrap',
                paddingRight: '0.5em'
            });

            el.parentNode.style.textAlign = 'left';
            el.parentNode.style.width = '1em';
            el.parentNode.style.overflow = 'hidden'; // Needed due to https://bugzilla.mozilla.org/show_bug.cgi?id=456497
            el.parentNode.style.verticalAlign = 'top';
            span.textContent = el.alt;

            el.parentNode.insertBefore(span, el);
            var span_proxy = span.cloneNode(true); // So we can measure width even with hidden tabs
            span_proxy.style.position = 'absolute';
            span_proxy.style.visibility = 'hidden';
            document.body.appendChild(span_proxy);

            window.setTimeout(function () {
                var width = span_proxy.offsetWidth + 15;
                span_proxy.parentNode.removeChild(span_proxy);
                if (el.parentNode.nodeName === 'TH' || el.parentNode.nodeName === 'TD') {
                    el.parentNode.style.height = width + 'px';
                } else {
                    el.parentNode.style.minHeight = width + 'px';
                }
            }, 0);
        }
    }

    function openLinkAsOverlay(options) {
        options = $cms.defaults({
            width: '800',
            height: 'auto',
            target: '_top',
            el: null
        }, options);

        var el = options.el,
            url = (el.href === undefined) ? el.action : el.href,
            url_stripped = url.replace(/#.*/, ''),
            new_url = url_stripped + (!url_stripped.includes('?') ? '?' : '&') + 'wide_high=1' + url.replace(/^[^\#]+/, '');

        $cms.ui.open(new_url, null, 'width=' + options.width + ';height=' + options.height, options.target);
    }

    function convert_tooltip(el) {
        var title = el.title;

        if (!title || $cms.isTouchEnabled || el.classList.contains('leave_native_tooltip')) {
            return;
        }

        // Remove old tooltip
        if ((el.localName === 'img') && !el.alt) {
            el.alt = el.title;
        }

        el.title = '';

        if (el.onmouseover || (el.firstElementChild && (el.firstElementChild.onmouseover || el.firstElementChild.title))) {
            // Only put on new tooltip if there's nothing with a tooltip inside the element
            return;
        }

        if (el.textContent) {
            var prefix = el.textContent + ': ';
            if (title.substr(0, prefix.length) === prefix) {
                title = title.substring(prefix.length, title.length);
            }
            else if (title === el.textContent) {
                return;
            }
        }

        // Stop the tooltip code adding to these events, by defining our own (it will not overwrite existing events).
        if (!el.onmouseout) {
            el.onmouseout = function () {
            };
        }
        if (!el.onmousemove) {
            el.onmouseover = function () {
            };
        }

        // And now define nice listeners for it all...
        var global = get_main_cms_window(true);

        el.cms_tooltip_title = $cms.filter.html(title);

        $cms.dom.on(el, 'mouseover', function (event) {
            global.$cms.ui.activateTooltip(el, event, el.cms_tooltip_title, 'auto', '', null, false, false, false, false, global);
        });

        $cms.dom.on(el, 'mousemove', function (event) {
            global.$cms.ui.repositionTooltip(el, event, false, false, null, false, global);
        });

        $cms.dom.on(el, 'mouseout', function () {
            global.$cms.ui.deactivateTooltip(el);
        });
    }

    function confirm_delete(form, multi, callback) {
        multi = !!multi;

        $cms.ui.confirm(
            multi ? '{!_ARE_YOU_SURE_DELETE;^}' : '{!ARE_YOU_SURE_DELETE;^}',
            function (result) {
                if (result) {
                    if (callback !== undefined) {
                        callback();
                    } else {
                        form.submit();
                    }
                }
            }
        );
    }


    function prepareMassSelectMarker(set, type, id, checked) {
        var mass_delete_form = $cms.dom.$id('mass_select_form__' + set);
        if (!mass_delete_form) {
            mass_delete_form = $cms.dom.$id('mass_select_button').form;
        }
        var key = type + '_' + id;
        var hidden;
        if (mass_delete_form.elements[key] === undefined) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = key;
            mass_delete_form.appendChild(hidden);
        } else {
            hidden = mass_delete_form.elements[key];
        }
        hidden.value = checked ? '1' : '0';
        mass_delete_form.style.display = 'block';
    }
}(window.$cms));