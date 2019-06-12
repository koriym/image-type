<?php

namespace Selective\ImageType;

use SplFileInfo;
use SplFileObject;

/**
 * Image type detection.
 */
final class ImageTypeDetector
{
    /**
     * Detect image type.
     *
     * @param SplFileInfo $file The image file
     *
     * @throws ImageTypeException
     *
     * @return string The image type
     */
    public function getImageTypeFromFile(SplFileInfo $file): string
    {
        $realFile = $file->getRealPath();
        if ($realFile === false) {
            throw new ImageTypeException(sprintf('Image file could not be found: %s', $file->getPath()));
        }

        $stream = new SplFileObject($realFile);
        $type = $this->parseType($stream);
        unset($stream);

        if ($type === null) {
            throw new ImageTypeException(sprintf('Image type could not be detected: %s', $file->getRealPath()));
        }

        return $type;
    }

    /**
     * Reads and returns the type of the image.
     *
     * @param SplFileObject $file The image file
     *
     * @return string|null
     */
    private function parseType(SplFileObject $file): ?string
    {
        foreach ($this->getDetectors() as $detector) {
            $type = $detector($file);

            if ($type !== null) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Get array with detectors.
     *
     * @return array The detector callbacks
     */
    private function getDetectors(): array
    {
        return [
            function (SplFileObject $file) {
                return $this->detectBasicTypes($file);
            },
            function (SplFileObject $file) {
                return $this->detectPng($file);
            },
            function (SplFileObject $file) {
                return $this->detectWebp($file);
            },
            function (SplFileObject $file) {
                return $this->detectSvg($file);
            },
            function (SplFileObject $file) {
                return $this->detectIcoAndCur($file);
            },
        ];
    }

    /**
     * Simple image detection.
     *
     * @param SplFileObject $file The image file
     *
     * @return string|null The image type
     */
    private function detectBasicTypes(SplFileObject $file): ?string
    {
        $file->rewind();
        $bytes = $file->fread(2);

        // Mapping
        $magicBytes = [
            'BM' => 'bmp',
            'GI' => 'gif',
            chr(0xFF) . chr(0xd8) => 'jpeg',
            '8B' => 'psd',
            'II' => 'tiff',
            'MM' => 'tiff',
        ];

        if (isset($magicBytes[$bytes])) {
            return (string)$magicBytes[$bytes];
        }

        return null;
    }

    /**
     * Image detection.
     *
     * @param SplFileObject $file The image file
     *
     * @return string|null The image type
     */
    private function detectPng(SplFileObject $file): ?string
    {
        $file->rewind();

        return $file->fread(4) === chr(0x89) . 'PNG' ? 'png' : null;
    }

    /**
     * Detect ICO and CUR file format.
     *
     * @param SplFileObject $file The image file
     *
     * @return string|null The image type
     */
    private function detectIcoAndCur(SplFileObject $file): ?string
    {
        $file->rewind();
        $bytes = $file->fread(3);

        if ($bytes === "\0\0\1") {
            return 'ico';
        }

        if ($bytes === "\0\0\2") {
            return 'cur';
        }

        return null;
    }

    /**
     * Image detection.
     *
     * @param SplFileObject $file The image file
     *
     * @return string|null The image type
     */
    private function detectWebp(SplFileObject $file): ?string
    {
        $file->rewind();
        $bytes = $file->fread(12) ?: '';

        return substr($bytes, 8, 4) === 'WEBP' ? 'webp' : null;
    }

    /**
     * Image detection.
     *
     * @param SplFileObject $file The image file
     *
     * @return string|null The image type
     */
    private function detectSvg(SplFileObject $file): ?string
    {
        $file->rewind();
        $bytes = $file->fread(4) ?: '';

        return strtolower($bytes) === '<svg' ? 'svg' : null;
    }
}
