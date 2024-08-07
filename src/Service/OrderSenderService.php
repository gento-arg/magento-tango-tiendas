<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Service;

use Gento\TangoTiendas\Api\OrderSenderService\PaymentMethodProcessorInterface;
use Gento\TangoTiendas\Api\OrderSenderServiceInterface;
use Gento\TangoTiendas\Block\Adminhtml\Form\Field\PaymentTypes;
use Gento\TangoTiendas\Model\OrderNotificationRepository;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Mugar\CustomerIdentificationDocument\Api\Data\CidFieldsInterface;
use Gento\TangoTiendas\Api\Data\LoggerInterface;
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
use TangoTiendas\Model\Shipping;
use TangoTiendas\Model\ShippingFactory;
use TangoTiendas\Service\Orders;
use TangoTiendas\Service\OrdersFactory as OrdersServiceFactory;

class OrderSenderService implements OrderSenderServiceInterface
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

    protected LoggerInterface $logger;

    /**
     * @var string[]
     */
    protected $traceMessages = [];
    protected SerializerInterface $serializer;
    protected DateTime $dateTime;
    /**
     * @var PaymentMethodProcessorInterface[]
     */
    protected array $paymentMethodProcessors;
    protected ConfigService $configService;
    /**
     * @var CashPaymentFactory
     */
    private $cashpaymentFactory;
    /**
     * @var PaymentFactory
     */
    private $paymentFactory;
    /**
     * @var ShippingFactory
     */
    private $shippingFactory;
    private OrderNotificationRepository $notificationRepository;

    /**
     * @param OrdersServiceFactory $ordersServiceFactory
     * @param OrderFactory $orderFactory
     * @param CashPaymentFactory $cashpaymentFactory
     * @param PaymentFactory $paymentFactory
     * @param OrderItemFactory $orderItemFactory
     * @param CustomerFactory $customerFactory
     * @param ShippingFactory $shippingFactory
     * @param ConfigService $configService
     * @param LoggerInterface $logger
     * @param OrderNotificationRepository $notificationRepository
     * @param SerializerInterface $serializer
     * @param DateTime $dateTime
     * @param PaymentMethodProcessorInterface[] $paymentMethodProcessors
     */
    public function __construct(
        OrdersServiceFactory $ordersServiceFactory,
        OrderFactory $orderFactory,
        CashPaymentFactory $cashpaymentFactory,
        PaymentFactory $paymentFactory,
        OrderItemFactory $orderItemFactory,
        CustomerFactory $customerFactory,
        ShippingFactory $shippingFactory,
        ConfigService $configService,
        LoggerInterface $logger,
        OrderNotificationRepository $notificationRepository,
        SerializerInterface $serializer,
        DateTime $dateTime,
        array $paymentMethodProcessors = []
    ) {
        $this->ordersServiceFactory = $ordersServiceFactory;
        $this->orderFactory = $orderFactory;
        $this->cashpaymentFactory = $cashpaymentFactory;
        $this->paymentFactory = $paymentFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->customerFactory = $customerFactory;
        $this->configService = $configService;
        $this->logger = $logger;
        $this->shippingFactory = $shippingFactory;
        $this->notificationRepository = $notificationRepository;
        $this->serializer = $serializer;
        $this->dateTime = $dateTime;
        $this->paymentMethodProcessors = $paymentMethodProcessors;
    }

    /**
     * @inheritdoc
     */
    public function addOrderItem(TangoOrder $orderModel, Item $orderItem)
    {
        $parentItem = $orderItem;
        if ($orderItem->getParentItem() && $orderItem->getParentItem()->getProductType() !== 'bundle') {
            $parentItem = $orderItem->getParentItem();
        }

        if (!$orderItem->getTangoSku()) {
            $this->traceMessages[] = __('TangoTiendas: Producto sin Tango SKU %1', $orderItem->getSku());
        }

        /** @var OrderItem $orderItemModel */
        $orderItemModel = $this->orderItemFactory
            ->create();

        $unitPrice = $parentItem->getPriceInclTax();
        $discount = $parentItem->getDiscountPercent() ?? $orderItem->getDiscountPercent();

        $orderItemModel
            ->setProductCode($orderItem->getSku())
            ->setSKUCode($orderItem->getTangoSku())
            ->setQuantity($this->configService->round($orderItem->getQtyOrdered()))
            ->setUnitPrice($this->configService->round($unitPrice))
            ->setDescription($orderItem->getName())
            ->setDiscountPercentage($this->configService->round($discount));

        $orderModel->addOrderItem($orderItemModel);
    }

    /**
     * @inheritdoc
     */
    public function getPaymentModel(Order $order)
    {
        $orderPayment = $order->getPayment();
        foreach ($this->paymentMethodProcessors as $processor) {
            $paymentModel = $processor->process($order, $orderPayment);
            if ($paymentModel !== null) {
                return $paymentModel;
            }
        }

        $paymentMatrix = $this->configService->getCodeMapPayment();
        $findPayment = function ($paymentCode) use ($paymentMatrix) {
            foreach ($paymentMatrix as $paymentData) {
                if ($paymentData['payment'] == $paymentCode)
                    return $paymentData;
            }
            return null;
        };
        $paymentMapData = $findPayment($orderPayment->getMethod());
        if (!$paymentMapData) {
            return null;
        }

        $code = $paymentMapData['code'];
        $type = $paymentMapData['type'];

        if ($type === PaymentTypes::TYPE_CASH_PAYMENT) {
            $paymentModel = $this->cashpaymentFactory->create();
            $paymentModel->setPaymentID($order->getEntityId())
                ->setPaymentMethod($code)
                ->setPaymentTotal($this->configService->round($orderPayment->getAmountPaid()));
            return $paymentModel;
        }

        if ($type === PaymentTypes::TYPE_PAYMENT) {
            /** @var Payment $paymentModel */
            $paymentModel = $this->paymentFactory->create();
            switch ($code) {
                case 'MPA': // Legacy
                    $additionalInfo = $orderPayment->getAdditionalInformation();
                    $paymentResponse = $additionalInfo['paymentResponse'];

                    if ($paymentResponse['status'] !== 'approved') {
                        break;
                    }

                    $installments = $additionalInfo['installments'];
                    $installmentAmount = $additionalInfo['amount'] / $installments;
                    $paymentModel->setPaymentID($order->getEntityId())
                        ->setVoucherNo($paymentResponse['authorization_code'])
                        ->setTransactionDate($paymentResponse['money_release_date'])
                        ->setCardCode('DI')
                        ->setCardPlanCode('1')
                        ->setInstallments($installments)
                        ->setInstallmentAmount($this->configService->round($installmentAmount))
                        // Eventualmente, MP muestra un amount paid menor o mayor al total, y en la integracion con
                        // Tango eso no es viable
                        ->setPaymentTotal($this->configService->round($orderPayment->getAmountPaid()));
                    break;
            }
            return $paymentModel;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function sendOrder(OrderInterface $order)
    {
        /** @var Orders $orderService */
        $orderService = $this->ordersServiceFactory->create([
            'accessToken' => $this->configService->getApiToken(),
        ]);

        $this->logger->info(__('Order created: %1', $order->getIncrementId()));

        try {
            $orderModel = $this->getOrderModel($order);
            $this->logger->info(__('Formatted json data: %1', $order->getIncrementId()));
            $jsonData = json_encode($orderModel->jsonSerialize(), JSON_PRETTY_PRINT);
            $this->notificationRepository->addNotification($order, $jsonData);
            $this->logger->info(__('Add notification: %1', $order->getIncrementId()), ['message' => $jsonData]);
            $notification = $orderService->sendOrder($orderModel);
            $this->logger->info(__('Send notification: %1', $order->getIncrementId()));
            $message = $notification->getMessage();
            $this->logger->info(var_export($notification, true));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $message = $e->getMessage();
        }

        $message = sprintf('TangoTiendas: %s', $message);

        if (count($this->traceMessages) > 0) {
            $info = implode('<br>' . PHP_EOL, $this->traceMessages);
            $message = sprintf("%s\n\nAdditional information: \n%s", $message, $info);
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
        /** @var TangoOrder $orderModel */
        $orderModel = $this->orderFactory->create();
        $orderModel->setCustomer($customerModel)
            ->setDate($order->getCreatedAt())
            ->setOrderID($order->getIncrementId())
            ->setOrderNumber($order->getIncrementId());

        foreach ($order->getAllVisibleItems() as /** @var Item */ $orderItem) {
            switch ($orderItem->getProductType()) {
                case 'virtual':
                case 'simple':
                    $this->addOrderItem($orderModel, $orderItem);
                    break;
                case 'configurable':
                case 'bundle':
                    foreach ($orderItem->getChildrenItems() as $childItem) {
                        if ($orderItem->getProductType() === 'configurable' ||
                            $childItem->getProductType() === 'configurable' ||
                            $childItem->getProductType() === 'simple' ||
                            $childItem->getProductType() === 'virtual'
                        ) {
                            $this->addOrderItem($orderModel, $childItem);
                        }
                    }
                    break;
            }
        }

        $paymentModel = $this->getPaymentModel($order);
        if ($paymentModel instanceof CashPayment) {
            $orderModel->addCashPayment($paymentModel);
        }
        if ($paymentModel instanceof Payment) {
            $orderModel->addPayment($paymentModel);
        }

        /** @var Shipping $shipping */
        $shipping = $this->shippingFactory->create();
        $shippingAddress = $order->getShippingAddress();
        $provinceCode = $shippingAddress->getRegionCode();
        $provinceCode = $this->getRegionCodeAfip($provinceCode);
        $shipping->setShippingID($order->getId())
            ->setShippingCost($this->configService->round($order->getShippingAmount()))
            ->setStreet(implode(', ', $shippingAddress->getStreet()))
            ->setCity($shippingAddress->getCity())
            ->setProvinceCode($provinceCode)
            ->setPostalCode($shippingAddress->getPostcode());

        $orderModel->setShipping($shipping);

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
        if ($documentType === null) {
            return null;
        }
        $docTypes = ['cuit' => 80, 'cuil' => 86, 'cdi' => 87, 'le' => 89, 'lc' => 90, 'dni' => 96,];
        $sanitized = preg_replace('/[^A-Za-z]/', '', strtolower($documentType));
        if (isset($docTypes[$sanitized])) {
            return $docTypes[$sanitized];
        }

        return null;
    }

    private function getRegionCodeAfip($regionCode)
    {
        $regionCode = preg_replace('/^AR\-?/', '', $regionCode);
        $regionCodes = [
            'B' => 1, 'C' => 0, 'H' => 16, 'K' => 2, 'U' => 17, 'X' => 3, 'W' => 4, 'E' => 5, 'P' => 18, 'Y' => 6,
            'L' => 21, 'F' => 8, 'M' => 7, 'N' => 19, 'Q' => 20, 'R' => 22, 'A' => 9, 'J' => 10, 'D' => 11, 'Z' => 23,
            'S' => 12, 'G' => 13, 'V' => 24, 'T' => 14,
        ];
        if (isset($regionCodes[$regionCode])) {
            return $regionCodes[$regionCode];
        }

        return $regionCode;
    }
}
