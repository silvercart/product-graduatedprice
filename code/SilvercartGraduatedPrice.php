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
    
    /**
     * sapphire class attributes for ORM
     * 
     * @var array 
     */
    public static $db = array(
        'price'             => 'Money',
        'minimumQuantity'   => 'Int'
    );
    
    /**
     * 1:1 or 1:n relationships.
     *
     * @var array
     */
    public static $has_one = array(
        'SilvercartProduct' => 'SilvercartProduct'
    );
    
    /**
     * n:m relationships.
     *
     * @var array
     */
    public static $many_many = array(
        'CustomerGroups' => 'Group'
    );

    /**
     * cast the return values of methods to attributes
     * 
     * @var array
     */
    public static $casting = array(
        'PriceFormatted'        => 'VarChar(20)',
        'GroupsNamesFormatted'  => 'HTMLText',
        'RelatedGroupIndicator' => 'HTMLText',
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
    
    /**
     * Field labels for display in tables.
     *
     * @param boolean $includerelations A boolean value to indicate if the labels returned include relation fields
     *
     * @return array
     *
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 23.08.2011
     */
    public function fieldLabels($includerelations = true) {
        $fieldLabels = array_merge(
                parent::fieldLabels($includerelations),
                array(
                    'price'             => _t('SilvercartGraduatedPrice.PRICE'),
                    'minimumQuantity'   => _t('SilvercartGraduatedPrice.MINIMUMQUANTITY'),
                    'SilvercartProduct' => _t('SilvercartProduct.SINGULARNAME'),
                    'CustomerGroups'    => _t('Group.PLURALNAME'),
                )
        );
        $this->extend('updateFieldLabels', $fieldLabels);
        return $fieldLabels;
    }
    
    /**
     * Summaryfields for display in tables.
     *
     * @return array
     *
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 23.08.2011
     */
    public function summaryFields() {
        $summaryFields = array(
            'RelatedGroupIndicator' => '&nbsp;',
            'minimumQuantity'       => $this->fieldLabel('minimumQuantity'),
            'PriceFormatted'        => $this->fieldLabel('price'),
            'GroupsNamesFormatted'  => $this->fieldLabel('CustomerGroups'),
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
     */
    public function getCMSFields($params = null) {
        $fields = parent::getCMSFields($params);
        $fields->removeByName('CustomerGroups');
        $productID = $fields->dataFieldByName('SilvercartProductID')->Value();
        $fields->removeByName('SilvercartProductID');
        $fields->insertFirst(new HiddenField('SilvercartProductID', $title = null, $productID));
        if ($this->ID > 0) {
            $groupsTable = new TreeMultiselectField('CustomerGroups', _t('Group.PLURALNAME'));
            $groupsTable->extraClass('customerGroupTreeDropdown');
            $fields->addFieldToTab('Root.' . _t('Group.PLURALNAME'), $groupsTable);
        }
        $this->extend('updateCMSFields', $fields);
        return $fields;
    }
    
    /**
     * Returns the requirements for the ModelAdmins popup
     *
     * @return void
     */
    public function getRequirementsForPopup() {
        Requirements::css('silvercart_product_graduatedprice/css/SilvercartGraduatedPrice.css');
    }
    
    /**
     * Returns the Price formatted by locale.
     *
     * @return string
     */
    public function getPriceFormatted() {
        return $this->price->Nice();
    }
    
    /**
     * helper for summary fields
     * 
     * @return string concatination of all assigned groups names seperated by /
     */
    public function getGroupsNamesFormatted() {
        $groups = $this->CustomerGroups();
        $groupsNamesFormatted = "";
        if ($groups->Count() > 0) {
            $groupsNamesFormatted = implode(' / ', $groups->map('ID', 'Title'));
        } else {
            $groupsNamesFormatted = sprintf(
                '<strong style="color: red;">%s</strong>',
                _t('SilvercartGraduatedPrice.NO_GROUP_RELATED')
            );
        }
        return $groupsNamesFormatted;
    }
    
    /**
     * Checks whether a customer group is related or not. If not, the graduated
     * price is out of function and won't be used in any case. At least one 
     * customer group has to be related to have a working graduated price
     *
     * @return string
     */
    public function getRelatedGroupIndicator() {
        $indicatorColor = 'red';
        if ($this->CustomerGroups()->Count() > 0) {
            $indicatorColor = 'green';
        }
        return sprintf(
                '<div style="background-color: %s;">&nbsp;</div>',
                $indicatorColor
        );
    }
}

