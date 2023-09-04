<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Model;

use Gento\TangoTiendas\Api\Data\OrderNotificationInterface;
use Gento\TangoTiendas\Model\ResourceModel\OrderNotification as OrderNotificationResourceModel;
use Magento\Framework\Model\AbstractModel;

/**
 * @method ResourceModel\OrderNotification _getResource()
 * @method ResourceModel\OrderNotification getResource()
 */
class OrderNotification extends AbstractModel implements OrderNotificationInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(OrderNotificationResourceModel::class);
    }
}
