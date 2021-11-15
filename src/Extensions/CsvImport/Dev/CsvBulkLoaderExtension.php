<?php

namespace SilverCart\GraduatedPrice\Extensions\CsvImport\Dev;

use SilverCart\CsvImport\Model\CsvImport;
use SilverCart\GraduatedPrice\Model\GraduatedPrice;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\BulkLoader_Result;
use SilverStripe\Security\Group;

/**
 * Extension for SilverCart CsvBulkLoader.
 *
 * @package SilverCart
 * @subpackage GraduatedPrice\Extensions\CsvImport\Dev
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 11.11.2021
 * @copyright 2021 Sebastian Diel
 * @license see license file in modules root directory
 * 
 * @property \SilverCart\CsvImport\Dev\CsvBulkLoader $owner Owner
 */
class CsvBulkLoaderExtension extends Extension
{
    /**
     * Adds the default customer Group.
     * 
     * @param array             &$record   Record
     * @param array             $columnMap Column map
     * @param BulkLoader_Result $results   Results
     * @param bool              $preview   Is preview?
     * 
     * @return void
     */
    public function updateProcessRecord(array &$record, array &$columnMap, BulkLoader_Result &$results, bool &$preview) : void
    {
        $csvImport = $this->owner->getImportContext();
        if ($csvImport instanceof CsvImport
         && $csvImport->GraduatedPriceDefaultGroup()->exists()
        ) {
            $columnMap['GraduatedPriceDefaultGroupID'] = '->importGraduatedPriceDefaultGroupByID';
            $record['GraduatedPriceDefaultGroupID']    = $csvImport->GraduatedPriceDefaultGroup()->ID;
        }
    }
    
    /**
     * Imports a customer group by ID.
     * 
     * @param GraduatedPrice $price     GraduatedPrice object
     * @param string         $value     ID Value
     * @param array          $csvRecord CSV record
     * 
     * @return void
     */
    public function importGraduatedPriceDefaultGroupByID(GraduatedPrice $price, string $value, array $csvRecord) : void
    {
        $group = Group::get()->byID($value);
        if ($group instanceof Group) {
            $price->CustomerGroups()->add($group);
        }
    }
}