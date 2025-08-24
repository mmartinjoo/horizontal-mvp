<?php

namespace App\Services;

use Generator;
use PrinsFrank\PdfParser\PdfParser as VendorPdfParser;

class PdfParser
{
    private VendorPdfParser $parser;

    public function __construct()
    {
        $this->parser = new VendorPdfParser();
    }

    public function stream(string $path): Generator
    {
        $document = $this->parser->parseFile(storage_path('app/private/' . $path));
        foreach ($document->getPages() as $page) {
            yield $page->getText();
        }
    }
}
