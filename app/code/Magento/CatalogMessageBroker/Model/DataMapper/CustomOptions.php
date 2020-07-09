<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogMessageBroker\Model\DataMapper;

/**
 * Data mapper for custom options
 */
class CustomOptions implements DataMapperInterface
{
    //Temporary constant that contains product types, for which the options are done and do not require re-mapping.
    //TODO: Remove this once options for all product types are mapped correctly
    private const FINISHED_PRODUCT_TYPES = [
        'configurable'
    ];

    /**
     * @inheritDoc
     */
    public function map(array $data): array
    {
        $productCustomOptions = [];

        if (!empty($data['options'])) {
            if (in_array($data['type'], self::FINISHED_PRODUCT_TYPES)) {
                foreach($data['options'] as $option) {
                    $productCustomOptions[] = $option;

                }
            } else {
                $productSelectableOptions = $data['options'];
                $customOptions = array_filter(
                    $productSelectableOptions,
                    function ($value) {
                        return $value['type'] == 'custom_option';
                    }
                );
                foreach ($customOptions as $customOption) {
                    $customOptionValues = [];
                    foreach ($customOption['values'] as $value) {
                        $customOptionValue = $value;
                        $customOptionValue['title'] = $value['value'];
                        $customOptionValue['option_type_id'] = $value['id'];
                        unset($value['value']);
                        $customOptionValues[$value['id']] = $customOptionValue;
                    }
                    unset($customOption['values']);
                    $customOption['value'] = $customOptionValues;
                    $customOption['type'] = $customOption['render_type'];
                    $customOption['option_id'] = $customOption['id'];
                    $productCustomOptions[] = $customOption;
                }
            }
        }

        if (!empty($data['entered_options'])) {
            $productEnteredOptions = $data['entered_options'];
            foreach ($productEnteredOptions as $customOption) {
                $customOption['title'] = $customOption['value'];
                $customOption['type'] = $customOption['render_type'];
                $customOption['option_id'] = $customOption['id'];
                $customOption['value'] = $customOption;
                $productCustomOptions[] = $customOption;
            }
        }

        return $productCustomOptions;
    }
}
