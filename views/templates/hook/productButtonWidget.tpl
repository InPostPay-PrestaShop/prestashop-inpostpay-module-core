<div
  class="inpost-izi-btn-wrapper {if $refresh}js-inpost-izi-product-btn-wrapper{/if}"
  data-hook="{$hookName}"
  data-id-product="{$idProduct}"
  {if [] !== $styles}
    style="{foreach $styles as $name => $value}{$name|escape:'html'}:{$value|escape:'html'};{/foreach}"
  {/if}
>
  {if !$refresh}
    {$widget|cleanHtml nofilter}
  {/if}
  <div class="clearfix"></div>
</div>
