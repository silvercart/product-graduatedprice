<?php

namespace SilverCart\GraduatedPrice\Extensions;

use SilverCart\GraduatedPrice\Model\GraduatedPrice;
use SilverStripe\ORM\DataExtension;

/**
 * Extension for Group. Adds the relation to graduated prices.
 *
 * @package SilverCart
 * @subpackage GraduatedPrice_Extensions
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright pixeltricks GmbH
 * @since 29.05.2018
 * @license see license file in modules root directory
 */
class GroupExtension extends DataExtension
{
    /**
     * n:m relationships.
     *
     * @var array
     */
    private static $belongs_many_many = [
        'GraduatedPrices' => GraduatedPrice::class,
    ];
}