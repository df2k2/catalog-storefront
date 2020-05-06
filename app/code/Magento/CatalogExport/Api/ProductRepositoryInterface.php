<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogExport\Api;

interface ProductRepositoryInterface
{
    /**
     * @param string[] $ids
     * @return \Magento\CatalogExport\Api\Data\ProductInterface[]
     */
    public function get(array $ids);
}