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
     * @return array|false 
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 3.8.2011
     */
    public function updatePrice(&$quantity) {
        $whereClause = sprintf("`SilvercartProductID` = '%s' AND `minimumQuantity` >= '%d'", $this->owner->ID, $quantity);
        $graduatedPrices = DataObject::get('SilvercartGraduatedPrice', $whereClause, "priceAmount ASC");
        if ($graduatedPrices) {
            $graduatedPrice = $graduatedPrices->First();
            if (false) {
                
            }
            $member = Member::currentUser();
        }
        return false;
    }
    

    public function getGraduatedPricesForCustomersGroups($quantity) {
        $member = Member::currentUser();
        if ($member) {
            $whereClause = sprintf("`SilvercartProductID` = '%s' AND `minimumQuantity` >= '%d'", $this->owner->ID, $quantity);
            $graduatedPrices = DataObject::get('SilvercartGraduatedPrice', $whereClause, "priceAmount ASC");
            if ($graduatedPrices) {
                $graduatedPrice = $graduatedPrices->First();
                return $graduatedPrice;
            }
        }
        return false;
    }
}

