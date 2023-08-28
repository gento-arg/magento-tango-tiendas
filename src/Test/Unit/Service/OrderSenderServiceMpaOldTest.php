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

class  OrderSenderServiceMpaOldTest extends TestCase
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
            ->willReturn($this->returnValueMap([
                [8404.65, 8404.65]
            ]));


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
        $serializedValue = '{"payment[method]":"mercadopago_custom","card_holder_name":"Ullamco saepe facere","doc_type":"DNI","doc_number":"12345678","installments":"1","issuer_id":"12335","total_amount":"8404.65","amount":"8404.65","site_id":"MLA","token":"123abcd567","payment_method_id":"master","gateway_mode":"0","extension_attributes":[],"method":"mercadopago_custom","payment_type_id":"credit_card","payment_method":"master","cardholderName":"Ullamco saepe facere","method_title":"Tarjeta de Credito y Debito","paymentResponse":{"accounts_info":null,"acquirer_reconciliation":[],"additional_info":{"authentication_code":null,"available_balance":null,"items":[{"category_id":"others","description":"Producto Custom","id":"SKU-CUSTOM-PRODUCT","picture_url":"","quantity":"1","title":"Producto Custom","unit_price":"6800"},{"category_id":null,"description":"Descuento","id":"Descuento","picture_url":null,"quantity":"1","title":"Descuento","unit_price":"-680"}],"nsu_processadora":null,"payer":{"address":{"street_name":"Calle falsa","zip_code":"1145"},"first_name":"Ullamco saepe ","last_name":"facere","phone":{"area_code":"-","number":"011555555"},"registration_date":"2023-08-12T22:04:21"},"shipments":{"receiver_address":{"apartment":"-","floor":"-","street_name":"Calle falsa","zip_code":"1145"}}},"authorization_code":"DG5ADP","binary_mode":false,"brand_id":null,"build_version":"3.12.1","call_for_authorize_id":null,"captured":true,"card":{"bin":"963258741","cardholder":{"identification":{"number":"12345678","type":"DNI"},"name":"Ullamco saepe facere"},"date_created":"2023-08-16T08:56:11.000-04:00","date_last_updated":"2023-08-16T08:56:11.000-04:00","expiration_month":6,"expiration_year":2026,"first_six_digits":"963252","id":null,"last_four_digits":"1234"},"charges_details":[{"accounts":{"from":"collector","to":"mp"},"amounts":{"original":344.59,"refunded":0},"client_id":0,"date_created":"2023-08-16T08:56:10.000-04:00","id":"00000000001-001","last_updated":"2023-08-16T08:56:10.000-04:00","metadata":[],"name":"mercadopago_fee","refund_charges":[],"reserve_id":null,"type":"fee"},{"accounts":{"from":"collector","to":"mp"},"amounts":{"original":588.33,"refunded":0},"client_id":0,"date_created":"2023-08-16T08:56:11.000-04:00","id":"00000000001-002","last_updated":"2023-08-16T08:56:11.000-04:00","metadata":{"mov_detail":"tax_withholding","mov_financial_entity":"santa_fe","mov_type":"expense","tax_id":10000000001,"tax_status":"applied","user_id":111111111},"name":"tax_withholding-santa_fe","refund_charges":[],"reserve_id":null,"type":"tax"},{"accounts":{"from":"collector","to":"mp"},"amounts":{"original":126.07,"refunded":0},"client_id":0,"date_created":"2023-08-16T08:56:11.000-04:00","id":"10000000001-003","last_updated":"2023-08-16T08:56:11.000-04:00","metadata":{"mov_detail":"tax_withholding_sirtac","mov_financial_entity":"santa_fe","mov_type":"expense","tax_id":10000000001,"tax_status":"applied","user_id":111111111},"name":"tax_withholding_sirtac-santa_fe","refund_charges":[],"reserve_id":null,"type":"tax"}],"collector_id":111111111,"corporation_id":null,"counter_currency":null,"coupon_amount":0,"currency_id":"ARS","date_approved":"2023-08-16T08:56:15.000-04:00","date_created":"2023-08-16T08:56:10.000-04:00","date_last_updated":"2023-08-16T08:57:20.000-04:00","date_of_expiration":null,"deduction_schema":"AHORAADMIN_6","description":"Order # 1000000001 in store","differential_pricing_id":null,"external_reference":"1000000001","fee_details":[{"amount":344.59,"fee_payer":"collector","type":"mercadopago_fee"}],"financing_group":null,"id":11111111111,"installments":1,"integrator_id":null,"issuer_id":"12335","live_mode":true,"marketplace_owner":null,"merchant_account_id":null,"merchant_number":null,"metadata":{"checkout_type":"credit_card","site":"MLA","platform_version":"2.4.5","test_mode":true,"module_version":"3.19.0","checkout":"custom","sponsor_id":222568987,"platform":"BP1EF6QIC4P001KBGQ10","token":"b0b68183691dc988f2f1ffcdfda7e0aa"},"money_release_date":"2023-09-03T08:56:15.000-04:00","money_release_schema":null,"money_release_status":"pending","notification_url":"https:\/\/noaflojes.com.ar\/mercadopago\/notifications\/custom\/?source_news=webhooks","operation_type":"regular_payment","order":[],"payer":{"email":"costaangelavaleria@yahoo.com.ar","entity_type":null,"first_name":null,"id":"1451163155","identification":{"number":"28022925","type":"DNI"},"last_name":null,"operator_id":null,"phone":{"area_code":null,"extension":null,"number":null},"type":null},"payment_method":{"id":"master","issuer_id":"12335","type":"credit_card"},"payment_method_id":"master","payment_type_id":"credit_card","platform_id":"ABCDE123123","point_of_interaction":{"business_info":{"sub_unit":"magento","unit":"online_payments"},"type":"UNSPECIFIED"},"pos_id":null,"processing_mode":"aggregator","refunds":[],"shipping_amount":0,"sponsor_id":111111111,"statement_descriptor":"MERPAGO*COMPRAEN","status":"approved","status_detail":"accredited","store_id":null,"tags":null,"taxes_amount":0,"transaction_amount":8404.65,"transaction_amount_refunded":0,"transaction_details":{"acquirer_reference":null,"external_resource_url":null,"financial_institution":null,"installment_amount":8404.65,"net_received_amount":7345.66,"overpaid_amount":0,"payable_deferral_period":null,"payment_method_reference_id":null,"total_paid_amount":8404.65}}}';
        $orderPaymentMock->method('getAdditionalInformation')
            ->willReturn(json_decode($serializedValue, true));
        $orderMock->method('getEntityId')
            ->willReturn(1);
        $orderMock->method('getUpdatedAt')
            ->willReturn('2023-08-24 12:30:05');


        $model = $this->service->getPaymentModel($orderMock, $orderPaymentMock, [
            'type' => PaymentTypes::TYPE_PAYMENT,
            'code' => 'MPA'
        ]);

        $this->assertNotNull($model, 'Model was returned null');
        $this->assertEquals(1, $model->getPaymentID());
        $this->assertEquals('DG5ADP', $model->getVoucherNo());
        $this->assertEquals('2023-08-24 12:30:05', $model->getTransactionDate());
        $this->assertEquals('DI', $model->getCardCode());
        $this->assertEquals('1', $model->getCardPlanCode());
        $this->assertEquals(1, $model->getInstallments());
        $this->assertEquals(8404.65, $model->getInstallmentAmount());
        $this->assertEquals(8404.65, $model->getTotal());
    }
}
