<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Service;

use Magento\Directory\Model\PriceCurrency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Gento\TangoTiendas\Api\Data\LoggerInterface;

class ConfigService
{
    public const CONFIG_API_TOKEN = 'api_token';
    public const CONFIG_CODE_MAP_PAYMENT = 'code_map/payments_methods';
    public const CONFIG_GUEST_ID = 'guest_id';
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var LoggerInterface
     */
    private $logger;
    private PriceCurrency $priceCurrency;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface  $serializer
     * @param LoggerInterface      $logger
     * @param PriceCurrency        $priceCurrency
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        PriceCurrency $priceCurrency
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @return string
     */
    public function getApiToken()
    {
        return trim($this->getConfig(self::CONFIG_API_TOKEN));
    }

    /**
     * @return mixed
     */
    public function getCodeMapPayment()
    {
        $attr = $this->getConfig(self::CONFIG_CODE_MAP_PAYMENT);

        if (!$attr) {
            return [];
        }

        try {
            return $this->serializer->unserialize($attr);
        } catch (\InvalidArgumentException $e) {
            $this->logger->debug((string) $e);
            return [];
        }

    }

    /**
     * @return mixed
     */
    public function getCustomerGuestId()
    {
        return $this->getConfig(self::CONFIG_GUEST_ID);
    }

    /**
     * @param $path
     *
     * @return mixed
     */
    private function getConfig($path)
    {
        return $this->scopeConfig->getValue('tango/gento_tangotiendas/' . $path);
    }

    /**
     * @param $number
     *
     * @return float
     */
    public function round($number)
    {
        return $this->priceCurrency->roundPrice($number, 2);
    }
}
