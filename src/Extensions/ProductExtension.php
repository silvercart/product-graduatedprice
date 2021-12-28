<?php

namespace SilverCart\GraduatedPrice\Extensions;

use SilverCart\GraduatedPrice\Extensions\PageControllerExtension as GraduatedPricePageController;
use SilverCart\GraduatedPrice\Model\GraduatedPrice;
use SilverCart\Model\Customer\Customer;
use SilverCart\Model\Order\ShoppingCartPosition;
use SilverCart\Model\Product\Product;
use SilverCart\ORM\FieldType\DBMoney;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Member;
use SilverStripe\View\ArrayData;

/**
 * Extension for Product.
 * Overwrites the return value of the price getter.
 *
 * @package SilverCart
 * @subpackage GraduatedPrice_Extensions
 * @author Roland Lehmann <rlehmann@pixeltricks.de>,
 *         Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright pixeltricks GmbH
 * @since 29.05.2018
 * @license see license file in modules root directory
 * 
 * @property \SilverCart\Model\Product\Product $owner Owner
 */
class ProductExtension extends DataExtension
{
    /**
     * Cache for method "getGraduatedPriceForCustomersGroups".
     *
     * @var GraduatedPrice[]
     */
    protected $graduatedPriceForCustomersGroups = [];
    /**
     * Cache for method "getGraduatedPricesForCustomersGroups".
     *
     * @var ArrayList[]
     */
    protected $graduatedPricesForCustomersGroups = [];
    /**
     * 1:n relationships.
     *
     * @var array
     */
    private static $has_many = [
        'GraduatedPrices' => GraduatedPrice::class,
    ];
    
    /**
     * decorates the price getter of Product. It updates a products price if
     * a price range is found for the customer class.
     * 
     * @param DBMoney &$price the return value of the decorated method passed by reference
     * 
     * @return void 
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>,
     *         Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 29.05.2018
     */
    public function updatePrice(DBMoney &$price) : void
    {
        $customerPrice = $this->getGraduatedPriceForCustomersGroups();
        if ($customerPrice instanceof GraduatedPrice
         && $customerPrice->exists()
        ) {
            $price = $customerPrice->price;
        }
    }
    
    /**
     * ID list of already rendered price info modals.
     * 
     * @var int[]
     */
    protected $renderedModals = [];


    /**
     * Updates the price nice.
     * 
     * @param DBHTMLText &$priceNice Price nice
     * 
     * @return void
     */
    public function updatePriceNice(&$priceNice) : void
    {
        if (Controller::has_curr()) {
            $ctrl = Controller::curr();
            if ($ctrl->hasMethod('isProductDetailView')
             && $ctrl->isProductDetailView()
             && $ctrl->getDetailViewProduct()->ID === $this->owner->ID
            ) {
                return;
            }
        }
        if ($this->getGraduatedPricesForCustomersGroups()->count() > 1
         || ($this->getGraduatedPricesForCustomersGroups()->count() === 1
          && $this->getGraduatedPricesForCustomersGroups()->first()->minimumQuantity > 1)
        ) {
            $prices    = $this->getGraduatedPricesForCustomersGroups()->map('ID', 'priceAmount')->toArray();
            $minPrice  = min($prices);
            $money     = DBMoney::create()->setAmount($minPrice)->setCurrency($this->owner->getPrice()->getCurrency());
            $priceNice = $this->owner->renderWith(GraduatedPrice::class . '_PriceNice', [
                'MinPriceNice' => _t(Product::class . '.PriceFrom', 'from {price}', ['price' => $money->Nice()]),
            ]);
            if (!in_array($this->owner->ID, $this->renderedModals)) {
                $modal = $this->owner->renderWith(GraduatedPrice::class . '_TableModal', [
                    'MinPriceNice' => _t(Product::class . '.PriceFrom', 'from {price}', ['price' => $money->Nice()]),
                ]);
                $this->renderedModals[] = $this->owner->ID;
                GraduatedPricePageController::addModal($modal);
            }
        }
    }
    
    /**
     * Updates the CMS fields.
     * 
     * @param FieldList $fields Fields to update
     * 
     * @return void
     */
    public function updateCMSFields(FieldList $fields) : void
    {
        if ($this->owner->exists()) {
            $grid = $fields->dataFieldByName('GraduatedPrices');
            /* @var $grid \SilverStripe\Forms\GridField\GridField */
            $config = $grid->getConfig();
            $config->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
        }
    }
    
    /**
     * Updates the field labels
     *
     * @param array &$labels Labels to update
     * 
     * @return void
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.05.2018
     */
    public function updateFieldLabels(&$labels) : void
    {
        $labels = array_merge(
                $labels,
                [
                    'GraduatedPrices' => GraduatedPrice::singleton()->plural_name(),
                ]
        );
    }

