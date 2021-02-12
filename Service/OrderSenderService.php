<?php

namespace Gento\TangoTiendas\Service;

use Gento\TangoTiendas\Logger\Logger;
use Gento\TangoTiendas\Model\ParseException;
use Gento\TangoTiendas\Service\ConfigService;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Sales\Model\Order;
use Mugar\CustomerIdentificationDocument\Api\Data\CidFieldsInterface;
use TangoTiendas\Model\CustomerFactory;
use TangoTiendas\Model\OrderFactory;
use TangoTiendas\Model\OrderItemFactory;
use TangoTiendas\Service\OrdersFactory as OrdersServiceFactory;

class OrderSenderService
{
    /**
     * @var OrdersServiceFactory
     */
    protected $ordersServiceFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderItemFactory
     */
    protected $orderItemFactory;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string[]
     */
    protected $traceMessages = [];

    public function __construct(
        OrdersServiceFactory $ordersServiceFactory,
        OrderFactory $orderFactory,
        OrderItemFactory $orderItemFactory,
        CustomerFactory $customerFactory,
        ConfigService $configService,
        Logger $logger
    ) {
        $this->ordersServiceFactory = $ordersServiceFactory;
        $this->orderFactory = $orderFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->customerFactory = $customerFactory;
        $this->configService = $configService;
        $this->logger = $logger;
    }

    public function sendOrder(Order $order)
    {
        /** @var \TangoTiendas\Service\Orders $orderService */
        $orderService = $this->ordersServiceFactory->create([
            'accessToken' => $this->configService->getApiToken(),
        ]);

        $this->logger->info(__('Order created: %1', $order->getIncrementId()));

        $message = '';
        try {
            $orderModel = $this->getOrderModel($order);
            if ($orderModel->getTotal() != $order->getGrandTotal()) {
                throw new ParseException(__('El monto a informar difiere del pedido'));
            }
            $this->logger->info(json_encode($orderModel->jsonSerialize(), JSON_PRETTY_PRINT));
            $notification = $orderService->sendOrder($orderModel);
            $message = $notification->getMessage();
            $this->logger->info(var_export($notification, true));
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            $message = $e->getMessage();
        }

        $message = sprintf('TangoTiendas: %s', $message);

        if (count($this->traceMessages) > 0) {
            $message = sprintf("%s\n\nAdditional information: \n%s", $message, implode(PHP_EOL, $this->traceMessages));
        }

        $order->addCommentToStatusHistory($message);
    }

    /**
     * @return \TangoTiendas\Model\Order
     */
    private function getOrderModel(Order $order)
    {
        $customerModel = $this->getCustomerModel($order);
        /** @var \TangoTiendas\Model\Order $orderModel */
        $orderModel = $this->orderFactory->create();
        $orderModel->setCustomer($customerModel)
            ->setDate($order->getCreatedAt())
            ->setOrderID($order->getIncrementId())
            ->setOrderNumber($order->getIncrementId())
        ;

        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getProductType() == Configurable::TYPE_CODE) {
                continue;
            }

            if (!$orderItem->getTangoSku()) {
                $this->traceMessages[] = __('TangoTiendas: Producto sin Tango SKU %1', $orderItem->getSku());
            }

            /** @var \TangoTiendas\Model\OrderItem  $orderItemModel */
            $orderItemModel = $this->orderItemFactory
                ->create();

            $orderItemModel
                ->setProductCode($orderItem->getSku())
                ->setSKUCode($orderItem->getTangoSku())
                ->setQuantity($orderItem->getQtyOrdered())
                ->setUnitPrice($orderItem->getPrice())
                ->setDescription($orderItem->getName())
                ->setDiscountPercentage($orderItem->getDiscountPercent())
            ;
            die();

            $orderModel->addOrderItem($orderItemModel);
        }

        return $orderModel;
    }

    /**
     * @return \TangoTiendas\Model\Customer
     */
    private function getCustomerModel(Order $order)
    {
        // $customerCode = $order->getCustomerTangoCode();
        $customerCode = null;

        $user = sprintf('%s - %s, %s',
            $order->getCustomerIsGuest() ? __('Invitado') : __('Cliente'),
            $order->getCustomerFirstname(),
            $order->getCustomerLastname()
        );

        if ($order->getCustomerIsGuest()) {
            $customerCode = $this->configService->getCustomerGuestId();
        }

        $documentType = $order->getData(CidFieldsInterface::SHIPPING_CID_TYPE);
        $documentType = $this->getDocumentTypeAfip($documentType);

        $shippingAddress = $order->getShippingAddress();
        $provinceCode = $shippingAddress->getRegionCode();
        $provinceCode = $this->getRegionCodeAfip($provinceCode);

        /** @var \TangoTiendas\Model\Customer $customerModel */
        $customerModel = $this->customerFactory->create();
        $customerModel->setCustomerId((int) 1)
            ->setCode($customerCode)
            ->setDocumentType($documentType)
            ->setDocumentNumber($order->getData(CidFieldsInterface::SHIPPING_CID_NUMBER))
        // TODO Cambiar esto a dinamico
            ->setIvaCategoryCode('CF')
            ->setUser($user)
            ->setFirstName($order->getCustomerFirstname())
            ->setLastName($order->getCustomerLastname())
            ->setEmail($order->getCustomerEmail())
            ->setProvinceCode($provinceCode)
        ;

        return $customerModel;
    }

    private function getRegionCodeAfip($regionCode)
    {
        $regionCodes = [
            'B' => 1,
            'C' => 0,
            'H' => 16,
            'K' => 2,
            'U' => 17,
            'X' => 3,
            'W' => 4,
            'E' => 5,
            'P' => 18,
            'Y' => 6,
            'L' => 21,
            'F' => 8,
            'M' => 7,
            'N' => 19,
            'Q' => 20,
            'R' => 22,
            'A' => 9,
            'J' => 10,
            'D' => 11,
            'Z' => 23,
            'S' => 12,
            'G' => 13,
            'V' => 24,
            'T' => 14,
        ];
        if (isset($regionCodes[$regionCode])) {
            return $regionCodes[$regionCode];
        }

        return -1;
    }

    private function getDocumentTypeAfip($documentType)
    {
        $docTypes = [
            'cuit' => 80,
            'cuil' => 86,
            'cdi' => 87,
            'le' => 89,
            'lc' => 90,
            'dni' => 96,
        ];
        $sanitized = preg_replace('/[^A-Za-z]/', '', strtolower($documentType));
        if (isset($docTypes[$sanitized])) {
            return $docTypes[$sanitized];
        }

        return null;
    }
}
