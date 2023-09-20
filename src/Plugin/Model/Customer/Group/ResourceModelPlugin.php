<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Plugin\Model\Customer\Group;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\ResourceModel\Group as GroupResource;

class ResourceModelPlugin
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * ResourceModelPlugin constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        $this->context = $context;
    }

    public function beforeSave(
        GroupResource $repository,
        Group $group
    ) {
        $tangoId = $this->context->getRequest()->getParam('tango_id');
        $group->setData('tango_id', $tangoId);

        return [$group];
    }
}
