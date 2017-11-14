function loadCommandr() {
    'use strict';
    
    $cms.requireCss('commandr');

    if (!document.getElementById('commandr_img_loader')) {
        var img = document.getElementById('commandr_img');
        img.className = 'footer_button_loading';
        var tmpEl = document.createElement('img');
        tmpEl.id = 'commandr_img_loader';
        tmpEl.src = $cms.img('{$IMG;,loading}');
        tmpEl.style.position = 'absolute';
        tmpEl.style.left = ($dom.findPosX(img) + 2) + 'px';
        tmpEl.style.top = ($dom.findPosY(img) + 1) + 'px';
        img.parentNode.appendChild(tmpEl);
    }

    $cms.requireJavascript('commandr').then(function () {
        $cms.ui.confirmSession().then(function (sessionConfirmed) {
            // Remove "loading" indicator from button
            var tmpElement = document.getElementById('commandr_img_loader');
            if (tmpElement) {
                tmpElement.parentNode.removeChild(tmpElement);
            }

            if (!sessionConfirmed) {
                return;
            }

            // Set up Commandr window
            var commandrBox = document.getElementById('commandr_box');
            if (!commandrBox) {
                commandrBox = $dom.create('div', {
                    id: 'commandr_box',
                    css: {
                        position: 'absolute',
                        zIndex: 2000,
                        left: ($dom.getWindowWidth() - 800) / 2 + 'px',
                        top: Math.max(100, ($dom.getWindowHeight() - 600) / 2) + 'px',
                        display: 'none',
                        width: '800px',
                        height: '500px'
                    }
                });
                document.body.appendChild(commandrBox);
                $cms.loadSnippet('commandr').then(function (result) {
                    $dom.html(commandrBox, result);
                    doCommandrBox();
                });
            } else {
                doCommandrBox();
            }
        });
    });
    
    function doCommandrBox() {
        var commandrBox = document.getElementById('commandr_box'),
            img = document.getElementById('commandr_img'),
            bi, cmdLine;

        if ($dom.notDisplayed(commandrBox)) { // Showing Commandr again
            $dom.show(commandrBox);

            if (img) {
                img.src = $cms.img('{$IMG;,icons/24x24/tool_buttons/commandr_off}');
                img.srcset = $cms.img('{$IMG;,icons/48x48/tool_buttons/commandr_off} 2x');
                img.className = '';
            }

            $dom.smoothScroll(0, null, null, function () {
                document.getElementById('commandr_command').focus();
            });

            cmdLine = document.getElementById('command_line');
            cmdLine.style.opacity = 0.0;
            $dom.fadeTo(cmdLine, null, 0.9);

            bi = document.getElementById('main_website_inner');
            if (bi) {
                bi.style.opacity = 1.0;
                $dom.fadeTo(bi, null, 0.3);
            }

            document.getElementById('commandr_command').focus();
        } else { // Hiding Commandr
            if (img) {
                img.src = $cms.img('{$IMG;,icons/24x24/tool_buttons/commandr_on}');
                img.srcset = $cms.img('{$IMG;,icons/48x48/tool_buttons/commandr_on}') + ' 2x';
                img.style.opacity = 1.0;
            }

            $dom.hide(commandrBox);
            bi = document.getElementById('main_website_inner');
            if (bi) {
                $dom.fadeIn(bi);
            }
        }
    }
}
