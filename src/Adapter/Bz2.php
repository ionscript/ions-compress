<?php

namespace Ions\Compress\Adapter;

use Ions\Std\AbstractOptions;

/**
 * Class Bz2
 * @package Ions\Compress\Adapter
 */
class Bz2 extends AbstractOptions implements CompressionInterface
{
    /**
     * Bz2 constructor.
     * @param null $options
     * @throws \RuntimeException
     */
    public function __construct($options = null)
    {
        if (! extension_loaded('bz2')) {
            throw new \RuntimeException('This filter needs the bz2 extension');
        }
        parent::__construct($options);
    }

    /**
     * @return mixed
     */
    public function getBlocksize()
    {
        return $this->options['blocksize'];
    }

    /**
     * @param $blocksize
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setBlocksize($blocksize)
    {
        if ($blocksize < 0 || $blocksize > 9) {
            throw new \InvalidArgumentException('Blocksize must be between 0 and 9');
        }

        $this->options['blocksize'] = (int) $blocksize;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getArchive()
    {
        return $this->options['archive'];
    }

    /**
     * @param $archive
     * @return $this
     */
    public function setArchive($archive)
    {
        $this->options['archive'] = (string) $archive;
        return $this;
    }

    /**
     * @param $content
     * @return bool
     * @throws \RuntimeException
     */
    public function compress($content)
    {
        $archive = $this->getArchive();
        if (! empty($archive)) {
            $file = bzopen($archive, 'w');
            if (! $file) {
                throw new \RuntimeException("Error opening the archive '" . $archive . "'");
            }

            bzwrite($file, $content);
            bzclose($file);
            $compressed = true;
        } else {
            $compressed = bzcompress($content, $this->getBlocksize());
        }

        if (is_int($compressed)) {
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
        $archive = $this->getArchive();

        //check if there are null byte characters before doing a file_exists check
        if (! strstr($content, "\0") && file_exists($content)) {
            $archive = $content;
        }

        if (file_exists($archive)) {
            $file = bzopen($archive, 'r');
            if (! $file) {
                throw new \RuntimeException("Error opening the archive '" . $content . "'");
            }

            $compressed = bzread($file);
            bzclose($file);
        } else {
            $compressed = bzdecompress($content);
        }

        if (is_int($compressed)) {
            throw new \RuntimeException('Error during decompression');
        }

        return $compressed;
    }

    public function toString()
    {
        return 'Bz2';
    }
}
