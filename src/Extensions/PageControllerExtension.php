<?php

namespace SilverCart\GraduatedPrice\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Extension for SilverCart PageController.
 * 
 * @package SilverCart
 * @subpackage GraduatedPrice\Extensions
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @since 28.12.2021
 * @copyright 2021 pixeltricks GmbH
 * @license see license file in modules root directory
 * 
 * @property \SilverCart\Model\Pages\PageController $owner Owner
 */
class PageControllerExtension extends Extension
{
    /**
     * List of already added modals per product.
     * 
     * @var DBHTMLText[]
     */
    protected static $modals = [];
    
    /**
     * Adds a rendered $modal as DBHTMLText.
     * 
     * @param DBHTMLText $modal Rendered modal to add
     * 
     * @return void
     */
    public static function addModal(DBHTMLText $modal) : void
    {
        self::$modals[] = $modal;
    }
    
    /**
     * Updates the extra $content to render in footer.
     * 
     * @param string|DBHTMLText &$content Content to update
     * 
     * @return void
     */
    public function updateFooterCustomHtmlContent(&$content) : void
    {
        if (count(self::$modals) === 0) {
            return;
        }
        $content .= implode('', self::$modals);
    }
}