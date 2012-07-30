<?php
/**
 * Copyright 2012 pixeltricks GmbH
 *
 * This file is part of SilvercartPrepaymentPayment.
 *
 * SilvercartPaypalPayment is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SilvercartPrepaymentPayment is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with SilvercartPrepaymentPayment.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Russian language pack
 *
 * @package SilvercartProductGraduatedprice
 * @subpackage i18n
 * @ignore
 */

i18n::include_locale_file('silvercart_product_graduatedprice', 'en_US');

global $lang;

if (array_key_exists('ru_RU', $lang) && is_array($lang['ru_RU'])) {
    $lang['ru_RU'] = array_merge($lang['en_US'], $lang['ru_RU']);
} else {
    $lang['ru_RU'] = $lang['en_US'];
}

$lang['ru_RU']['SilvercartGraduatedPrice']['PLURALNAME'] = 'Ценовые группы';
$lang['ru_RU']['SilvercartGraduatedPrice']['SINGULARNAME'] = 'Ценовая группа';
$lang['ru_RU']['SilvercartGraduatedPrice']['PRICE'] = 'Цена';
$lang['ru_RU']['SilvercartGraduatedPrice']['MINIMUMQUANTITY'] = 'Минимальное количество';
$lang['ru_RU']['SilvercartGraduatedPrice']['NO_GROUP_RELATED'] = 'Клиентская группа не определена. Минимум одна группа должна быть сделана для установки цены. ';
