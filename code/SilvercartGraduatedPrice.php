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
 * abstract for price ranges
 * Customers may get a discount depending on the product quantity they bought.
 *
 * @package SilverCart
 * @subpackage GraduatedPrices
 * @author Roland Lehmann <rlehmann@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright pixeltricks GmbH
 * @since 22.05.2012
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
     * @author Roland Lehmann <rlehmann@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
     * @since 22.05.2012
     */
    public function singular_name() {
        return SilvercartTools::singular_name_for($this);
    }
    
    /**
     * Returns the translated plural name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string the objects plural name
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
     * @since 22.05.2012
     */
    public function plural_name() {
        return SilvercartTools::plural_name_for($this);
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
        $fields = parent::getCMSFields(
                array_merge(
                        array(
                            'fieldClasses' => array(
                                'price' => 'SilvercartMoneyField',
                            ),
                        ),
                        (array)$params
                )
        );
        $fields->removeByName('CustomerGroups');
        $productID = $fields->dataFieldByName('SilvercartProductID')->Value();
        $fields->removeByName('SilvercartProductID');
        $fields->insertFirst(new HiddenField('SilvercartProductID', $title = null, $productID));
        if ($this->ID > 0) {
            $groupsTable = new TreeMultiselectField('CustomerGroups', _t('Group.PLURALNAME'));
            $groupsTable->extraClass('customerGroupTreeDropdown');
            $fields->findOrMakeTab('Root.CustomerGroups', $this->fieldLabel('CustomerGroups'));
            $fields->addFieldToTab('Root.CustomerGroups', $groupsTable);
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

