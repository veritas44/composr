{$REQUIRE_JAVASCRIPT,core_rich_media}
{+START,IF,{$NAND,{$MATCH_KEY_MATCH,_WILD:admin_zones},{$EQ,{B},code,quote,url}}}<a href="#!" data-tpl="comcodeEditorButton" data-tpl-params="{+START,PARAMS_JSON,IS_POSTING_FIELD,B,FIELD_NAME}{_*}{+END}" {+START,IF,{$AND,{IS_POSTING_FIELD},{$EQ,{B},thumb,img}}} id="js-attachment-upload-button"{+END} class="for-field-{FIELD_NAME*} comcode-button comcode-button-{B*} js-comcode-button-{B*} {+START,IF,{DIVIDER}} divider{+END}" title="{TITLE*}"><img src="{$IMG*,comcode_editor/{B}}" alt="{TITLE*}" height="34"></a>{+END}