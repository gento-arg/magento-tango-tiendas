<?php
declare (strict_types = 1);

namespace Gento\TangoTiendas\Observer\Order;

use Gento\TangoTiendas\Logger\Logger;
use Gento\TangoTiendas\Service\ConfigService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Mugar\CustomerIdentificationDocument\Api\Data\CidFieldsInterface;
use TangoTiendas\Model\CustomerFactory;
use TangoTiendas\Model\OrderFactory;
use TangoTiendas\Service\OrdersFactory;

class PlaceAfter implements ObserverInterface
{
    /**
     * @var OrdersFactory
     */
    protected $ordersServiceFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(
        OrdersFactory $ordersServiceFactory,
        OrderFactory $orderFactory,
        CustomerFactory $customerFactory,
        ConfigService $configService,
        Logger $logger
    ) {
        $this->ordersServiceFactory = $ordersServiceFactory;
        $this->orderFactory = $orderFactory;
        $this->customerFactory = $customerFactory;
        $this->configService = $configService;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        /** @var \TangoTiendas\Service\Orders $orderService */
        $orderService = $this->ordersServiceFactory->create([
            'accessToken' => $this->configService->getApiToken(),
        ]);

        /** @var Order $order  */
        $order = $observer->getData('order');
        $this->logger->info(__('Order created: %1', $order->getIncrementId()));

        $customer = $order->getCustomer();
        // $customerId = $order->getCustomerId();
        $customerId = null;

        if ($order->getCustomerIsGuest()) {
            $customerId = $this->configService->getCustomerGuestId();
        }

        /** @var \TangoTiendas\Model\Customer $customerModel */
        // $customerModel = $this->customerFactory->create();
        // $customerModel->setCustomerId($customerId)
        //     ->setDocumentType($order->getData(CidFieldsInterface::SHIPPING_CID_TYPE));
        /** @var \TangoTiendas\Model\Order $orderModel */
        // $orderModel = $this->orderFactory->create();
        // $orderModel->setCustomer()
    }
}
