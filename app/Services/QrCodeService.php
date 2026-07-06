<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    public function dataUri(string $content, int $size = 300): string
    {
        return $this->build($content, $size)->getDataUri();
    }

    public function png(string $content, int $size = 300): string
    {
        return $this->build($content, $size)->getString();
    }

    protected function build(string $content, int $size): \Endroid\QrCode\Writer\Result\ResultInterface
    {
        return (new Builder(
            writer: new PngWriter(),
            data: $content,
            size: $size,
            margin: 10,
        ))->build();
    }
}
