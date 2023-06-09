<?php

namespace SilverCart\GraduatedPrice\Extensions;

use SilverCart\GraduatedPrice\Model\GraduatedPrice;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Group;

/**
 * Extension for Group. Adds the relation to graduated prices.
 *
 * @package SilverCart
 * @subpackage GraduatedPrice\Extensions
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright pixeltricks GmbH
 * @since 29.05.2018
 * @license see license file in modules root directory
 * 
 * @property Group $owner Owner
 */
class GroupExtension extends DataExtension
{
    /**
     * n:m relationships.
     *
     * @var array
     */
    private static array $belongs_many_many = [
        'GraduatedPrices' => GraduatedPrice::class . '.CustomerGroups',
    ];
}