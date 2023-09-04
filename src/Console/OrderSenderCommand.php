<?php
/**
 * @author    Manuel Cánepa <manuel@gento.com.ar>
 * @copyright GENTo 2023 Todos los derechos reservados
 */

declare (strict_types = 1);

namespace Gento\TangoTiendas\Console;

use Gento\TangoTiendas\Service\OrderSenderService;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Sales\Model\OrderRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class OrderSenderCommand extends Command
{
    /**
     * @var State
     */
    private $state;
    private OrderSenderService $senderService;
    private OrderRepository $orderRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @param State                 $state
     * @param OrderSenderService    $senderService
     * @param OrderRepository       $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param string|null           $name
     */
    public function __construct(
        State $state,
        OrderSenderService $senderService,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        string $name = null
    ) {
        parent::__construct($name);
        $this->state = $state;
        $this->senderService = $senderService;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('tangotiendas:order:send')
            ->setDescription('Send orders to tango.')
            ->addOption(
                'order_id',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                __('Order IDs')
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $orderIds = $input->getOption('order_id');

        $this->state->setAreaCode(Area::AREA_CRONTAB);
        foreach ($orderIds as $incrementId) {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('increment_id', $incrementId)->create();

            try {
                $orders = $this->orderRepository->getList($searchCriteria);
                foreach ($orders->getItems() as $order) {
                    $this->senderService->sendOrder($order);
                    $this->orderRepository->save($order);
                }
            } catch (\Exception $e) {
                $output->writeln(__('An error has occured: %1', $e->getMessage()));
            }
        }
//        $this->syncCommand->setOutput($output);
//        $this->syncCommand->execute();
    }
}
