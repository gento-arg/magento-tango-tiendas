<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2022 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Controller\Adminhtml\Order;

use Gento\TangoTiendas\Service\OrderSenderService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Model\OrderRepository;

class Resend extends Action
{
    private OrderSenderService $senderService;
    private OrderRepository $orderRepository;

    /**
     * @param Context            $context
     * @param OrderSenderService $senderService
     * @param OrderRepository    $orderRepository
     */
    public function __construct(
        Context $context,
        OrderSenderService $senderService,
        OrderRepository $orderRepository
    ) {
        parent::__construct($context);
        $this->senderService = $senderService;
        $this->orderRepository = $orderRepository;
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        try {
            $order = $this->orderRepository->get($orderId);
            $this->senderService->sendOrder($order);
            $this->orderRepository->save($order);
            $this->getMessageManager()->addSuccessMessage(__('The order has been sended to Tango'));
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage(__('An error has occured: %1', $e->getMessage()));
        }

        return $this->_redirect('sales/order/view', [
            'order_id' => $orderId
        ]);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Gento_TangoTiendas::orders');
    }
}
