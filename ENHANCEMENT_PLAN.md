# ACP Module Enhancement Plan
## Phase 1 & 2 Implementation: Per-Product Control + CLI Feed Generation

**Status:** Data patch created (`AddAcpProductAttributes.php`). Ready for continuation.

---

## ✅ Completed

1. **Setup/Patch/Data/AddAcpProductAttributes.php** - Creates two product attributes:
   - `acp_enable_search` (boolean) - Show in ChatGPT discovery
   - `acp_enable_checkout` (boolean) - Allow instant checkout
   - Both default to "1" (enabled) for backwards compatibility
   - Added to "Agentic Commerce" attribute group

---

## 🔄 Next Steps - Phase 1: Per-Product Control

### 1. Modify `Model/Feed/ProductFeedGenerator.php`

**Location:** `/Model/Feed/ProductFeedGenerator.php`

**Changes needed:**

```php
// In generate() method, after loading products, add filter:
foreach ($productCollection as $product) {
    // Skip products with acp_enable_search disabled
    if (!$product->getData('acp_enable_search')) {
        continue;
    }

    // ... existing code ...

    // Add flags to feed output
    $productData = [
        // ... existing fields ...
        'enable_search' => (bool)$product->getData('acp_enable_search'),
        'enable_checkout' => (bool)$product->getData('acp_enable_checkout'),
    ];
}
```

### 2. Modify `Model/CheckoutSessionManagement.php`

**Location:** `/Model/CheckoutSessionManagement.php`

**Changes needed:**

```php
// In create() method, after loading product, add validation:
$product = $this->productRepository->get($item['sku']);

if (!$product->getData('acp_enable_checkout')) {
    throw new LocalizedException(
        __('Product "%1" is not available for instant checkout', $product->getName())
    );
}
```

### 3. Create Admin UI

**File:** `view/adminhtml/ui_component/product_form.xml`

```xml
<?xml version="1.0"?>
<form xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
    <fieldset name="agentic-commerce">
        <settings>
            <label translate="true">Agentic Commerce</label>
            <collapsible>true</collapsible>
            <opened>false</opened>
        </settings>
        <field name="acp_enable_search" formElement="checkbox">
            <settings>
                <dataType>boolean</dataType>
                <label translate="true">Enable Agentic Search</label>
                <tooltip>
                    <description translate="true">Show this product in ChatGPT product discovery</description>
                </tooltip>
            </settings>
        </field>
        <field name="acp_enable_checkout" formElement="checkbox">
            <settings>
                <dataType>boolean</dataType>
                <label translate="true">Enable Agentic Checkout</label>
                <tooltip>
                    <description translate="true">Allow instant checkout for this product via ChatGPT</description>
                </tooltip>
            </settings>
        </field>
    </fieldset>
</form>
```

---

## 🔄 Phase 2: CLI Feed Generation

### 1. Create Console Command

**File:** `Console/Command/GenerateFeedCommand.php`

```php
<?php
declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use RunAsRoot\AgenticCommerceProtocol\Model\Feed\Generator\StaticFeedGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateFeedCommand extends Command
{
    private StaticFeedGenerator $feedGenerator;
    private State $appState;

    public function __construct(
        StaticFeedGenerator $feedGenerator,
        State $appState,
        string $name = null
    ) {
        $this->feedGenerator = $feedGenerator;
        $this->appState = $appState;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('acp:feed:generate')
            ->setDescription('Generate static ACP product feed')
            ->addOption('store', 's', InputOption::VALUE_OPTIONAL, 'Store ID', 0)
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file path', 'var/acp/feed.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);

        $storeId = (int)$input->getOption('store');
        $outputPath = $input->getOption('output');

        $output->writeln('<info>Generating ACP product feed...</info>');

        try {
            $result = $this->feedGenerator->generate($storeId, $outputPath);

            $output->writeln("<info>✓ Feed generated: {$outputPath}</info>");
            $output->writeln("<info>✓ Products: {$result['product_count']}</info>");
            $output->writeln("<info>✓ Size: {$result['file_size']}</info>");

            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>✗ Error: {$e->getMessage()}</error>");
            return Cli::RETURN_FAILURE;
        }
    }
}
```

### 2. Create Static Feed Generator

