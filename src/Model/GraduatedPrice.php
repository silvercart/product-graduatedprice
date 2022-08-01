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
use SilverStripe\ORM\Filters\ExactMatchFilter;
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
 *
 * @property DBMoney $price           Price
 * @property int     $minimumQuantity Minimum Quantity
 * @property bool    $isTopseller  Is Topseller
 *
 * @method Product Product() Returns the related product.
 *
 * @method \SilverStripe\ORM\ManyManyList CustomerGroups() Returns the related customer groups.
 */
class GraduatedPrice extends DataObject
{
    use \SilverCart\ORM\ExtensibleDataObject;
    /**
     * DB attributes
     *
     * @var array
     */
    private static $db = [
        'price'             => DBMoney::class,
        'minimumQuantity'   => 'Int',
        'isTopseller'       => 'Boolean(0)',
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
        'PriceFormatted'       => 'Varchar(20)',
        'GroupsNamesFormatted' => DBHTMLText::class,
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
        return $this->defaultFieldLabels($includerelations, [
            'price'           => _t(GraduatedPrice::class . '.PRICE', 'Price'),
            'minimumQuantity' => _t(GraduatedPrice::class . '.MINIMUMQUANTITY', 'Minimum Quantity'),
            'Product'         => Product::singleton()->singular_name(),
            'CustomerGroups'  => Group::singleton()->plural_name(),
            'NoGroupRelated'  => _t(GraduatedPrice::class . '.NO_GROUP_RELATED', 'No related customer group found!'),
            'AddGroup'        => _t(Member::class . '.ADDGROUP', 'Add group'),
        ]);
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
            'minimumQuantity'       => $this->fieldLabel('minimumQuantity'),
            'PriceFormatted'        => $this->fieldLabel('price'),
            'GroupsNamesFormatted'  => $this->fieldLabel('CustomerGroups'),
            'isTopseller'           => $this->fieldLabel('isTopseller'),
        ];
        $this->extend('updateSummaryFields', $summaryFields);
        return $summaryFields;
    }

    /**
     * Returns the searchable fields.
     *
     * @return array
     */
    public function searchableFields() : array
    {
        parent::searchableFields();
        $fields = [
            'price' => [
                'title'  => $this->fieldLabel('price'),
                'filter' => ExactMatchFilter::class
            ],
            'minimumQuantity' => [
                'title'  => $this->fieldLabel('minimumQuantity'),
                'filter' => ExactMatchFilter::class
            ],
            'Product.ProductNumberShop' => [
                'title'  => $this->fieldLabel('Product'),
                'filter' => ExactMatchFilter::class
            ],
            'isTopseller' => [
                'title'  => $this->fieldLabel('isTopseller'),
                'filter' => ExactMatchFilter::class
            ],
        ];
        $this->extend('updateSearchableFields', $fields);
        return $fields;
    }

    /**
     * Returns GridField row CSS classes.
     *
     * @return array
     */
    public function getGridFieldRowClasses() : array
    {
        $classes = [];
        if (!$this->isValidPrice()) {
            $classes[] = 'table-danger';
        }
        return $classes;
    }

    /**
     * Returns whether this is a valid price.
     *
     * @return bool
     */
    public function isValidPrice() : bool
    {
        $is = $this->CustomerGroups()->exists()
           && $this->price->getAmount() > 0;
        $this->extend('updateIsValidPrice', $is);
        return $is;
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
     * @return string|null
     */
    public function getPriceFormatted() : ?string
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
}
