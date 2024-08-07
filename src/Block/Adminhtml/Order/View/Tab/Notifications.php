<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Block\Adminhtml\Order\View\Tab;

use Gento\TangoTiendas\Model\ResourceModel\OrderNotification\Collection;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;

class Notifications extends Template implements TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Gento_TangoTiendas::order/view/tab/notifications.phtml';

    protected $items;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry = null;
    private Collection $collection;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Collection $collection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Collection $collection,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
        $this->collection = $collection;
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Get Class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->getTabClass();
    }

    /**
     * @return \Magento\Framework\Api\Search\DocumentInterface[]|\Magento\Framework\DataObject[]
     */
    public function getFullHistory()
    {
        if ($this->items === null) {
            $this->items = $this->collection->addFilter('order_id', $this->getOrder()->getId())->getItems();
        }
        return $this->items;
    }

    /**
     * Retrieve order model instance
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * Get Tab Class
     *
     * @return string
     */
    public function getTabClass()
    {
        return 'ajax only';
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return __('Tango Notifications');
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return __('Tango Notifications');
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }
}
