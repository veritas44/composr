{$CURRENCY_SYMBOL,{FROM_CURRENCY}}{AMOUNT*}{+START,IF_EMPTY,{$CURRENCY_SYMBOL,{FROM_CURRENCY}}}&nbsp;{FROM_CURRENCY*}{+END}&nbsp;<span class="associated-details">({+START,IF,{$NEQ,{FROM_CURRENCY},{TO_CURRENCY}}}~{$CURRENCY_SYMBOL,{TO_CURRENCY}}{NEW_AMOUNT*}{+START,IF_EMPTY,{$CURRENCY_SYMBOL,{TO_CURRENCY}}}&nbsp;{TO_CURRENCY*}{+END}&nbsp;<a rel="external" title="{!ecommerce:CURRENCY_APPROXIMATION} {!LINK_NEW_WINDOW}" target="_blank" href="http://www.x-rates.com/cgi-bin/cgicalc.cgi?value={AMOUNT*}&amp;base={FROM_CURRENCY*}">&dagger;</a>{+END}{+START,IF,{$EQ,{FROM_CURRENCY},{TO_CURRENCY}}}<a rel="external" title="{!ecommerce:CURRENCY_CONVERSIONS} {!LINK_NEW_WINDOW}" target="_blank" href="http://www.x-rates.com/cgi-bin/cgicalc.cgi?value={AMOUNT*}&amp;base={FROM_CURRENCY*}">&dagger;</a>{+END})</span>