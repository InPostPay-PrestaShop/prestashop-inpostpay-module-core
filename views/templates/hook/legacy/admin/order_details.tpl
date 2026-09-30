<div id="inpostizi_block_backend" class="panel">
  <div class="panel-heading">InPost Pay</div>

  <dl class="well list-detail">
    <dt>{l s='Unique masked email address' mod='inpostizi'}:</dt><dd>{mailto address=$delivery_email|escape:'html':'UTF-8'}</dd>
    <dt>{l s='Delivery method' mod='inpostizi'}:</dt><dd>{$delivery}</dd>

    {if $apm !== ''}
      <dt>{l s='APM' mod='inpostizi'}:</dt><dd>{$apm}</dd>
    {/if}

    {if $issue_invoice}
      <dt>{l s='The customer requested a VAT invoice' mod='inpostizi'}</dt><dd></dd>
    {/if}
  </dl>
</div>
