<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2017

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    calculatr
 */

i_solemnly_declare(I_UNDERSTAND_SQL_INJECTION | I_UNDERSTAND_XSS | I_UNDERSTAND_PATH_INJECTION);
load_csp(array('csp_allow_eval_js' => '1'));

require_javascript('checking');
require_javascript('calculatr');

$message = $map['message'];
$equation = $map['equation'];
$equation = str_replace('math.', 'Math.', strtolower($equation)); // Name fields come out lower case, so equation needs to be
?>
<form data-require-javascript="calculatr" data-tpl="miniblockMainCalculator" data-tp-message="<?= escape_html($message) ?>" data-tp-equation="<?= escape_html($equation) ?>" action="#!" method="post">
<?php
foreach ($map as $key => $val) {
    $key = strtolower($key);
    if (($key != 'equation') && ($key != 'block') && ($key != 'message') && ($key != 'cache')) {
        echo '<p>
            <input class="input_integer_required right" size="6" type="text" id="' . escape_html($key) . '" name="' . escape_html($key) . '" value="" />
            <label class="field_title" for="' . escape_html($key) . '">' . escape_html($val) . '</label>
        </p>';
    }
}
?>
<p class="proceed_button">
    <input data-click-pd class="buttons__calculate button_screen_item js-btn-click-calculate-sum" type="submit" value="Calculate" />
</p>
</form>
