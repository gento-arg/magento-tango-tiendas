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

class CreditCardApprovedTest extends TestCase
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
        $serializer->method('unserialize')
            ->willReturnCallback(function ($data) {
                return json_decode($data, true);
            });
        $datetime = $this->createMock(DateTime::class);
        $paymentFactory->method('create')
            ->willReturn(new Payment());
        $configService->method('round')
            ->willReturnCallback(function ($value) {
                return $value;
            });
        $datetime->method('gmtDate')
            ->willReturn('2023-08-24 12:30:05');

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
    public function testCreditCardApproved($orderMock, $orderPaymentMock, $result)
    {
        $model = $this->service->getPaymentModel($orderMock, $orderPaymentMock, [
            'type' => PaymentTypes::TYPE_PAYMENT,
            'code' => 'MPANew'
        ]);

        $this->assertNotNull($model, 'Model was returned null');
        $this->assertEquals($result['payment_id'], $model->getPaymentID(), 'Payment ID');
        $this->assertEquals($result['voucher_no'], $model->getVoucherNo(), 'Voucher No');
        $this->assertEquals($result['transaction_date'], $model->getTransactionDate(), 'Transaction Date');
        $this->assertEquals($result['card_code'], $model->getCardCode(), 'Card Code');
        $this->assertEquals($result['card_plan_code'], $model->getCardPlanCode(), 'Card Plan Code');
        $this->assertEquals($result['installments'], $model->getInstallments(), 'Installments');
        $this->assertEquals($result['installments_amount'], $model->getInstallmentAmount(), 'Installments Amount');
        $this->assertEquals($result['total'], $model->getTotal(), 'Total');
    }

    public function dataProvider()
    {
        $data = [];

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getEntityId')
            ->willReturn(1);

        $orderPaymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderPaymentMock->method('getAdditionalInformation')
            ->willReturn([
                'mp_status' => 'approved'
            ]);
        $orderPaymentMock->method('getAdditionalData')
            ->willReturn(json_encode([
                'money_release_date' => '2023-08-31 20:00:00',
                'authorization_code' => 123456,
                'transaction_amount' => 8542,
                'installments' => 1
            ]));
        $paymentModel = [
            'payment_id' => 1,
            'voucher_no' => 123456,
            'transaction_date' => '2023-08-31 20:00:00',
            'card_code' => 'DI',
            'card_plan_code' => '1',
            'installments' => 1,
            'installments_amount' => 8542,
            'total' => 8542,
        ];
        $data[] = [$orderMock, $orderPaymentMock, $paymentModel];


        $orderPaymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderPaymentMock->method('getAdditionalInformation')
            ->willReturn([
                'mp_status' => 'approved'
            ]);
        $orderPaymentMock->method('getAdditionalData')
            ->willReturn(json_encode([
                'money_release_date' => '2023-09-17T17:26:56.250-04:00',
                'authorization_code' => '006751',
                'transaction_amount' => 4500,
                'installments' => 1
            ]));
        $paymentModel = [
            'payment_id' => 1,
            'voucher_no' => '006751',
            'transaction_date' => '2023-09-17T17:26:56.250-04:00',
            'card_code' => 'DI',
            'card_plan_code' => '1',
            'installments' => 1,
            'installments_amount' => 4500,
            'total' => 4500,
        ];
        $data[] = [$orderMock, $orderPaymentMock, $paymentModel];

        $orderPaymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderPaymentMock->method('getAdditionalInformation')
            ->willReturn([
                'mp_status' => 'approved'
            ]);
        $orderPaymentMock->method('getAdditionalData')
            ->willReturn(json_encode([
                'money_release_date' => '2023-09-18T11:33:08.975-04:00',
                'authorization_code' => '563447',
                'transaction_amount' => 145350,
                'installments' => 1
            ]));
        $paymentModel = [
            'payment_id' => 1,
            'voucher_no' => '563447',
            'transaction_date' => '2023-09-18T11:33:08.975-04:00',
            'card_code' => 'DI',
            'card_plan_code' => '1',
            'installments' => 1,
            'installments_amount' => 145350,
            'total' => 145350,
        ];
        $data[] = [$orderMock, $orderPaymentMock, $paymentModel];

        $orderPaymentMock = $this->createMock(OrderPaymentInterface::class);
        $orderPaymentMock->method('getAdditionalInformation')
            ->willReturn([
                'mp_status' => 'approved'
            ]);
        $orderPaymentMock->method('getAdditionalData')
            ->willReturn(json_encode([
                'money_release_date' => '2023-09-18T11:33:08.975-04:00',
                'authorization_code' => '563447',
                'transaction_amount' => 145350,
                'installments' => 6
            ]));
        $paymentModel = [
            'payment_id' => 1,
            'voucher_no' => '563447',
            'transaction_date' => '2023-09-18T11:33:08.975-04:00',
            'card_code' => 'DI',
            'card_plan_code' => '1',
            'installments' => 6,
            'installments_amount' => 24225,
            'total' => 145350,
        ];
        $data[] = [$orderMock, $orderPaymentMock, $paymentModel];

        return $data;
    }
}
