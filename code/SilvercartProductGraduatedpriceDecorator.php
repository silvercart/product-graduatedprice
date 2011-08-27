<?php

/**
 * Decorator for SilvercartProduct
 * Changes the behavior of price getters
 *
 * @package SilverCart
 * @subpackage GraduatedPrices
 * @author Roland Lehmann <rlehmann@pixeltricks.de>
 * @copyright Pixeltricks GmbH
 * @since 03.08.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartProductGraduatedpriceDecorator extends DataObjectDecorator {
    
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
     * decorates the price getter of SilvercartProduct. If it returns a Money if
     * a price range is found for the customer class or false if no price range
     * exists.
     * 
     * @param Money &$price the return value of the decorated method passed by reference
     * 
     * @return void 
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 3.8.2011
     */
    public function updatePrice(Money &$price) {
        $customerPrice = $this->owner->getGraduatedPriceForCustomersGroups();
        $grouplessPrice = $this->owner->getGraduatedPriceWithoutGroup();
        if ($customerPrice) {
            if ($grouplessPrice && $grouplessPrice->price->getAmount() < $customerPrice->price->getAmount()) {
                $price = $grouplessPrice->price;
            } else {
                $price = $customerPrice->price;
            }
        } elseif ($grouplessPrice) {
            $price = $grouplessPrice->price;
        }
    }
    
    /**
     * Calculates the most convenient price
     * Selects all graduated prices for a customers groups that fit the $quantity.
     * 
     * @return SilvercartGraduatedprice|false
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
                    if ($graduatedPrice->CustomerGroups() && $member->inGroups($graduatedPrice->CustomerGroups())) {
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
     * Returns a graduated price without checking the customers groups.
     * Only graduated prices without any group assigned are considered.
     * 
     * @return SilvercartGraduatedprice|false
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 6.8.2011
     */
    public function getGraduatedPriceWithoutGroup() {
        $member = Member::currentUser();
        $quantity = $this->owner->getProductQuantityInCart();
        if ($member) {
            $whereClause = sprintf("`SilvercartProductID` = '%s' AND `minimumQuantity` <= '%d'", $this->owner->ID, $quantity);
            $graduatedPrices = DataObject::get('SilvercartGraduatedPrice', $whereClause, "priceAmount ASC");
            if ($graduatedPrices) {
                return $graduatedPrices->First();
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
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 6.8.2011 
     */
    public function getProductQuantityInCart() {
        $member = Member::currentUser();
        if ($member && $member->getCart()) {
            $whereClause = sprintf("`SilvercartProductID` = '%s' AND `SilvercartShoppingCartID` = '%s'", $this->owner->ID, $member->getCart()->ID);
            $position = DataObject::get_one('SilvercartShoppingcartPosition', $whereClause);
            if ($position) {
                return $position->Quantity;
            }
        }
        return 1;
    }
}

