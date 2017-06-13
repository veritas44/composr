(function ($cms) {
    'use strict';

    $cms.templates.setupwizard7 = function setupwizard7(params, container) {
        $cms.dom.on('#rules', 'click', function () {
            $cms.dom.smoothScroll($cms.dom.findPosY('#rules_set'));
        });
    };

    $cms.functions.adminSetupwizardStep5 = function () {
        var cuz = document.getElementById('collapse_user_zones');
        cuz.addEventListener('change', cuz_func);
        cuz_func();

        function cuz_func() {
            var gza = document.getElementById('guest_zone_access');
            gza.disabled = cuz.checked;
            if (cuz.checked) {
                gza.checked = true;
            }
        }
    };

    $cms.functions.adminSetupwizardStep7 = function () {
        document.getElementById('rules').addEventListener('change', function() {
            var items = ['preview_box_balanced', 'preview_box_liberal', 'preview_box_corporate'];
            for (var i = 0; i < items.length; i++) {
                document.getElementById(items[i]).style.display = (this.selectedIndex != i) ? 'none' : 'block';
            }
        });
    };

    $cms.functions.adminSetupwizardStep9 = function () {
        document.getElementById('site_closed').addEventListener('change', function () {
            document.getElementById('closed').disabled = !this.checked;
        });
    };
}(window.$cms));