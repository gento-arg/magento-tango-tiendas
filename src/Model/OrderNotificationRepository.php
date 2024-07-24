<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Model;

use Gento\TangoTiendas\Api\Data\OrderNotificationInterface;
use Gento\TangoTiendas\Api\Data\OrderNotificationInterfaceFactory;
use Gento\TangoTiendas\Model\ResourceModel\OrderNotification as OrderNotificationResourceModel;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;

class OrderNotificationRepository
{
    private OrderNotificationResourceModel $resource;
    private OrderNotificationInterfaceFactory $factory;

    /**
     * @param OrderNotificationResourceModel    $resource
     * @param OrderNotificationInterfaceFactory $factory
     */
    public function __construct(
        OrderNotificationResourceModel $resource,
        OrderNotificationInterfaceFactory $factory
    ) {
        $this->resource = $resource;
        $this->factory = $factory;
    }

    /**
     * @param $order
     * @param $jsonData
     *
     * @throws LocalizedException
     * @return OrderNotificationInterface|AbstractModel
     */
    public function addNotification($order, $jsonData)
    {
        $row = $this->factory->create();
        $row->setOrderId($order->getId())
            ->setJsonData($jsonData);

        return $this->save($row);
    }

    /**
     * @param OrderNotificationInterface $item
     *
     * @throws LocalizedException
     * @return OrderNotificationInterface
     */
    public function save(OrderNotificationInterface $item)
    {
        /** @var OrderNotificationInterface|AbstractModel $item */
        try {
            $this->resource->save($item);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the notification: %1',
                $exception->getMessage()
            ));
        }
        return $item;
    }
}
