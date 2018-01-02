<?php

namespace Ions\Compress\Adapter;

use Ions\Std\AbstractOptions;

/**
 * Class Gz
 * @package Ions\Compress\Adapter
 */
class Gz extends AbstractOptions implements CompressionInterface
{
    /**
     * @var array
     */
    protected $options = [
        'level'   => 9,
        'mode'    => 'compress',
        'archive' => null,
    ];

    /**
     * Gz constructor.
     * @param null $options
     * @throws \RuntimeException
     */
    public function __construct($options = null)
    {
        if (! extension_loaded('zlib')) {
            throw new \RuntimeException('This filter needs the zlib extension');
        }
        parent::__construct($options);
    }

    /**
     * @return mixed
     */
    public function getLevel()
    {
        return $this->options['level'];
    }

    /**
     * @param $level
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setLevel($level)
    {
        if ($level < 0 || $level > 9) {
            throw new \InvalidArgumentException('Level must be between 0 and 9');
        }

        $this->options['level'] = (int) $level;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMode()
    {
        return $this->options['mode'];
    }

    /**
     * @param $mode
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setMode($mode)
    {
        if ($mode != 'compress' && $mode != 'deflate') {
            throw new \InvalidArgumentException('Given compression mode not supported');
        }

        $this->options['mode'] = $mode;
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
     * @return bool|string
     * @throws \RuntimeException
     */
    public function compress($content)
    {
        $archive = $this->getArchive();
        if (! empty($archive)) {
            $file = gzopen($archive, 'w' . $this->getLevel());
            if (! $file) {
                throw new \RuntimeException("Error opening the archive '" . $this->options['archive'] . "'");
            }

            gzwrite($file, $content);
            gzclose($file);
            $compressed = true;
        } elseif ($this->options['mode'] == 'deflate') {
            $compressed = gzdeflate($content, $this->getLevel());
        } else {
            $compressed = gzcompress($content, $this->getLevel());
        }

        if (! $compressed) {
            throw new \RuntimeException('Error during compression');
        }

        return $compressed;
    }

    /**
     * @param $content
     * @return string
     * @throws \RuntimeException
     */
    public function decompress($content)
    {
        $archive = $this->getArchive();
        $mode    = $this->getMode();

        //check if there are null byte characters before doing a file_exists check
        if (false === strpos($content, "\0") && file_exists($content)) {
            $archive = $content;
        }

        if (file_exists($archive)) {
            $handler = fopen($archive, 'rb');
            if (! $handler) {
                throw new \RuntimeException("Error opening the archive '" . $archive . "'");
            }

            fseek($handler, -4, SEEK_END);
            $packet = fread($handler, 4);
            $bytes  = unpack('V', $packet);
            $size   = end($bytes);
            fclose($handler);

            $file       = gzopen($archive, 'r');
            $compressed = gzread($file, $size);
            gzclose($file);
        } elseif ($mode === 'deflate') {
            $compressed = gzinflate($content);
        } else {
            $compressed = gzuncompress($content);
        }

        if ($compressed === false) {
            throw new \RuntimeException('Error during decompression');
        }

        return $compressed;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return 'Gz';
    }
}
