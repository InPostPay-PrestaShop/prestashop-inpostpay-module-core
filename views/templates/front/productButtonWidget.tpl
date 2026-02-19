<div
  class="inpost-izi-btn-wrapper js-inpost-izi-product-btn-wrapper"
  data-hook="{$hookName}"
  data-id-product="{$idProduct}"
  {if [] !== $styles}
    style="{foreach $styles as $name => $value}{$name|escape:'html'}:{$value|escape:'html'};{/foreach}"
  {/if}
>
  {$widget|cleanHtml nofilter}
  <div class="clearfix"></div>
</div>