**File:** `Model/Feed/Generator/StaticFeedGenerator.php`

```php
<?php
declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Feed\Generator;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Serialize\SerializerInterface;
use RunAsRoot\AgenticCommerceProtocol\Model\Feed\ProductFeedGenerator;

class StaticFeedGenerator
{
    private ProductFeedGenerator $feedGenerator;
    private Filesystem $filesystem;
    private SerializerInterface $serializer;

    public function __construct(
        ProductFeedGenerator $feedGenerator,
        Filesystem $filesystem,
        SerializerInterface $serializer
    ) {
        $this->feedGenerator = $feedGenerator;
        $this->filesystem = $filesystem;
        $this->serializer = $serializer;
    }

    public function generate(int $storeId, string $outputPath): array
    {
        // Generate feed data
        $feedData = $this->feedGenerator->generate($storeId);

        // Add metadata
        $feedData['generated_at'] = date('c');
        $feedData['store_id'] = $storeId;

        // Write to file
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $relativePath = 'acp/' . basename($outputPath);

        $directory->writeFile(
            $relativePath,
            $this->serializer->serialize($feedData)
        );

        return [
            'product_count' => count($feedData['products'] ?? []),
            'file_size' => $directory->stat($relativePath)['size'] ?? 0,
            'path' => $directory->getAbsolutePath($relativePath)
        ];
    }
}
```

### 3. Create Cron Job

**File:** `Cron/GenerateFeed.php`

```php
<?php
declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Cron;

use Psr\Log\LoggerInterface;
use RunAsRoot\AgenticCommerceProtocol\Model\Feed\Generator\StaticFeedGenerator;

class GenerateFeed
{
    private StaticFeedGenerator $feedGenerator;
    private LoggerInterface $logger;

    public function __construct(
        StaticFeedGenerator $feedGenerator,
        LoggerInterface $logger
    ) {
        $this->feedGenerator = $feedGenerator;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        try {
            $this->logger->info('ACP: Starting scheduled feed generation');
            $result = $this->feedGenerator->generate(0, 'feed.json');
            $this->logger->info('ACP: Feed generated successfully', $result);
        } catch (\Exception $e) {
            $this->logger->error('ACP: Feed generation failed', ['error' => $e->getMessage()]);
        }
    }
}
```

### 4. Add Cron Configuration

**File:** `etc/crontab.xml`

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Cron:etc/crontab.xsd">
    <group id="default">
        <job name="acp_generate_feed" instance="RunAsRoot\AgenticCommerceProtocol\Cron\GenerateFeed" method="execute">
            <schedule>0 */6 * * *</schedule>
        </job>
    </group>
</config>
```

### 5. Update `etc/di.xml`

Add console command registration:

```xml
<type name="Magento\Framework\Console\CommandListInterface">
    <arguments>
        <argument name="commands" xsi:type="array">
            <item name="acpGenerateFeed" xsi:type="object">RunAsRoot\AgenticCommerceProtocol\Console\Command\GenerateFeedCommand</item>
        </argument>
    </arguments>
</type>
```

---

## 📝 Testing Commands

After implementation:

```bash
# Run setup
bin/magento setup:upgrade
bin/magento setup:di:compile

# Test CLI command
bin/magento acp:feed:generate

# Verify output
ls -lh var/acp/feed.json
cat var/acp/feed.json | jq '.products | length'

# Test cron
bin/magento cron:run --group default
```

---

## 🎯 Success Criteria

**Phase 1 Complete when:**
- ✅ Products can be hidden from feed via admin
- ✅ Products can be restricted from checkout
- ✅ Admin UI shows new attributes
- ✅ Validation prevents checkout of disabled products

**Phase 2 Complete when:**
- ✅ `bin/magento acp:feed:generate` works
- ✅ Static feed file generated in var/acp/
- ✅ Cron job runs every 6 hours
- ✅ File includes product count and timestamp

---

## 🔄 Future Enhancements (Phase 3+)

- XML attribute mapping system
- Feed validation command
- Admin config for feed mode (realtime/static/hybrid)
- Observer for auto-regeneration on product save
- Multiple feed formats (JSON/XML/CSV)

---

**Created:** 2025-10-10
**Author:** David Lambauer + Claude
**Status:** Ready for implementation
