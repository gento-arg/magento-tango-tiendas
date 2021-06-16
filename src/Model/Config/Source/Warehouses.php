<?php
declare (strict_types = 1);

namespace Gento\TangoTiendas\Model\Config\Source;

use Gento\TangoTiendas\Service\ConfigService;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Message\ManagerInterface;
use TangoTiendas\Exceptions\ClientException;
use TangoTiendas\Service\WarehousesFactory;

class Warehouses implements OptionSourceInterface
{
    /**
     * @var WarehousesFactory
     */
    protected $warehouseServiceFactory;

    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    public function __construct(
        WarehousesFactory $warehouseServiceFactory,
        ConfigService $configService,
        ManagerInterface $messageManager
    ) {
        $this->warehouseServiceFactory = $warehouseServiceFactory;
        $this->configService = $configService;
        $this->messageManager = $messageManager;
    }

    public function toOptionArray()
    {
        $opts = [
            0 => [
                'value' => '',
                'label' => __('-- Default value --'),
            ]
        ];
        try {
            if (!$this->configService->getApiToken()) {
                return $opts;
            }

            /** @var \TangoTiendas\Service\Warehouse $warehouseService */
            $warehouseService = $this->warehouseServiceFactory->create([
                'accessToken' => $this->configService->getApiToken(),
            ]);

            /** @var \TangoTiendas\Model\PagingResult $items */
            $items = $warehouseService->getList();

            /** @var \TangoTiendas\Model\Warehouse $item */
            foreach ($items->getData() as $item) {
                $opts[] = [
                    'value' => $item->getCode(),
                    'label' => $item->getDescription(),
                ];
            }
        } catch (ClientException $ce) {
            $response = $ce->getResponse();
            $opts[0] = [
                'value' => '',
                'label' => $response['Message'],
            ];
        }

        return $opts;
    }
}
