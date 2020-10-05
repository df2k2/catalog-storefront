<?php
# Generated by the Magento PHP proto generator.  DO NOT EDIT!

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CatalogStorefrontApi\Api\Data;

use Magento\Framework\ObjectManagerInterface;

/**
 * Autogenerated description for ImportProductsRequest class
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
final class ImportProductsRequestArrayMapper
{
    /**
     * @var mixed
     */
    private $data;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
    * Convert the DTO to the array with the data
    *
    * @param ImportProductsRequest $dto
    * @return array
    */
    public function convertToArray(ImportProductsRequest $dto)
    {
        $result = [];
        /** Convert complex Array field **/
        $fieldArray = [];
        foreach ($dto->getProducts() as $fieldArrayDto) {
            $fieldArray[] = $this->objectManager->get(\Magento\CatalogStorefrontApi\Api\Data\ImportProductDataRequestArrayMapper::class)
                ->convertToArray($fieldArrayDto);
        }
        $result["products"] = $fieldArray;
        $result["store"] = $dto->getStore();
        if ($dto->getParams() !== null) {
            $result["params"] = $this->objectManager->get(\Magento\CatalogStorefrontApi\Api\Data\KeyValueArrayMapper::class)
                ->convertToArray($dto->getParams());
        }
        return $result;
    }
}
