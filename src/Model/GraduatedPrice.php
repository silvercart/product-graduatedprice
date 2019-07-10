<?php

namespace SilverCart\GraduatedPrice\Model;

use SilverCart\Dev\Tools;
use SilverCart\Model\Product\Product;
use SilverCart\ORM\FieldType\DBMoney;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ListboxField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
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
class GraduatedPrice extends DataObject
{
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
     * Returns all prices for the given $group and $product.
     * 
     * @param Group   $group   Group to get prices for
     * @param Product $product Product to get prices for
     * 
     * @return DataList
     */
    public static function get_by_group(Group $group, Product $product) : DataList
    {
        $tableName = self::config()->table_name;
        $idQuery   = "SELECT DISTINCT ID FROM {$tableName} WHERE ProductID = {$product->ID}";
        $records   = DB::query("SELECT DISTINCT {$tableName}ID AS GraduatedPriceID FROM {$tableName}_CustomerGroups WHERE GroupID = {$group->ID} AND {$tableName}ID IN ({$idQuery})");
        $priceIDs  = array_filter(array_keys($records->map()), function($value) { return $value !== ''; });
        if (empty($priceIDs)) {
            $priceIDs = 0;
        }
        return self::get()->filter('ID', $priceIDs);
    }
    
    /**
     * Returns the translated singular name.
     * 
     * @return string
     */
    public function singular_name() : string
    {
        return Tools::singular_name_for($this);
    }
    
    /**
     * Returns the translated plural name.
     * 
     * @return string
     */
    public function plural_name() : string
    {
        return Tools::plural_name_for($this);
    }
    
    /**
     * Field labels for display in tables.
     *
     * @param bool $includerelations A boolean value to indicate if the labels returned include relation fields
     *
     * @return array
     */
    public function fieldLabels($includerelations = true) : array
    {
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
    public function getCMSFields() : FieldList
    {
        $this->beforeUpdateCMSFields(function(FieldList $fields) {
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
        });
        return parent::getCMSFields();
    }
    
    /**
     * Summaryfields for display in tables.
     *
     * @return array
     */
    public function summaryFields() : array
    {
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
    public function getTitle() : string
    {
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
    public function getPriceFormatted() : string
    {
        return $this->price->Nice();
    }
    
    /**
     * Concatination of all assigned groups names seperated by /
     * 
     * @return DBHTMLText
     */
    public function getGroupsNamesFormatted() : DBHTMLText
    {
        $groups = $this->CustomerGroups();
        $groupsNamesFormatted = "";
        if ($groups->exists()) {
            $groupsNamesFormatted = implode(' / ', $groups->map()->toArray());
        } else {
            $groupsNamesFormatted = "<strong style=\"color: red;\">{$this->fieldLabel('NoGroupRelated')}</strong>";
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
    public function getRelatedGroupIndicator() : DBHTMLText
    {
        $indicatorColor = 'red';
        if ($this->CustomerGroups()->exists()) {
            $indicatorColor = 'green';
        }
        $indicatorColorHtml = "<div style=\"background-color: {$indicatorColor};\">&nbsp;</div>";
        $htmlText = DBHTMLText::create();
        $htmlText->setValue($indicatorColorHtml);
        return $htmlText;
    }
}