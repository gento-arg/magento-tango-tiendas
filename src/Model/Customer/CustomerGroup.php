<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Model\Customer;

use Magento\Customer\Model\Data\Group;

class CustomerGroup extends Group
{

    public function getTangoId()
    {
        return $this->_get('tango_id');
    }

    public function setTangoId($value)
    {
        return $this->setData('tango_id', $value);
    }

}
