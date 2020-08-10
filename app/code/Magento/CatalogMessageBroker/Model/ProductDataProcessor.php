<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogMessageBroker\Model;

use Magento\CatalogMessageBroker\Model\DataMapper\DataMapperInterface;

/**
 * Processing data for the product.
 *
 * Processes data coming from old api and merges data from new API to replace usage of existing data providers.
 */
class ProductDataProcessor
{
    /**
     * @var DataMapperInterface[]
     */
    private $dataMappers;

    /**
     * @var string[]
     * Map fields from Export API to Import API.
     * All fields should be present in format "export field name" => "import field name"
     */
    private static $map = [
        'product_id' => 'id',
        'sku' => 'sku',
        'status' => 'status',
        'name' => 'name',
        'description' => 'description',
        'short_description' => 'short_description',
        'url_key' => 'url_key',
        'tax_class_id' => 'tax_class_id',
        'weight' => 'weight',
        'swatch_image' => 'swatch_image',
        'thumbnail' => 'thumbnail',
        'image' => 'image',
        'small_image' => 'small_image',
        'visibility' => 'visibility',
        'meta_description' => 'meta_description',
        'meta_keyword' => 'meta_keyword',
        'meta_title' => 'meta_title',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
        'attributes' => 'dynamic_attributes',
        // 'variants' => 'variants', // \Magento\CatalogStorefrontApi\Api\Data\VariantInterface[]
        'category_ids' => 'categories',
        // The following list of fields are present in Import API (proto schema) but absent in Export API (et_schema)
        // TODO: review list, move to ^^ after corresponding fields resolved in story
        'has_options', //type: bool
        'type_id', //type: string
        'stock_status', //type: string
        'qty', //type: float
        'media_gallery', //type: \Magento\CatalogStorefrontApi\Api\Data\MediaGalleryItemInterface[]
        'dynamic_attributes', //type: \Magento\CatalogStorefrontApi\Api\Data\DynamicAttributeValueInterface[]
        'required_options', //type: string
        'created_in', //type: string
        'updated_in', //type: string
        'quantity_and_stock_status', //type: string
        'options_container', //type: string
        'msrp_display_actual_price_type', //type: string
        'is_returnable', //type: string
        'url_suffix', //type: string
        'url_rewrites', //type: \Magento\CatalogStorefrontApi\Api\Data\UrlRewriteInterface[]
        'configurable_options', //type: \Magento\CatalogStorefrontApi\Api\Data\ConfigurableOptionInterface[]
        'country_of_manufacture', //type: string
        'gift_message_available', //type: bool
        'special_price', //type: float
        'special_from_date', //type: string
        'special_to_date', //type: string
        'product_links', //type: \Magento\CatalogStorefrontApi\Api\Data\ProductLinkInterface[]
        'canonical_url', //type: string
        'ship_bundle_items', //type: string
        'dynamic_weight', //type: bool
        'dynamic_sku', //type: bool
        'dynamic_price', //type: bool
        'price_view', //type: string
        'items', //type: \Magento\CatalogStorefrontApi\Api\Data\BundleItemInterface[]
        'links_purchased_separately', //type: bool
        'links_title', //type: string
        'downloadable_product_links', //type: \Magento\CatalogStorefrontApi\Api\Data\DownloadableLinkInterface[]
        'downloadable_product_samples', //type: \Magento\CatalogStorefrontApi\Api\Data\DownloadableSampleInterface[]
        'only_xleft_in_stock', //type: float
    ];

    /**
     * @param DataMapperInterface[] $dataMappers
     */
    public function __construct(array $dataMappers)
    {
        $this->dataMappers = $dataMappers;
    }

    /**
     * Override data returned from old API with data returned from new API
     *
     * @param array $product
     * @param array $oldExportDataProduct
     * @return array
     * @deprecated this is a temporary solution that will be replaced
     * with declarative schema of mapping exported data format to storefront format
     */
    public function merge(array $product, array $oldExportDataProduct): array
    {
        $importProduct = [];
        foreach (self::$map as $nameInExport => $nameInImport) {
            if (isset($product[$nameInExport])) {
                $importProduct[$nameInImport] = $product[$nameInExport];
            }
            unset($oldExportDataProduct[$nameInExport]);
        }

        /** @var DataMapperInterface $dataMapper */
        foreach ($this->dataMappers as $nameInExport => $dataMapper) {
            // phpcs:ignore Magento2.Performance.ForeachArrayMerge
            $importProduct = \array_merge($importProduct, $dataMapper->map($product));
        }
        // TODO: handle grouped product
        if (\array_key_exists('type_id', $oldExportDataProduct)
            && $oldExportDataProduct['type_id'] === 'grouped'
        ) {
            $importProduct['grouped_items'] = $oldExportDataProduct['items'];
        }

        $importProduct['dynamic_attributes'] = [];
        foreach ($product['attributes'] ?? [] as $attribute) {
            $importProduct['dynamic_attributes'][] = [
                'code' => $attribute['attribute_code'],
                'value' => $importProduct[$attribute['attribute_code']] ?? '',
            ];
            unset($oldExportDataProduct[$attribute['attribute_code']]);
        }

        // TODO: only $importProduct must be returned https://github.com/magento/catalog-storefront/issues/165
        return array_merge($oldExportDataProduct, $importProduct);
    }
}
