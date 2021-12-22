<?php

declare (strict_types = 1);

namespace Gento\TangoTiendas\Service;

use Gento\TangoTiendas\Block\Adminhtml\Form\Field\PaymentTypes;
use Gento\TangoTiendas\Logger\Logger;
use Gento\TangoTiendas\Model\ParseException;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Mugar\CustomerIdentificationDocument\Api\Data\CidFieldsInterface;
use TangoTiendas\Exceptions\ModelException;
use TangoTiendas\Model\CashPayment;
use TangoTiendas\Model\CashPaymentFactory;
use TangoTiendas\Model\Customer;
use TangoTiendas\Model\CustomerFactory;
use TangoTiendas\Model\Order as TangoOrder;
use TangoTiendas\Model\OrderFactory;
use TangoTiendas\Model\OrderItem;
use TangoTiendas\Model\OrderItemFactory;
use TangoTiendas\Model\Payment;
use TangoTiendas\Model\PaymentFactory;
use TangoTiendas\Service\Orders;
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
    /**
     * @var CashPaymentFactory
     */
    private $cashpaymentFactory;
    /**
     * @var PaymentFactory
     */
    private $paymentFactory;

    /**
     * @param OrdersServiceFactory $ordersServiceFactory
     * @param OrderFactory         $orderFactory
     * @param CashPaymentFactory   $cashpaymentFactory
     * @param PaymentFactory       $paymentFactory
     * @param OrderItemFactory     $orderItemFactory
     * @param CustomerFactory      $customerFactory
     * @param ConfigService        $configService
     * @param Logger               $logger
     */
    public function __construct(
        OrdersServiceFactory $ordersServiceFactory,
        OrderFactory $orderFactory,
        CashPaymentFactory $cashpaymentFactory,
        PaymentFactory $paymentFactory,
        OrderItemFactory $orderItemFactory,
        CustomerFactory $customerFactory,
        ConfigService $configService,
        Logger $logger
    ) {
        $this->ordersServiceFactory = $ordersServiceFactory;
        $this->orderFactory = $orderFactory;
        $this->cashpaymentFactory = $cashpaymentFactory;
        $this->paymentFactory = $paymentFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->customerFactory = $customerFactory;
        $this->configService = $configService;
        $this->logger = $logger;
    }

    /**
     * @param Order                 $order
     * @param OrderPaymentInterface $orderPayment
     * @param array                 $paymentMapData
     *
     * @throws ModelException
     * @return CashPayment|Payment
     */
    public function getPaymentModel(Order $order, OrderPaymentInterface $orderPayment, $paymentMapData)
    {
        $code = $paymentMapData['code'];
        $type = $paymentMapData['type'];

        if ($type === PaymentTypes::TYPE_CASH_PAYMENT) {
            $paymentModel = $this->cashpaymentFactory->create();
            $paymentModel->setPaymentID($order->getEntityId())
                ->setPaymentMethod($code)
                ->setPaymentTotal($orderPayment->getAmountPaid());
            return $paymentModel;
        }

        if ($type === PaymentTypes::TYPE_PAYMENT) {
            return $this->paymentFactory->create();
        }

        return null;
    }

    public function sendOrder(Order $order)
    {
        /** @var Orders $orderService */
        $orderService = $this->ordersServiceFactory->create([
            'accessToken' => $this->configService->getApiToken(),
        ]);

        $this->logger->info(__('Order created: %1', $order->getIncrementId()));

        try {
            $orderModel = $this->getOrderModel($order);
            if ($orderModel->getTotal() != $order->getGrandTotal()) {
                throw new ParseException(__('El monto a informar difiere del pedido'));
            }
            $this->logger->info(json_encode($orderModel->jsonSerialize(), JSON_PRETTY_PRINT));
            $notification = $orderService->sendOrder($orderModel);
            $message = $notification->getMessage();
            $this->logger->info(var_export($notification, true));
        } catch (\Exception $e) {
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
     * @throws ModelException
     * @return TangoOrder
     */
    private function getOrderModel(Order $order)
    {
        $customerModel = $this->getCustomerModel($order);
        $orderModel = $this->orderFactory->create();
        $orderModel->setCustomer($customerModel)
            ->setDate($order->getCreatedAt())
            ->setOrderID($order->getIncrementId())
            ->setOrderNumber($order->getIncrementId());

        foreach ($order->getAllVisibleItems() as /** @var Item */ $orderItem) {
            $parentItem = $orderItem;
            if ($orderItem->getParentItem()) {
                $parentItem = $orderItem->getParentItem();
            }

            if ($orderItem->getProductType() == Configurable::TYPE_CODE) {
                continue;
            }

            if (!$orderItem->getTangoSku()) {
                $this->traceMessages[] = __('TangoTiendas: Producto sin Tango SKU %1', $orderItem->getSku());
            }

            /** @var OrderItem $orderItemModel */
            $orderItemModel = $this->orderItemFactory
                ->create();

            $orderItemModel
                ->setProductCode($orderItem->getSku())
                ->setSKUCode($orderItem->getTangoSku())
                ->setQuantity($orderItem->getQtyOrdered())
                ->setUnitPrice($parentItem->getPrice())
                ->setDescription($orderItem->getName())
                ->setDiscountPercentage($orderItem->getDiscountPercent());

            $orderModel->addOrderItem($orderItemModel);
        }

        $paymentMatrix = $this->configService->getCodeMapPayment();
        $findPayment = function ($paymentCode) use ($paymentMatrix) {
            foreach ($paymentMatrix as $paymentData) {
                if ($paymentData['payment'] == $paymentCode)
                    return $paymentData;
            }
            return null;
        };

        $orderPayment = $order->getPayment();
        $paymentMapData = $findPayment($orderPayment->getMethod());
        if ($paymentMapData !== null) {
            $paymentModel = $this->getPaymentModel($order, $orderPayment, $paymentMapData);
            if ($paymentModel instanceof CashPayment) {
                $orderModel->addCashPayment($paymentModel);
            }
            if ($paymentModel instanceof Payment) {
                $orderModel->addPayment($paymentModel);
            }
        }

        return $orderModel;
    }

    /**
     * @throws ModelException
     * @return Customer
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

        /** @var Customer $customerModel */
        $customerModel = $this->customerFactory->create();
        $customerModel->setCustomerId(1)
            ->setCode($customerCode)
            ->setDocumentType($documentType)
            ->setDocumentNumber($order->getData(CidFieldsInterface::SHIPPING_CID_NUMBER))
            // TODO Cambiar esto a dinamico
            ->setIvaCategoryCode('CF')
            ->setUser($user)
            ->setFirstName($order->getCustomerFirstname())
            ->setLastName($order->getCustomerLastname())
            ->setEmail($order->getCustomerEmail())
            ->setProvinceCode($provinceCode);

        return $customerModel;
    }

    private function getDocumentTypeAfip($documentType)
    {
        $docTypes = ['cuit' => 80, 'cuil' => 86, 'cdi' => 87, 'le' => 89, 'lc' => 90, 'dni' => 96,];
        $sanitized = preg_replace('/[^A-Za-z]/', '', strtolower($documentType));
        if (isset($docTypes[$sanitized])) {
            return $docTypes[$sanitized];
        }

        return null;
    }

    private function getRegionCodeAfip($regionCode)
    {
        $regionCodes = [
            'B' => 1, 'C' => 0, 'H' => 16, 'K' => 2, 'U' => 17, 'X' => 3, 'W' => 4, 'E' => 5, 'P' => 18, 'Y' => 6,
            'L' => 21, 'F' => 8, 'M' => 7, 'N' => 19, 'Q' => 20, 'R' => 22, 'A' => 9, 'J' => 10, 'D' => 11, 'Z' => 23,
            'S' => 12, 'G' => 13, 'V' => 24, 'T' => 14,
        ];
        if (isset($regionCodes[$regionCode])) {
            return $regionCodes[$regionCode];
        }

        return -1;
    }
}
