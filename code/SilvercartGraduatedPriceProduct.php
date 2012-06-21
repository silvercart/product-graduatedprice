<?php
/**
 * Copyright 2012 pixeltricks GmbH
 *
 * This file is part of SilverCart.
 *
 * SilverCart is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SilverCart is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with SilverCart.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Silvercart
 * @subpackage GraduatedPrices
 */

/**
 * Decorator for SilvercartProduct
 * Overwrites the return value of the price getter.
 *
 * @package SilverCart
 * @subpackage GraduatedPrices
 * @author Roland Lehmann <rlehmann@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright pixeltricks GmbH
 * @since 22.05.2012
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartGraduatedPriceProduct extends DataObjectDecorator {
    
    /**
     * adds attributes and relations
     * 
     * @return array
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 3.8.2011 
     */
    public function extraStatics() {
        return array(
            'has_many' => array(
                'SilvercartGraduatedPrices' => 'SilvercartGraduatedPrice'
            )
        );
    }
    
    /**
     * decorates the price getter of SilvercartProduct. It updates a products price if
     * a price range is found for the customer class.
     * 
     * @param Money &$price the return value of the decorated method passed by reference
     * 
     * @return void 
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 3.8.2011
     */
    public function updatePrice(Money &$price) {
        $customerPrice = $this->getGraduatedPriceForCustomersGroups();
        if ($customerPrice) {
            $price = $customerPrice->price;
        }
    }
    
    /**
     * Add the new relations fields to the CMS fields
     *
     * @param FieldSet &$fields the field set passed by reference
     * 
     * @return void
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 04.09.2011
     */
    public function updateCMSFields(FieldSet &$fields) {
        if ($this->owner->ID) {
            $graduatedPrices = $fields->dataFieldByName('SilvercartGraduatedPrices');
            $root = $fields->findOrMakeTab('Root');
            $root->removeByName('SilvercartGraduatedPrices');
            $fields->addFieldToTab('Root.Prices', $graduatedPrices);
        }
    }
    
    /**
     * Updates the field labels
     *
     * @param array &$labels Labels to update
     * 
     * @return void
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 16.05.2012
     */
    public function updateFieldLabels(&$labels) {
        $labels = array_merge(
                $labels,
                array(
                    'SilvercartGraduatedPrices' => _t('SilvercartGraduatedPrice.PLURALNAME'),
                )
        );
    }

    /**
     * Calculates the most convenient price
     * Selects all graduated prices for a customers groups that fit the $quantity.
     * 
     * @return SilvercartGraduatedPrice|false the most convenient price
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 6.8.2011 
     */
    public function getGraduatedPriceForCustomersGroups() {
        $member = Member::currentUser();
        $quantity = $this->owner->getProductQuantityInCart();
        if ($member) {
            $whereClause = sprintf("`SilvercartProductID` = '%s' AND `minimumQuantity` <= '%d'", $this->owner->ID, $quantity);
            $graduatedPrices = DataObject::get('SilvercartGraduatedPrice', $whereClause);
            
            if ($graduatedPrices) {
                $graduatedPricesForMembersGroups = new DataObjectSet();
                foreach ($graduatedPrices as $graduatedPrice) {
                    if ($graduatedPrice->CustomerGroups() &&
                        $graduatedPrice->CustomerGroups()->Count() > 0 &&
                        $member->inGroups($graduatedPrice->CustomerGroups())) {
                        $graduatedPricesForMembersGroups->push($graduatedPrice);
                    }
                }
                if ($graduatedPricesForMembersGroups) {
                    $graduatedPricesForMembersGroups->sort('priceAmount', "ASC");
                    return $graduatedPricesForMembersGroups->First();
                }
            }
        }
        return false;
    }
    
    /**
     * A logged in member always has a cart. If this product is inside the cart
     * the positions quantity will be returned. If the product is not in the cart
     * yet 1 will be returned.
     * 
     * @return integer
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
     * @since 21.06.2012
     */
    public function getProductQuantityInCart() {
        $quantity   = 1;
        $member     = Member::currentUser();
        if ($member &&
            $member->SilvercartShoppingCartID > 0) {
            $whereClause    = sprintf("`SilvercartProductID` = '%s' AND `SilvercartShoppingCartID` = '%s'", $this->owner->ID, $member->SilvercartShoppingCartID);
            $position       = DataObject::get_one('SilvercartShoppingcartPosition', $whereClause);
            if ($position) {
                $quantity = $position->Quantity;
            }
        }
        return $quantity;
    }
    
}

