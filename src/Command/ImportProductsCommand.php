<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductFiltersConfigurationException;
use Synolia\SyliusAkeneoPlugin\Factory\ProductPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload;

final class ImportProductsCommand extends Command
{
    use LockableTrait;

    private const DESCRIPTION = 'Import Products from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:import:products';

    /** @var \Synolia\SyliusAkeneoPlugin\Client\ClientFactory */
    private $clientFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Factory\ProductPipelineFactory */
    private $productPipelineFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ProductPipelineFactory $productPipelineFactory,
        ClientFactory $clientFactory,
        LoggerInterface $akeneoLogger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->productPipelineFactory = $productPipelineFactory;
        $this->clientFactory = $clientFactory;
        $this->logger = $akeneoLogger;
    }

    protected function configure(): void
    {
        $this->setDescription(self::DESCRIPTION);
        $this->addOption('channels', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Akeneo channel code');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        if (!$this->lock()) {
            $output->writeln(Messages::commandAlreadyRunning());

            return 0;
        }

        $channels = $input->getOption('channels');
        if (!is_array($channels) || 0 === count($channels)) {
            $channelsApi = $this->clientFactory->createFromApiCredentials()->getChannelApi()->all();
            $channels = [];
            foreach ($channelsApi as $channelApi) {
                $channels[$channelApi['code']] = $channelApi['code'];
            }
        }

        $this->logger->notice(self::$defaultName);
        /** @var \League\Pipeline\Pipeline $productPipeline */
        $productPipeline = $this->productPipelineFactory->create();

        foreach ($channels as $channel) {
            /** @var \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload $productPayload */
            $productPayload = new ProductPayload($this->clientFactory->createFromApiCredentials(), $channel);
            try {
                $productPipeline->process($productPayload);
            } catch (NoProductFiltersConfigurationException $exception) {
                $this->logger->warning($exception->getMessage());
            }
        }

        $this->logger->notice(Messages::endOfCommand(self::$defaultName));
        $this->release();

        return 0;
    }
}
