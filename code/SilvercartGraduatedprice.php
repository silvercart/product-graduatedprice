<?php

/**
 * abstract for price ranges
 * Customers may get a discount depending on the product quantity they bought.
 *
 * @package SilverCart
 * @subpackage GraduatedPrices
 * @author Roland Lehmann <rlehmann@pixeltricks.de>
 * @copyright Pixeltricks GmbH
 * @since 03.08.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartGraduatedPrice extends DataObject {
    
    public static $singular_name = "graduated price";
    public static $plural_name = "graduated prices";
    
    public static $db = array(
        'price' => 'Money', //price for a single position
        'minimumQuantity' => 'Int'
    );
    
    public static $has_one = array(
        'SilvercartProduct' => 'SilvercartProduct'
    );
    
    public static $many_many = array(
        'CustomerGroups' => 'Group'
    );
    
    public static $casting = array(
        'PriceFormatted'       => 'VarChar(20)',
        'GroupsNamesFormatted' => 'VarChar()'
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
        if (_t('SilvercartGraduatedPrice.SINGULARNAME')) {
            return _t('SilvercartGraduatedPrice.SINGULARNAME');
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
        if (_t('SilvercartGraduatedPrice.PLURALNAME')) {
            return _t('SilvercartGraduatedPrice.PLURALNAME');
        } else {
            return parent::plural_name();
        }   
    }
    
    public function fieldLabels() {
        $fieldLabels = array_merge(
                parent::fieldLabels(),
                array(
                    'price' => _t('SilvercartGraduatedprice.PRICE'),
                    'minimumQuantity' => _t('SilvercartGraduatedprice.MINIMUMQUANTITY'),
                    'SilvercartProduct' => _t('SilvercartProduct.SINGULARNAME'),
                    'CustomerGroups' => _t('Group.PLURALNAME')
                    )
                );
        $this->extend('updateFieldLabels', $fieldLabels);
        return $fieldLabels;
    }
    
    public function summaryFields() {
        $summaryFields = array(
            'minimumQuantity'      => _t('SilvercartGraduatedprice.MINIMUMQUANTITY'),
            'PriceFormatted'       => _t('SilvercartGraduatedprice.PRICE'),
            'GroupsNamesFormatted' => _t('Group.PLURALNAME')
            
        );
        $this->extend('updateSummaryFields', $summaryFields);
        return $summaryFields;
    }
    
    /**
     * define CMS fields
     *
     * @param array $params See {@link scaffoldFormFields()}
     *
     * @return FieldSet
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 6.8.2011
     */
    public function getCMSFields($params = null) {
        $fields = parent::getCMSFields($params);
        $fields->removeByName('CustomerGroups');
        $groupsTable = new ManyManyComplexTableField($this, 'CustomerGroups', 'Group');
        $fields->addFieldToTab('Root.' . _t('Group.PLURALNAME'), $groupsTable);
        $this->extend('updateCMSFields', $fields);
        return $fields;
    }
    
    /**
     * Returns the Price formatted by locale.
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 31.01.2011
     */
    public function PriceFormatted() {
        return $this->price->Nice();
    }
    
    /**
     * helper for summary fields
     * 
     * @return string concatination of all assigned groups names seperated by /
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 6.8.2011
     */
    public function GroupsNamesFormatted() {
        $groups = $this->CustomerGroups();
        $groupsNamesFormatted = "";
        if ($groups) {
            foreach ($groups as $group) {
                $groupsNamesFormatted .= $group->getField('Title') . "/";
            }
        }
        return $groupsNamesFormatted;
    }
}

