<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2022 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Plugin\Sales\Block\Adminhtml\Order\View;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class AddResendButtonPlugin
{
    public function beforeSetLayout(
        OrderView $view
    ) {
        $message = __('Are you sure you want to do this?');
        $url = $view->getUrl('tangotiendas/order/resend');
        $view->addButton('tangotiendas_resend_order', [
                'label' => __('Resend to tango'),
                'onclick' => "confirmSetLocation('{$message}', '{$url}')"
            ]
        );
    }
}
