<div
  class="inpost-izi-btn-wrapper js-inpost-izi-product-btn-wrapper"
  data-hook="{$hookName}"
  data-id-product="{$idProduct}"
  {if $refresh}
    data-refresh="true"
  {/if}
  {if [] !== $styles}
    style="{foreach $styles as $name => $value}{$name|escape:'html'}:{$value|escape:'html'};{/foreach}"
  {/if}
>
  {if !$refresh}
    {$widget|cleanHtml nofilter}
  {/if}
  <div class="clearfix"></div>
</div>
