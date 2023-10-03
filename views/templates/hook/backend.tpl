<!-- Block backend -->
<div id="inpostizi_block_backend" class="card">
    <div class="card-header">
        <h3 class="card-header-title">
            InPost Pay
        </h3>
    </div>
    {*    <div id="customerInfo" class="info-block">*}
    {*        <div class="row">*}
    {*            <div class="col-xxl-12">*}
    {*                <h2 class="mb-0">*}
    {*                    Szczegóły zamówienia InPost Pay*}
    {*                </h2>*}
    {*            </div>*}
    {*        </div>*}
    {*    </div>*}
    <div class="card-body">
        <div class="row mt-3">
            <div id="customerEmail" class="col-xxl-6">
                <p class="mb-1">
                    <strong>Wysyłka:</strong>
                </p>
                <p>
                    {$delivery}
                </p>
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
<!-- /Block backend -->
