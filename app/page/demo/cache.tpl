
= {$page->now}

{capture name='c'}{$page->now}{/capture}
{nocache}

* {$page->now}

= {$smarty.capture.c}
{/nocache}
