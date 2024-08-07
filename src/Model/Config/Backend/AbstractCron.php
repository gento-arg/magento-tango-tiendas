<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Model\Config\Backend;

use Exception;
use Magento\Cron\Model\Config\Source\Frequency;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\App\Config\ValueInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

abstract class AbstractCron extends Value
{
    public const CRON_FREQUENCY_PATH = '';
    public const CRON_STRING_PATH = '';
    public const CRON_TIME_PATH = '';
    /**
     * @var ValueFactory
     */
    protected $_configValueFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ValueFactory $configValueFactory
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ValueFactory $configValueFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_configValueFactory = $configValueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * After save handler
     *
     * @throws Exception
     * @return $this
     */
    public function afterSave()
    {
        $time = $this->getData(static::CRON_TIME_PATH);
        $frequency = $this->getData(static::CRON_FREQUENCY_PATH);

        $frequencyWeekly = Frequency::CRON_WEEKLY;
        $frequencyMonthly = Frequency::CRON_MONTHLY;

        $cronExprArray = [
            (int) $time[1], # Minute
            (int) $time[0], # Hour
            $frequency == $frequencyMonthly ? '1' : '*', # Day of the Month
            '*', # Month of the Year
            $frequency == $frequencyWeekly ? '1' : '*', # Day of the Week
        ];

        $cronExprString = join(' ', $cronExprArray);

        try {
            /** @var $configValue ValueInterface */
            $configValue = $this->_configValueFactory->create();
            $configValue->load(static::CRON_STRING_PATH, 'path');
            $configValue->setValue($cronExprString)->setPath(static::CRON_STRING_PATH)->save();
        } catch (Exception $e) {
            throw new Exception(__('We can\'t save the Cron expression.'));
        }
        return parent::afterSave();
    }
}
