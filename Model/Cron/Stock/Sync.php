<?php
declare (strict_types = 1);

namespace Gento\TangoTiendas\Model\Config\Source;

use Gento\TangoTiendas\Console\StockImportCommand;

class Stores
{
    /**
     * @var StockImportCommand
     */
    protected $stockCommand;

    public function __construct(
        StockImportCommand $stockCommand
    ) {
        $this->stockCommand = $stockCommand;
    }
}
