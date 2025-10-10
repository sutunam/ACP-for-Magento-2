<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use RunAsRoot\AgenticCommerceProtocol\Model\Feed\Generator\StaticFeedGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to generate static product feed
 */
class GenerateFeedCommand extends Command
{
    public function __construct(
        private readonly StaticFeedGenerator $feedGenerator,
        private readonly State $appState,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * Configure command
     */
    protected function configure(): void
    {
        $this->setName('acp:feed:generate')
            ->setDescription('Generate static ACP product feed')
            ->addOption(
                'store',
                's',
                InputOption::VALUE_OPTIONAL,
                'Store ID (default: 0)',
                0
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Output filename (default: feed.json)',
                'feed.json'
            );
    }

    /**
     * Execute command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Set area code for Magento operations
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        } catch (\Exception $e) {
            // Area already set, continue
        }

        $storeId = (int)$input->getOption('store');
        $filename = $input->getOption('output');

        $output->writeln('<info>🚀 Generating ACP product feed...</info>');
        $output->writeln('');

        try {
            $result = $this->feedGenerator->generate($storeId, $filename);

            $output->writeln('<info>✓ Feed generated successfully!</info>');
            $output->writeln('');
            $output->writeln("  <comment>Products:</comment>    {$result['product_count']}");
            $output->writeln("  <comment>File Size:</comment>   {$result['file_size']}");
            $output->writeln("  <comment>Path:</comment>        {$result['path']}");
            $output->writeln("  <comment>Generated:</comment>   {$result['generated_at']}");
            $output->writeln('');

            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>✗ Error generating feed:</error>');
            $output->writeln("<error>  {$e->getMessage()}</error>");
            $output->writeln('');

            return Cli::RETURN_FAILURE;
        }
    }
}
