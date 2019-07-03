<?php

namespace Selective\ImageType\Detector;

use Selective\ImageType\ImageType;
use SplFileObject;

/**
 * Detector.
 */
final class PfmDetector implements DetectorInterface
{
    /**
     * PFM identification.
     *
     * @param SplFileObject $file The image file
     *
     * @return ImageType|null The image type
     */
    public function detect(SplFileObject $file): ?ImageType
    {
        $file->rewind();
        $bytes = strtoupper((string) $file->fread(2));

        return $bytes === "PF" ? new ImageType(ImageType::PFM) : null;
    }
}
