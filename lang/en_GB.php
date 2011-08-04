<?php
/**
 * Copyright 2010, 2011 pixeltricks GmbH
 *
 * This file is part of SilverCart_Pricerange.
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
 * English (GB) language pack
 *
 * @package SilvercartPricerange
 * @subpackage i18n
 * @ignore
 */
i18n::include_locale_file('silvercart_pricerange', 'en_US');

global $lang;

if (array_key_exists('en_GB', $lang) && is_array($lang['en_GB'])) {
    $lang['en_GB'] = array_merge($lang['en_US'], $lang['en_GB']);
} else {
    $lang['en_GB'] = $lang['en_US'];
}

$lang['en_GB']['SilvercartPricerange']['PLURALNAME'] = 'price ranges';
$lang['en_GB']['SilvercartPricerange']['SINGULARNAME'] = 'price range';