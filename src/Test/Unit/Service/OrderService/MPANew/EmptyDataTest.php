<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2022 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Test\Unit\Service;

use Gento\TangoTiendas\Block\Adminhtml\Form\Field\PaymentTypes;
use Gento\TangoTiendas\Logger\Logger;
use Gento\TangoTiendas\Model\OrderNotificationRepository;
use Gento\TangoTiendas\Service\ConfigService;
use Gento\TangoTiendas\Service\OrderSenderService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;
use TangoTiendas\Model\CashPaymentFactory;
use TangoTiendas\Model\CustomerFactory;
use TangoTiendas\Model\OrderFactory;
use TangoTiendas\Model\OrderItemFactory;
use TangoTiendas\Model\Payment;
use TangoTiendas\Model\PaymentFactory;
use TangoTiendas\Model\ShippingFactory;
use TangoTiendas\Service\OrdersFactory as OrdersServiceFactory;

class  EmptyDataTest extends TestCase
{
    protected function setUp(): void
    {
        $ordersServiceFactory = $this->createMock(OrdersServiceFactory::class);
        $orderFactory = $this->createMock(OrderFactory::class);
        $cashpaymentFactory = $this->createMock(CashPaymentFactory::class);
        $paymentFactory = $this->createMock(PaymentFactory::class);
        $orderItemFactory = $this->createMock(OrderItemFactory::class);
        $customerFactory = $this->createMock(CustomerFactory::class);
        $shippingFactory = $this->createMock(ShippingFactory::class);
        $configService = $this->createMock(ConfigService::class);
        $logger = $this->createMock(Logger::class);
        $notificationRepository = $this->createMock(OrderNotificationRepository::class);
        $serializer = $this->createMock(SerializerInterface::class);
        $datetime = $this->createMock(DateTime::class);
        $paymentFactory->method('create')
            ->willReturn(new Payment());

        $this->service = new OrderSenderService(
            $ordersServiceFactory,
            $orderFactory,
            $cashpaymentFactory,
            $paymentFactory,
            $orderItemFactory,
            $customerFactory,
            $shippingFactory,
            $configService,
            $logger,
            $notificationRepository,
            $serializer,
            $datetime
        );
    }

    /**
     * @covers       \Gento\TangoTiendas\Service\OrderSenderService::getPaymentModel()
     * @dataProvider dataProvider
     */
    public function testOrdersEmptyData($orderMock, $orderPaymentMock)
    {
        $this->expectException(LocalizedException::class);
        $this->service->getPaymentModel($orderMock, $orderPaymentMock, [
            'type' => PaymentTypes::TYPE_PAYMENT,
            'code' => 'MPANew'
        ]);
    }

    public function dataProvider()
    {
        $data = [
            [$this->createMock(Order::class), $this->createMock(OrderPaymentInterface::class)]
        ];

        $orderMock = $this->createMock(Order::class);
        $orderPaymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderPaymentMock->method('getAdditionalInformation')
            ->willReturn([

            ]);
        $orderMock->method('getEntityId')
            ->willReturn(1);
        $data[] = [$orderMock, $orderPaymentMock];

        $orderMock = $this->createMock(Order::class);
        $orderPaymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderPaymentMock->method('getAdditionalInformation')
            ->willReturn(['mp_status' => 'approved']);
        $orderMock->method('getEntityId')
            ->willReturn(1);
        $data[] = [$orderMock, $orderPaymentMock];

        return $data;
    }
}
