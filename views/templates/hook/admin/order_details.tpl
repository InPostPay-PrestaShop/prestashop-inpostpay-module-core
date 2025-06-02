<div id="inpostizi_block_backend" class="card">
  <div class="card-header">
    <h3 class="card-header-title">
      InPost Pay
    </h3>
  </div>

  <div class="card-body">
    <div class="row mt-3">
      <div class="col">
        <p class="mb-1">
          <strong>{l s='Delivery method' mod='inpostizi'}:</strong>
        </p>
        <p>{$delivery}</p>

        {if $apm != ''}
          <p class="mb-1">
            <strong>{l s='APM' mod='inpostizi'}:</strong>
          </p>
          <p>{$apm}</p>
        {/if}

        {if $issue_invoice}
          <p>
            <strong>{l s='The customer requested a VAT invoice' mod='inpostizi'}</strong>
          </p>
        {/if}
      </div>
    </div>
  </div>
</div>
