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
        $customerPrice = $this->owner->getGraduatedPriceForCustomersGroups();
        if ($customerPrice) {
                $price = $customerPrice->price;
            }
    }
    
    /**
     * Add the new relations fields to the CMS fields
     *
     * @param FieldSet &$CMSFields the field set passed by reference
     * 
     * @return void
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 04.09.2011
     */
    public function updateCMSFields(FieldSet &$CMSFields) {
        parent::updateCMSFields($CMSFields);
        
        
        $graduatedPricesTab = new Tab('GraduatedPrices');
        $graduatedPricesTab->setTitle(_t('SilvercartGraduatedPrice.PLURALNAME'));
        
        $graduatedPricesTable = new HasManyComplexTableField($this->owner, 'SilvercartGraduatedPrices', 'SilvercartGraduatedPrice', null, null, $sourceFilter = "SilvercartProductID =".$this->owner->ID);
        if (SilvercartConfig::DisplayTypeOfProductAdminFlat()) {
            $CMSFields->removeByName('SilvercartGraduatedPrices');
            $CMSFields->findOrMakeTab('Root.GraduatedPrices', _t('SilvercartGraduatedPrice.PLURALNAME'));
            $CMSFields->addFieldToTab('Root.GraduatedPrices', $graduatedPricesTable);
        } else {
            $mainTab = $CMSFields->findOrMakeTab('Root.Main');
            $mainTab->push($graduatedPricesTab);
            $CMSFields->addFieldToTab('Root.Main.GraduatedPrices', $graduatedPricesTable);
        }
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

