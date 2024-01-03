<div
  id="inpostizi_block_home"
  {if [] !== $styles}
    style="{foreach $styles as $name => $value}{$name|escape:'html'}:{$value|escape:'html'};{/foreach}"
  {/if}
>
  {$widget|cleanHtml nofilter}
  <div class="clearfix"></div>
</div>
