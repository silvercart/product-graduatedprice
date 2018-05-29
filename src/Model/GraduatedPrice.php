<?php

namespace SilverCart\GraduatedPrice\Model;

use SilverCart\Dev\Tools;
use SilverCart\Model\Product\Product;
use SilverCart\ORM\FieldType\DBMoney;
use SilverStripe\Forms\ListboxField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;

/**
 * abstract for price ranges
 * Customers may get a discount depending on the product quantity they bought.
 *
 * @package SilverCart
 * @subpackage GraduatedPrice_Model
 * @author Roland Lehmann <rlehmann@pixeltricks.de>,
 *         Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright pixeltricks GmbH
 * @since 29.05.2018
 * @license see license file in modules root directory
 */
class GraduatedPrice extends DataObject {
    
    /**
     * DB attributes
     * 
     * @var array 
     */
    private static $db = [
        'price'             => DBMoney::class,
        'minimumQuantity'   => 'Int',
    ];
    
    /**
     * 1:1 or 1:n relationships.
     *
     * @var array
     */
    private static $has_one = [
        'Product' => Product::class,
    ];
    
    /**
     * n:m relationships.
     *
     * @var array
     */
    private static $many_many = [
        'CustomerGroups' => Group::class,
    ];

    /**
     * cast the return values of methods to attributes
     * 
     * @var array
     */
    private static $casting = [
        'PriceFormatted'        => 'Varchar(20)',
        'GroupsNamesFormatted'  => DBHTMLText::class,
        'RelatedGroupIndicator' => DBHTMLText::class,
    ];
    
    /**
     * DB table name
     *
     * @var string
     */
    private static $table_name = 'SilvercartGraduatedPrice';

    /**
     * Returns the translated singular name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string The objects singular name 
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.05.2018
     */
    public function singular_name() {
        return Tools::singular_name_for($this);
    }
    
    /**
     * Returns the translated plural name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string the objects plural name
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.05.2018
     */
    public function plural_name() {
        return Tools::plural_name_for($this);
    }
    
    /**
     * Field labels for display in tables.
     *
     * @param boolean $includerelations A boolean value to indicate if the labels returned include relation fields
     *
     * @return array
     *
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 23.08.2011
     */
    public function fieldLabels($includerelations = true) {
        $fieldLabels = array_merge(
                parent::fieldLabels($includerelations),
                Tools::field_labels_for(static::class),
                [
                    'price'           => _t(GraduatedPrice::class . '.PRICE', 'Price'),
                    'minimumQuantity' => _t(GraduatedPrice::class . '.MINIMUMQUANTITY', 'Minimum Quantity'),
                    'Product'         => Product::singleton()->singular_name(),
                    'CustomerGroups'  => Group::singleton()->plural_name(),
                    'NoGroupRelated'  => _t(GraduatedPrice::class . '.NO_GROUP_RELATED', 'No related customer group found!'),
                    'AddGroup'        => _t(Member::class . '.ADDGROUP', 'Add group'),
                ]
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
        
        $groupsMap = [];
        foreach (Group::get() as $group) {
            // Listboxfield values are escaped, use ASCII char instead of &raquo;
            $groupsMap[$group->ID] = $group->getBreadcrumbs(' > ');
        }
        asort($groupsMap);
        $fields->addFieldToTab('Root.Main',
            ListboxField::create('CustomerGroups', $this->fieldLabel('CustomerGroups'))
                ->setSource($groupsMap)
                ->setAttribute(
                    'data-placeholder', 
                    $this->fieldLabel('AddGroup')
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
     * @since 23.08.2011
     */
    public function summaryFields() {
        $summaryFields = [
            'RelatedGroupIndicator' => '&nbsp;',
            'minimumQuantity'       => $this->fieldLabel('minimumQuantity'),
            'PriceFormatted'        => $this->fieldLabel('price'),
            'GroupsNamesFormatted'  => $this->fieldLabel('CustomerGroups'),
        ];
        $this->extend('updateSummaryFields', $summaryFields);
        return $summaryFields;
    }
    
    /**
     * Returns a generic title to display in backend breadcrumbs.
     * 
     * @return string
     */
    public function getTitle() {
        $title  = $this->price->Nice();
        $title .= ' | ' . $this->fieldLabel('minimumQuantity') . ': ' . $this->minimumQuantity;
        if ($this->CustomerGroups()->exists()) {
            $title .= ' | ' . implode(',', $this->CustomerGroups()->map()->toArray());
        }
        return $title;
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
                $this->fieldLabel('NoGroupRelated')
            );
        }
        $htmlText = DBHTMLText::create();
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
        $htmlText = DBHTMLText::create();
        $htmlText->setValue($indicatorColorHtml);
        return $htmlText;
    }
}

