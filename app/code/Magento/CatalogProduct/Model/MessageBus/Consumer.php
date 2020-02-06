<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogProduct\Model\MessageBus;

use Magento\CatalogProduct\Model\Storage\Client\CommandInterface;
use Magento\CatalogProduct\Model\Storage\Client\DataDefinitionInterface;
use Magento\CatalogProduct\Model\Storage\State;
use Psr\Log\LoggerInterface;

/**
 * Consumer for store data to data storage.
 */
class Consumer
{
    /**
     * @var CommandInterface
     */
    private $storageWriteSource;

    /**
     * @var State
     */
    private $storageState;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CatalogItemMessageBuilder
     */
    private $catalogItemMessageBuilder;

    /**
     * @var DataDefinitionInterface
     */
    private $storageSchemaManager;

    /**
     * @param CommandInterface $storageWriteSource
     * @param DataDefinitionInterface $storageSchemaManager
     * @param State $storageState
     * @param CatalogItemMessageBuilder $catalogItemMessageBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        CommandInterface $storageWriteSource,
        DataDefinitionInterface $storageSchemaManager,
        State $storageState,
        CatalogItemMessageBuilder $catalogItemMessageBuilder,
        LoggerInterface $logger
    ) {
        $this->storageWriteSource = $storageWriteSource;
        $this->storageSchemaManager = $storageSchemaManager;
        $this->storageState = $storageState;
        $this->logger = $logger;
        $this->catalogItemMessageBuilder = $catalogItemMessageBuilder;
    }

    /**
     * Process
     *
     * @param CatalogItemMessage[] $entities
     * @return void
     */
    public function process(array $entities): void
    {
        try {
            $dataPerType = $this->collectDataByEntityTypeAnsScope($entities);
            $this->saveToStorage($dataPerType);
        } catch (\Throwable $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * Collect catalog data. Structure by entity type and scope
     *
     * @param array $messages
     * @return array
     */
    private function collectDataByEntityTypeAnsScope(array $messages): array
    {
        $dataPerType = [];
        $messages = array_merge(...$messages);
        foreach ($messages as $message) {
            $entity = $this->catalogItemMessageBuilder->build($message);
            $entityData = $entity->getEntityData();
            $entityData['id'] = $entity->getEntityId();
            $entityData['store_id'] = $entity->getStoreId();
            $dataPerType[$entity->getEntityType()][$entity->getStoreId()][] = $entityData;
        }

        return $dataPerType;
    }

    /**
     * Save catalog data to the internal storage
     *
     * @param array $dataPerType
     * @throws \Magento\Framework\Exception\BulkException
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function saveToStorage(array $dataPerType): void
    {
        foreach ($dataPerType as $entityType => $dataPerStore) {
            foreach ($dataPerStore as $storeId => $data) {
                $sourceName = $this->storageState->getCurrentDataSourceName([$storeId, $entityType]);
                $this->logger->debug(
                    \sprintf('Save to storage "%s" %s record(s)', $sourceName, count($data)),
                    ['verbose' => $data]
                );

                // TODO: MC-31204
                // TODO: MC-31155
                if (!$this->storageSchemaManager->existsDataSource($sourceName)) {
                    $settings['index']['mapping']['total_fields']['limit'] = 200000;
                    $this->storageSchemaManager->createDataSource($sourceName, ['settings' => $settings]);
                    $this->storageSchemaManager->createEntity($sourceName, $entityType, []);
                }

                $this->storageWriteSource->bulkInsert($sourceName, $entityType, $data);
            }
        }
    }
}
