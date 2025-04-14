<div id="inpostizi_block_backend" class="card">
  <div class="card-header">
    <h3 class="card-header-title">
      InPost Pay
    </h3>
  </div>

  <div class="card-body">
    <div class="row mt-3">
      <div class="col-6">
        <p class="mb-1">
          <strong>Wysyłka:</strong>
        </p>
        <p>{$delivery}</p>

        {if $apm != ''}
          <p class="mb-1">
            <strong>Paczkomat:</strong>
          </p>
          <p>{$apm}</p>
        {/if}
      </div>
    </div>
  </div>
</div>
