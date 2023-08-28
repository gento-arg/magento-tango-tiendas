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
use Magento\Framework\Serialize\SerializerInterface;
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

class  OrderSenderServiceMpaProTest extends TestCase
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
        $paymentFactory->method('create')
            ->willReturn(new Payment());
        $configService->method('round')
            ->willReturn(18780.65);
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
            $serializer
        );
    }

    /**
     * @covers \Gento\TangoTiendas\Service\OrderSenderService::getPaymentModel()
     */
    public function testCreditCardApproved()
    {
        $orderMock = $this->createMock(Order::class);
        $orderPaymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderPaymentMock->method('getAdditionalInformation')
            ->willReturn([
                "method_title" => "Pagar a trav\u00e9s de MercadoPago",
                "init_point" => "https:\/\/www.mercadopago.com.ar\/checkout\/v1\/redirect?pref_id=XXXXXXXXXXXXXXXXXXXXXXXXX",
                "id" => "XXXXXXXXXXXXXXXXXXXXXXXXX",
                "payment_0_id" => 123456789,
                "payment_0_type" => "debvisa",
                "payment_0_total_amount" => 18780.65,
                "payment_0_paid_amount" => 18780.65,
                "payment_0_refunded_amount" => 0,
                "payment_0_card_number" => "1234",
                "payment_0_installments" => 1,
                "mp_0_status" => "approved",
                "mp_0_status_detail" => "accredited",
                "payment_0_expiration" => "2023-08-25T19:59:59.000-04:00",
                "payment_index_list" => [0],
                "mp_status" => "approved",
                "mp_status_detail" => "accredited"
            ]);
        $orderPaymentMock->method('getEntityId')
            ->willReturn(1);

        $orderMock->method('getEntityId')
            ->willReturn(1);

        $orderMock->method('getUpdatedAt')
            ->willReturn('2023-08-24 12:30:05');

        $model = $this->service->getPaymentModel($orderMock, $orderPaymentMock, [
            'type' => PaymentTypes::TYPE_PAYMENT,
            'code' => 'MPAPro'
        ]);

        $this->assertNotNull($model, 'Model was returned null');
        $this->assertEquals(1, $model->getPaymentID());
        $this->assertEquals(1, $model->getVoucherNo());
        $this->assertEquals('2023-08-24 12:30:05', $model->getTransactionDate());
        $this->assertEquals('DI', $model->getCardCode());
        $this->assertEquals('1', $model->getCardPlanCode());
        $this->assertEquals(1, $model->getInstallments());
        $this->assertEquals(18780.65, $model->getInstallmentAmount());
        $this->assertEquals(18780.65, $model->getTotal());
    }
}
