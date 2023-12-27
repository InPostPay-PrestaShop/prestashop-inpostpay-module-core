<div
  id="inpostizi_block_home"
  {if [] !== $styles}
    style="{foreach $styles as $name => $value}{$name|escape:'html'}:{$value|escape:'html'};{/foreach}"
  {/if}
>
  <inpost-izi-button
    {foreach $attributes as $name => $value}
      {$name|escape:'html'}="{$value|escape:'html'}"
    {/foreach}
  ></inpost-izi-button>
  <div class="clearfix"></div>
</div>
