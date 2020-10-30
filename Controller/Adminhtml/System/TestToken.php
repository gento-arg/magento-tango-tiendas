<?php
declare (strict_types = 1);

namespace Gento\TangoTiendas\Controller\Adminhtml\System;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use TangoTiendas\Service\StoresFactory;

class TestToken extends Action
{
    protected $resultJsonFactory;

    /**
     * @var StoresFactory
     */
    protected $storesServiceFactory;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LoggerInterface $logger,
        StoresFactory $storesServiceFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storesServiceFactory = $storesServiceFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $success = true;
        try {
            $token = $this->getRequest()->getParam('token');

            /** @var \TangoTiendas\Service\Stores $storeService */
            $storeService = $this->storesServiceFactory->create([
                'accessToken' => $token,
            ]);
            $status = $storeService->getStatus();
        } catch (\Exception $e) {
            $success = false;
            $this->logger->critical($e);
        }

        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();

        return $result->setData([
            'success' => $success,
            'status' => $status,
        ]);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Gento_TangoTiendas::config');
    }
}
