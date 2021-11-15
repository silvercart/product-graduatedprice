<?php

namespace SilverCart\GraduatedPrice\Extensions\CsvImport\Model;

use SilverCart\GraduatedPrice\Model\GraduatedPrice;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Group;

/**
 * Extension for SilverCart CSV Import
 * 
 * @package SilverCart
 * @subpackage GraduatedPrice\Extensions\CsvImport\Model
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 11.11.2021
 * @copyright 2021 pixeltricks GmbH
 * @license see license file in modules root directory
 * 
 * @property \SilverCart\CsvImport\Model\CsvImport $owner Owner
 */
class CsvImportExtension extends DataExtension
{
    /**
     * DB attributes.
     * 
     * @var string[]
     */
    private static $has_one = [
        'GraduatedPriceDefaultGroup' => Group::class,
    ];
    
    /**
     * Updates the CMS fields.
     * 
     * @param FieldList $fields Fields to update
     * 
     * @return void
     */
    public function updateCMSFields(FieldList $fields) : void
    {
        if ($this->owner->TargetDataObjectName === GraduatedPrice::class) {
            $fields->insertAfter('UseCentPrices', $fields->dataFieldByName('GraduatedPriceDefaultGroupID')->setDescription($this->owner->fieldLabel('GraduatedPriceDefaultGroupDesc')));
        } else {
            $fields->removeByName('GraduatedPriceDefaultGroupID');
        }
    }
}