    /**
     * Calculates the most convenient price.
     * Selects all graduated prices for a customers groups that fit the $quantity.
     * 
     * @return GraduatedPrice|null
     */
    public function getGraduatedPriceForCustomersGroups() : ?GraduatedPrice
    {
        if (!array_key_exists($this->owner->ID, $this->graduatedPriceForCustomersGroups)) {
            $member   = Customer::currentUser();
            $quantity = $this->owner->getProductQuantityInCart();
            $price    = null;
            $filter   = [
                'ProductID' => $this->owner->ID,
            ];
            $this->owner->extend('updateGraduatedPriceFilter', $filter);
            $graduatedPrices                 = GraduatedPrice::get()->filter($filter)->where('"minimumQuantity" <= ' . $quantity);
            $graduatedPricesForMembersGroups = $this->filterGraduatedPrices($graduatedPrices, $member);

            $this->owner->extend('updateGraduatedPriceForCustomersGroups', $graduatedPricesForMembersGroups, $member, $quantity);
            if ($graduatedPricesForMembersGroups) {
                $price = $graduatedPricesForMembersGroups->sort('minimumQuantity', "DESC")->first();
            }
            $this->graduatedPriceForCustomersGroups[$this->owner->ID] = $price;
        }

        return $this->graduatedPriceForCustomersGroups[$this->owner->ID];
    }

    /**
     * Returns all graduated prices for a customers groups.
     *
     * @return SS_List
     */
    public function getGraduatedPricesForCustomersGroups() : SS_List
    {
        if (!array_key_exists($this->owner->ID, $this->graduatedPricesForCustomersGroups)) {
            $member = Customer::currentUser();
            $filter = ['ProductID' => $this->owner->ID];
            $this->owner->extend('updateGraduatedPricesFilter', $filter);
            $graduatedPrices                 = GraduatedPrice::get()->filter($filter)->sort('minimumQuantity', 'ASC');
            $graduatedPricesForMembersGroups = $this->filterGraduatedPrices($graduatedPrices, $member);
            $this->owner->extend('updateGraduatedPricesForCustomersGroups', $graduatedPricesForMembersGroups, $member);
            $this->graduatedPricesForCustomersGroups[$this->owner->ID] = $graduatedPricesForMembersGroups->sort('minimumQuantity', 'ASC');
        }
        if ($this->graduatedPricesForCustomersGroups[$this->owner->ID]->exists()) {
            return $this->graduatedPricesForCustomersGroups[$this->owner->ID];
        } else {
            return ArrayList::create();
        }
    }
    
    /**
     * Filters the graduated prices by the current Member context.
     * 
     * @param DataList $graduatedPrices Graduated prices
     * @param Member   $member          Member
     * 
     * @return ArrayList
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.05.2018
     */
    protected function filterGraduatedPrices($graduatedPrices, $member) : ArrayList
    {
        $graduatedPricesForMembersGroups = ArrayList::create();
        if ($graduatedPrices->exists()) {
            if ($member instanceof Member
             && $member->exists()
            ) {
                foreach ($graduatedPrices as $graduatedPrice) {
                    if ($graduatedPrice->CustomerGroups()->exists()
                     && $member->inGroups($graduatedPrice->CustomerGroups())
                     && $this->isPriceQualified($graduatedPrice, $member)
                    ) {
                        $graduatedPricesForMembersGroups->push($graduatedPrice);
                    }
                }
            } else {
                foreach ($graduatedPrices as $graduatedPrice) {
                    if ($graduatedPrice->CustomerGroups()->exists()
                     && $graduatedPrice->CustomerGroups()->find('Code', 'anonymous')
                     && $this->isPriceQualified($graduatedPrice)
                    ) {
                        $graduatedPricesForMembersGroups->push($graduatedPrice);
                    }
                }
            }
        }
        return $graduatedPricesForMembersGroups;
    }
    
    /**
     * Returns whether the given price is qualified for the given member.
     * By default, this will return true.
     * If any extension returns false, the method will return false.
     * 
     * @param GraduatedPrice $graduatedPrice Graduated price
     * @param Member         $member         Member
     * 
     * @return bool
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 20.12.2017
     */
    public function isPriceQualified(GraduatedPrice $graduatedPrice, Member $member = null) : bool
    {
        $isPriceQualified = true;
        $result           = $graduatedPrice->extend('updateIsPriceQualified', $member);
        if (is_array($result)
         && !empty($result)
         && in_array(false, $result, true)
        ) {
            $isPriceQualified = false;
        }
        return $isPriceQualified;
    }
    
    /**
     * A logged in member always has a cart. If this product is inside the cart
     * the positions quantity will be returned. If the product is not in the cart
     * yet 1 will be returned.
     * 
     * @return int
     */
    public function getProductQuantityInCart() : int
    {
        $quantity = 1;
        $member   = Customer::currentUser();
        if ($member instanceof Member
         && $member->exists()
        ) {
            $position = ShoppingCartPosition::get()->filter([
                'ProductID'      => $this->owner->ID,
                'ShoppingCartID' => $member->ShoppingCartID,
            ])->first();
            if ($position instanceof ShoppingCartPosition
             && $position->exists()
            ) {
                $quantity = $position->Quantity;
            }
        }
        return $quantity;
    }
    
    /**
     * Adds some additional meta data to render into the product detail page.
     * 
     * @param ArrayList $metaData Existng list of additional meta data to render
     * 
     * @return void
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.05.2018
     */
    public function addPluggedInProductMetaData(ArrayList $metaData) : void
    {
        if (Controller::curr()->hasMethod('isProductDetailView')
         && Controller::curr()->isProductDetailView()
        ) {
            $metaData->push(ArrayData::create([
                'MetaData' => $this->owner->renderWith(GraduatedPrice::class . '_Table'),
            ]));
        }
    }
}