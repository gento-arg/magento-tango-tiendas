<?php
declare (strict_types = 1);

namespace Gento\TangoTiendas\Model\Config\Source;

use Gento\TangoTiendas\Service\ConfigService;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Message\ManagerInterface;
use TangoTiendas\Exceptions\ClientException;
use TangoTiendas\Service\StoresFactory;

class Stores implements OptionSourceInterface
{
    /**
     * @var StoresFactory
     */
    protected $storesServiceFactory;

    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    public function __construct(
        StoresFactory $storesServiceFactory,
        ConfigService $configService,
        ManagerInterface $messageManager
    ) {
        $this->storesServiceFactory = $storesServiceFactory;
        $this->configService = $configService;
        $this->messageManager = $messageManager;
    }

    public function toOptionArray()
    {
        $opts = [];
        try {
            /** @var \TangoTiendas\Service\Stores $storeService */
            $storeService = $this->storesServiceFactory->create([
                'accessToken' => $this->configService->getApiToken(),
            ]);

            /** @var \TangoTiendas\Model\PagingResult $stores */
            $stores = $storeService->getList();
            $opts[0] = [
                'value' => '',
                'label' => __('-- Default value --'),
            ];

            foreach ($stores->getData() as $store) {
                $opts[] = [
                    'value' => $store->getStoreNumber(),
                    'label' => $store->getDescription(),
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
