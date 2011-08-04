<?php

/**
 * abstract for price ranges
 * Customers may get a discount depending on the product quantity they bought.
 *
 * @package SilverCart
 * @subpackage PriceRange
 * @author Roland Lehmann <rlehmann@pixeltricks.de>
 * @copyright Pixeltricks GmbH
 * @since 03.08.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartPricerange extends DataObject {
    
    public static $singular_name = "price range";
    public static $plural_name = "price ranges";
    
    public static $db = array(
        'price' => 'Money', //price for a single position
        'minimumQuantity' => 'Int'
    );
    
    public static $has_one = array(
        'SilvercartProduct' => 'SilvercartProduct'
    );
    
    public static $belongs_many_many = array(
        'SilvercartCustomerCategories' => 'SilvercartCustomerCategory'
    );

        /**
     * Returns the translated singular name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string The objects singular name 
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 5.7.2011
     */
    public function singular_name() {
        if (_t('SilvercartPricerange.SINGULARNAME')) {
            return _t('SilvercartPricerange.SINGULARNAME');
        } else {
            return parent::singular_name();
        } 
    }
    
    /**
     * Returns the translated plural name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string the objects plural name
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 5.7.2011 
     */
    public function plural_name() {
        if (_t('SilvercartPricerange.PLURALNAME')) {
            return _t('SilvercartPricerange.PLURALNAME');
        } else {
            return parent::plural_name();
        }   
    }
    
}

