<?php

/**
 * Decorates the core class Group
 *
 * @package SilverCart
 * @subpackage silvercart_graduatedprice
 * @author Roland Lehmann <rlehmann@pixeltricks.de>
 * @copyright Pixeltricks GmbH
 * @since 04.08.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartGraduatedpriceGroupDecorator extends DataObjectDecorator {
    
    /**
     * adds attributes and relations
     * 
     * @return array
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 3.8.2011 
     */
    public function extraStatics() {
        return array(
            'belongs_many_many' => array(
                'SilvercartGraduatedPrices' => 'SilvercartGraduatedPrice'
            )
        );
    }
}

