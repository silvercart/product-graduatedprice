<?php
/**
 * Copyright 2015 pixeltricks GmbH
 *
 * This file is part of SilverCart.
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
 * @author Roland Lehmann <rlehmann@pixeltricks.de>,
 *         Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright pixeltricks GmbH
 * @since 06.05.2015
 * @license see license file in modules root directory
 */
class SilvercartGraduatedPriceProduct extends DataExtension {

    /**
     * Cache for method "getGraduatedPriceForCustomersGroups".
     *
     * @var SilvercartGraduatedPrice
     */
    protected $graduatedPriceForCustomersGroups = null;

    /**
     * Cache for method "getGraduatedPricesForCustomersGroups".
     *
     * @var ArrayList
     */
    protected $graduatedPricesForCustomersGroups = null;
    
    /**
     * 1:n relationships.
     *
     * @var array
     */
    public static $has_many = array(
        'SilvercartGraduatedPrices' => 'SilvercartGraduatedPrice'
    );
    
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
    public function updatePrice(SilvercartMoney &$price) {
        $customerPrice = $this->getGraduatedPriceForCustomersGroups();
        if ($customerPrice) {
            $price = $customerPrice->price;
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
     */
    public function getGraduatedPriceForCustomersGroups() {
        if ($this->graduatedPriceForCustomersGroups === null) {
            $member                          = Member::currentUser();
            $quantity                        = $this->owner->getProductQuantityInCart();
            $price                           = false;
            $filter                          = array(
                'SilvercartProductID' => $this->owner->ID,
            );
            $this->owner->extend('updateGraduatedPriceFilter', $filter);
            $graduatedPrices                 = SilvercartGraduatedPrice::get()->filter($filter)->where('"minimumQuantity" <= ' . $quantity);
            $graduatedPricesForMembersGroups = new ArrayList();

            if ($member) {
                if ($graduatedPrices) {
                    foreach ($graduatedPrices as $graduatedPrice) {
                        if ($graduatedPrice->CustomerGroups() &&
                            $graduatedPrice->CustomerGroups()->exists() &&
                            $member->inGroups($graduatedPrice->CustomerGroups())) {

                            $graduatedPricesForMembersGroups->push($graduatedPrice);
                        }
                    }
                }
            } else {
                if ($graduatedPrices) {
                    foreach ($graduatedPrices as $graduatedPrice) {
                        if ($graduatedPrice->CustomerGroups() &&
                            $graduatedPrice->CustomerGroups()->exists()) {

                            if ($graduatedPrice->CustomerGroups()->find('Code', 'anonymous')) {
                                $graduatedPricesForMembersGroups->push($graduatedPrice);
                            }
                        }
                    }
                }
            }
            if ($graduatedPricesForMembersGroups) {
                $price = $graduatedPricesForMembersGroups->sort('minimumQuantity', "DESC")->first();
            }
            $this->graduatedPriceForCustomersGroups = $price;
        }

        return $this->graduatedPriceForCustomersGroups;
    }

    /**
     * Returns all graduated prices for a customers groups.
     *
     * @return ArrayList
     */
    public function getGraduatedPricesForCustomersGroups() {
        if ($this->graduatedPricesForCustomersGroups === null) {
            $member                          = Member::currentUser();
            $graduatedPricesForMembersGroups = new ArrayList();
            $filter                          = array(
                'SilvercartProductID' => $this->owner->ID,    
            );
            $this->owner->extend('updateGraduatedPricesFilter', $filter);
            $graduatedPrices                 = SilvercartGraduatedPrice::get()->filter($filter)->sort('minimumQuantity', 'ASC');

            if ($member) {
                if ($graduatedPrices) {
                    foreach ($graduatedPrices as $graduatedPrice) {
                        if ($graduatedPrice->CustomerGroups() &&
                            $graduatedPrice->CustomerGroups()->exists() &&
                            $member->inGroups($graduatedPrice->CustomerGroups())) {

                            $graduatedPricesForMembersGroups->push($graduatedPrice);
                        }
                    }
                }
            } else {
                if ($graduatedPrices) {
                    foreach ($graduatedPrices as $graduatedPrice) {
                        if ($graduatedPrice->CustomerGroups()->exists()) {

                            if ($graduatedPrice->CustomerGroups()->find('Code', 'anonymous')) {
                                $graduatedPricesForMembersGroups->push($graduatedPrice);
                            }
                        }
                    }
                }
            }
            $this->graduatedPricesForCustomersGroups = $graduatedPricesForMembersGroups;
        }

        if ($this->graduatedPricesForCustomersGroups->exists()) {
            return $this->graduatedPricesForCustomersGroups;
        } else {
            return false;
        }
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
            $member->SilvercartShoppingCart()->isInDB()) {
            $whereClause    = sprintf("\"SilvercartProductID\" = '%s' AND \"SilvercartShoppingCartID\" = '%s'", $this->owner->ID, $member->SilvercartShoppingCartID);
            $position       = DataObject::get_one('SilvercartShoppingcartPosition', $whereClause);
            if ($position) {
                $quantity = $position->Quantity;
            }
        }
        return $quantity;
    }
    
}

