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
class SilvercartGraduatedPriceProduct extends DataExtension
{
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
    private static $has_many = [
        'SilvercartGraduatedPrices' => 'SilvercartGraduatedPrice'
    ];
    
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
    public function updatePrice(SilvercartMoney &$price) : void
    {
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
    public function updateFieldLabels(&$labels) : void
    {
        $labels = array_merge(
                $labels,
                [
                    'SilvercartGraduatedPrices' => _t('SilvercartGraduatedPrice.PLURALNAME'),
                ]
        );
    }

    /**
     * Calculates the most convenient price
     * Selects all graduated prices for a customers groups that fit the $quantity.
     * 
     * @return SilvercartGraduatedPrice|false the most convenient price
     */
    public function getGraduatedPriceForCustomersGroups()
    {
        if ($this->graduatedPriceForCustomersGroups === null) {
            $member                          = SilvercartCustomer::currentUser();
            $quantity                        = $this->owner->getProductQuantityInCart();
            $price                           = false;
            $filter                          = [
                'SilvercartProductID' => $this->owner->ID,
            ];
            $this->owner->extend('updateGraduatedPriceFilter', $filter);
            $graduatedPrices                 = SilvercartGraduatedPrice::get()->filter($filter)->where('"minimumQuantity" <= ' . $quantity);
            $graduatedPricesForMembersGroups = ArrayList::create();

            if ($member instanceof Member
             && $member->exists()
             && $graduatedPrices->exists()
            ) {
                foreach ($graduatedPrices as $graduatedPrice) {
                    if ($graduatedPrice->CustomerGroups()
                     && $graduatedPrice->CustomerGroups()->exists()
                     && $member->inGroups($graduatedPrice->CustomerGroups())
                     && $this->isPriceQualified($graduatedPrice, $member)
                    ) {
                        $graduatedPricesForMembersGroups->push($graduatedPrice);
                    }
                }
            } elseif ($graduatedPrices->exists()) {
                foreach ($graduatedPrices as $graduatedPrice) {
                    if ($graduatedPrice->CustomerGroups()
                     && $graduatedPrice->CustomerGroups()->exists()
                     && $graduatedPrice->CustomerGroups()->find('Code', 'anonymous')
                     && $this->isPriceQualified($graduatedPrice)
                    ) {
                        $graduatedPricesForMembersGroups->push($graduatedPrice);
                    }
                }
            }
            $this->owner->extend('updateGraduatedPriceForCustomersGroups', $graduatedPricesForMembersGroups, $member, $quantity);
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
    public function getGraduatedPricesForCustomersGroups()
    {
        if ($this->graduatedPricesForCustomersGroups === null) {
            $member                          = SilvercartCustomer::currentUser();
            $graduatedPricesForMembersGroups = ArrayList::create();
            $filter                          = [
                'SilvercartProductID' => $this->owner->ID,    
            ];
            $this->owner->extend('updateGraduatedPricesFilter', $filter);
            $graduatedPrices = SilvercartGraduatedPrice::get()->filter($filter)->sort('minimumQuantity', 'ASC');

            if ($member instanceof Member
             && $member->exists()
             && $graduatedPrices->exists()
            ) {
                foreach ($graduatedPrices as $graduatedPrice) {
                    if ($graduatedPrice->CustomerGroups()
                     && $graduatedPrice->CustomerGroups()->exists()
                     && $member->inGroups($graduatedPrice->CustomerGroups())
                     && $this->isPriceQualified($graduatedPrice, $member)
                    ) {
                        $graduatedPricesForMembersGroups->push($graduatedPrice);
                    }
                }
            } elseif ($graduatedPrices->exists()) {
                foreach ($graduatedPrices as $graduatedPrice) {
                    if ($graduatedPrice->CustomerGroups()->exists()
                     && $graduatedPrice->CustomerGroups()->find('Code', 'anonymous')
                     && $this->isPriceQualified($graduatedPrice)
                    ) {
                        $graduatedPricesForMembersGroups->push($graduatedPrice);
                    }
                }
            }
            $this->owner->extend('updateGraduatedPricesForCustomersGroups', $graduatedPricesForMembersGroups, $member);
            $this->graduatedPricesForCustomersGroups = $graduatedPricesForMembersGroups->sort('minimumQuantity', 'ASC');
        }
        if ($this->graduatedPricesForCustomersGroups->exists()) {
            return $this->graduatedPricesForCustomersGroups;
        } else {
            return false;
        }
    }
    
    /**
     * Returns whether the given price is qualified for the given member.
     * By default, this will return true.
     * If any extension returns false, the method will return false.
     * 
     * @param SilvercartGraduatedPrice $graduatedPrice Graduated price
     * @param Member                   $member         Member
     * 
     * @return boolean
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 20.12.2017
     */
    public function isPriceQualified(SilvercartGraduatedPrice $graduatedPrice, Member $member = null) : bool
    {
        $isPriceQualified = true;
        $result           = $graduatedPrice->extend('updateIsPriceQualified', $member);
        if (is_array($result)
         && !empty($result)
         && in_array(false, $result, true)
        ) {
            $isPriceQualified = false;
        }
        return $isPriceQualified;
    }
    
    /**
     * A logged in member always has a cart. If this product is inside the cart
     * the positions quantity will be returned. If the product is not in the cart
     * yet 1 will be returned.
     * 
     * @return int
     */
    public function getProductQuantityInCart() : int
    {
        $quantity   = 1;
        $member     = SilvercartCustomer::currentUser();
        if ($member instanceof Member
         && $member->exists()
        ) {
            $whereClause    = sprintf('"SilvercartProductID" = \'%s\' AND "SilvercartShoppingCartID" = \'%s\'', $this->owner->ID, $member->SilvercartShoppingCartID);
            $position       = SilvercartShoppingcartPosition::get()->where($whereClause)->first();
            if ($position instanceof SilvercartShoppingcartPosition
             && $position->exists()
            ) {
                $quantity = (int) $position->Quantity;
            }
        }
        return $quantity;
    }
}