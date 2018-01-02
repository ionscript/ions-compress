<?php

namespace Ions\Compress\Adapter;

/**
 * Class Snappy
 * @package Ions\Compress\Adapter
 */
class Snappy implements CompressionInterface
{
    /**
     * Snappy constructor.
     * @param null $options
     * @throws \RuntimeException
     */
    public function __construct($options = null)
    {
        if (! extension_loaded('snappy')) {
            throw new \RuntimeException('This filter needs the snappy extension');
        }
    }

    /**
     * @param $content
     * @return mixed
     * @throws \RuntimeException
     */
    public function compress($content)
    {
        $compressed = snappy_compress($content);

        if ($compressed === false) {
            throw new \RuntimeException('Error while compressing.');
        }

        return $compressed;
    }

    /**
     * @param $content
     * @return mixed
     * @throws \RuntimeException
     */
    public function decompress($content)
    {
        $compressed = snappy_uncompress($content);

        if ($compressed === false) {
            throw new \RuntimeException('Error while decompressing.');
        }

        return $compressed;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return 'Snappy';
    }
}
