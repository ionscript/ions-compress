<?php

namespace Ions\Compress\Adapter;

/**
 * Class Lzf
 * @package Ions\Compress\Adapter
 */
class Lzf implements CompressionInterface
{
    /**
     * Lzf constructor.
     * @param null $options
     * @throws \RuntimeException
     */
    public function __construct($options = null)
    {
        if (! extension_loaded('lzf')) {
            throw new \RuntimeException('This filter needs the lzf extension');
        }
    }

    /**
     * @param $content
     * @return mixed
     * @throws \RuntimeException
     */
    public function compress($content)
    {
        $compressed = lzf_compress($content);
        if (! $compressed) {
            throw new \RuntimeException('Error during compression');
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
        $compressed = lzf_decompress($content);
        if (! $compressed) {
            throw new \RuntimeException('Error during decompression');
        }

        return $compressed;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return 'Lzf';
    }
}
