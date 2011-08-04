<?php

/**
 * Decorator for SilvercartProduct
 * Changes the behavior of price getters
 *
 * @package SilverCart
 * @subpackage PriceRange
 * @author Roland Lehmann <rlehmann@pixeltricks.de>
 * @copyright Pixeltricks GmbH
 * @since 03.08.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartProductPricerangeDecorator extends DataObjectDecorator {
    
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
                'SilvercartPriceRanges' => 'SilvercartPricerange'
            )
        );
    }
    
    /**
     * decorates the price getter of SilvercartProduct. If it returns a Money if
     * a price range is found for the customer class or false if no price range
     * exists.
     * 
     * @return array|false 
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 3.8.2011
     */
    public function updatePrice(&$quantity) {
        $whereClause = sprintf("`SilvercartProductID` = '%s' AND `minimumQuantity` >= '%d'", $this->owner->ID, $quantity);
        $priceRanges = DataObject::get('SilvercartPricerange', $whereClause, "priceAmount ASC");
        if ($priceRanges) {
            $priceRange = $priceRanges->First();
            if ($priceRange->SilvercartCustomerCategories()) {
                
            }
            $member = Member::currentUser();
        }
        return false;
    }
    

    public function getPricerangesForCustomersCategory($quantity) {
        $member = Member::currentUser();
        if ($member) {
            $whereClause = sprintf("`SilvercartProductID` = '%s' AND `minimumQuantity` >= '%d'", $this->owner->ID, $quantity);
            $priceRanges = DataObject::get('SilvercartPricerange', $whereClause, "priceAmount ASC");
            if ($priceRanges) {
                $priceRange = $priceRanges->First();
                return $priceRange;
            }
        }
        return false;
    }
}

