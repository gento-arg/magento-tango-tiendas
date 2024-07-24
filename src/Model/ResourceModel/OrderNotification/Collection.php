<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Model\ResourceModel\OrderNotification;

use Gento\TangoTiendas\Model\OrderNotification;
use Gento\TangoTiendas\Model\ResourceModel\AbstractCollection;
use Gento\TangoTiendas\Model\ResourceModel\OrderNotification as OrderNotificationResourceModel;

class Collection extends AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(OrderNotification::class, OrderNotificationResourceModel::class);
    }
}
