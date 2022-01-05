<?php

namespace SilverCart\GraduatedPrice\Extensions\Forms;

use SilverCart\Model\Product\Product;
use SilverStripe\Core\Extension;

/**
 * Extension for SilverCart AddToCartForm.
 * 
 * @package SilverCart
 * @subpackage GraduatedPrice\Extensions\Forms
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 05.01.2022
 * @copyright 2022 pixeltricks GmbH
 * @license see license file in modules root directory
 * 
 * @property \SilverCart\Forms\AddToCartForm $owner Owner
 */
class AddToCartFormExtension extends Extension
{
    /**
     * Updates the custom fields.
     * 
     * @param array $fields Fields to update
     * 
     * @return void
     */
    public function updateCustomFields(array &$fields) : void
    {
        $product = $this->owner->getProduct();
        if (!$product->getGraduatedPricesForCustomersGroups()->exists()) {
            return;
        }
        foreach ($fields as $field) {
            /* @var $field \SilverStripe\Forms\FormField */
            if ($field->getName() === 'productQuantity') {
                $field->setAttribute('data-prices', $product->getGraduatedPricesForCustomersGroupsJSON());
                break;
            }
        }
    }
}