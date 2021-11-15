<?php

use SilverCart\CsvImport\Dev\CsvBulkLoader;
use SilverCart\CsvImport\Model\CsvImport;
use SilverCart\GraduatedPrice\Extensions\CsvImport\Dev\CsvBulkLoaderExtension;
use SilverCart\GraduatedPrice\Extensions\CsvImport\Model\CsvImportExtension;

if (class_exists(CsvImport::class)) {
    CsvImport::add_extension(CsvImportExtension::class);
    CsvBulkLoader::add_extension(CsvBulkLoaderExtension::class);
}