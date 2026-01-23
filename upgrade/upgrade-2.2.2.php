<?php

use InPost\Izi\Upgrade\ConfigUpdaterTrait;
use izi\prestashop\BasketApp\BasketAppClientInterface;
use izi\prestashop\BasketApp\Payment\PaymentsApiClientInterface;
use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use izi\prestashop\Common\PaymentType;
use izi\prestashop\Enum\Enum;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/ConfigUpdaterTrait.php';

class InPostIziUpdater_2_2_2
{
    use ConfigUpdaterTrait;

    /**
     * @var PaymentsApiClientInterface|null
     */
    private $client;

    public function __construct(Db $db, ?PaymentsApiClientInterface $client)
    {
        $this->db = $db;
        $this->client = $client;
    }

    public static function create(Module $module): self
    {
        $db = Db::getInstance();

        try {
            $client = $module->get(BasketAppClientInterface::class);
        } catch (ServiceNotFoundException $e) {
            $client = null;
        }

        return new self($db, $client);
    }

    public function upgrade(): bool
    {
        SymfonyCacheClearer::getInstance()->clear();

        return $this->updatePaymentOptionsConfig();
    }

    private function updatePaymentOptionsConfig(): bool
    {
        if (null === $this->client || !$clientId = Configuration::get('INPOST_PAY_client_id')) {
            return true;
        }

        try {
            $availableTypes = $this->client->getAvailablePaymentOptions()->getPaymentTypes();
        } catch (Exception $e) {
            return true;
        }

        $data = $this->getConfigDataByKeys([
            'INPOST_PAY_client_id',
            'INPOST_PAY_AVAILABLE_PAYMENT_OPTIONS',
        ]);
        $dataByShopGroup = $this->groupConfigValuesByShop($data);

        foreach ($dataByShopGroup as $shopGroupId => $dataByShop) {
            foreach ($dataByShop as $shopId => $data) {
                if (!isset($data['INPOST_PAY_AVAILABLE_PAYMENT_OPTIONS'])) {
                    continue;
                }

                if ($clientId !== $this->resolveClientId($dataByShopGroup, $shopGroupId, $shopId)) {
                    continue;
                }

                $enabledTypes = $this->decodePaymentTypesList($data['INPOST_PAY_AVAILABLE_PAYMENT_OPTIONS']);
                $value = [] === array_udiff($availableTypes, $enabledTypes, [Enum::class, 'compareValues']);

                if (!Configuration::updateValue('INPOST_PAY_ENABLE_ALL_PAYMENT_OPTIONS', (int) $value, false, $shopGroupId, $shopId)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function resolveClientId(array $config, int $shopGroupId, int $shopId): ?string
    {
        return $config[$shopGroupId][$shopId]['INPOST_PAY_client_id']
            ?? $config[$shopGroupId][0]['INPOST_PAY_client_id']
            ?? $config[0][0]['INPOST_PAY_client_id']
            ?? null;
    }

    private function decodePaymentTypesList(string $value): array
    {
        $data = json_decode($value, true);

        if (!is_array($data)) {
            return [];
        }

        return array_map([PaymentType::class, 'from'], $data);
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_2_2_2(Module $module): bool
{
    return InPostIziUpdater_2_2_2::create($module)->upgrade();
}
