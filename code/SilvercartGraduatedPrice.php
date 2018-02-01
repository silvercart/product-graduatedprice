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
 * abstract for price ranges
 * Customers may get a discount depending on the product quantity they bought.
 *
 * @package SilverCart
 * @subpackage GraduatedPrices
 * @author Roland Lehmann <rlehmann@pixeltricks.de>,
 *         Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright pixeltricks GmbH
 * @since 06.05.2015
 * @license see license file in modules root directory
 */
class SilvercartGraduatedPrice extends DataObject {
    
    /**
     * sapphire class attributes for ORM
     * 
     * @var array 
     */
    public static $db = array(
        'price'             => 'SilvercartMoney',
        'minimumQuantity'   => 'Int',
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
                    'price'             => _t('SilvercartGraduatedPrice.PRICE', 'Price'),
                    'minimumQuantity'   => _t('SilvercartGraduatedPrice.MINIMUMQUANTITY', 'Minimum Quantity'),
                    'SilvercartProduct' => SilvercartProduct::singleton()->singular_name(),
                    'CustomerGroups'    => Group::singleton()->plural_name(),
                )
        );
        $this->extend('updateFieldLabels', $fieldLabels);
        return $fieldLabels;
    }
    
    /**
     * Returns the CMS fields.
     * 
     * @return FieldList
     */
    public function getCMSFields() {
        $fields = parent::getCMSFields();
        
        $fields->removeByName('CustomerGroups');
        
        $groupsMap = array();
        foreach (Group::get() as $group) {
            // Listboxfield values are escaped, use ASCII char instead of &raquo;
            $groupsMap[$group->ID] = $group->getBreadcrumbs(' > ');
        }
        asort($groupsMap);
        $fields->addFieldToTab('Root.Main',
            ListboxField::create('CustomerGroups', $this->fieldLabel('CustomerGroups'))
                ->setMultiple(true)
                ->setSource($groupsMap)
                ->setAttribute(
                    'data-placeholder', 
                    _t('Member.ADDGROUP', 'Add group', 'Placeholder text for a dropdown')
                )
        );
        
        return $fields;
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
        if ($groups->exists()) {
            $groupsNamesFormatted = implode(' / ', $groups->map()->toArray());
        } else {
            $groupsNamesFormatted = sprintf(
                '<strong style="color: red;">%s</strong>',
                _t('SilvercartGraduatedPrice.NO_GROUP_RELATED')
            );
        }
        $htmlText = HTMLText::create();
        $htmlText->setValue($groupsNamesFormatted);
        return $htmlText;
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
        if ($this->CustomerGroups()->exists()) {
            $indicatorColor = 'green';
        }
        $indicatorColorHtml = sprintf(
                '<div style="background-color: %s;">&nbsp;</div>',
                $indicatorColor
        );
        $htmlText = HTMLText::create();
        $htmlText->setValue($indicatorColorHtml);
        return $htmlText;
    }
}

