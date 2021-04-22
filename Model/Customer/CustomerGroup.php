<?php

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
