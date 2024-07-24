<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo (https://gento.com.ar) Todos los derechos reservados
 */

declare (strict_types = 1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Gento_TangoTiendas',
    __DIR__
);
