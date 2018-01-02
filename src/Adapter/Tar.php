<?php

namespace Ions\Compress\Adapter;

use Archive_Tar;
use Ions\Std\AbstractOptions;

/**
 * Class Tar
 * @package Ions\Compress\Adapter
 */
class Tar extends AbstractOptions implements CompressionInterface
{
    /**
     * @var array
     */
    protected $options = [
        'archive' => null,
        'target' => '.',
        'mode' => null,
    ];

    /**
     * Tar constructor.
     * @param null $options
     * @throws \RuntimeException
     */
    public function __construct($options = null)
    {
        if (!class_exists('Archive_Tar')) {
            throw new \RuntimeException(
                'This filter needs PEAR\'s Archive_Tar component. '
                . 'Ensure loading Archive_Tar (registering autoload or require_once)'
            );
        }

        parent::__construct($options);
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
        $archive = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$archive);
        $this->options['archive'] = $archive;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTarget()
    {
        return $this->options['target'];
    }

    /**
     * @param $target
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setTarget($target)
    {
        if (!file_exists(dirname($target))) {
            throw new \InvalidArgumentException("The directory '$target' does not exist");
        }

        $target = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$target);
        $this->options['target'] = $target;
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
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public function setMode($mode)
    {
        $mode = strtolower($mode);
        if (($mode != 'bz2') && ($mode != 'gz')) {
            throw new \InvalidArgumentException("The mode '$mode' is unknown");
        }

        if (($mode == 'bz2') && (!extension_loaded('bz2'))) {
            throw new \RuntimeException('This mode needs the bz2 extension');
        }

        if (($mode == 'gz') && (!extension_loaded('zlib'))) {
            throw new \RuntimeException('This mode needs the zlib extension');
        }

        $this->options['mode'] = $mode;
        return $this;
    }

    /**
     * @param $content
     * @return mixed
     * @throws \RuntimeException
     */
    public function compress($content)
    {
        $archive = new Archive_Tar($this->getArchive(), $this->getMode());
        if (!file_exists($content)) {
            $file = $this->getTarget();
            if (is_dir($file)) {
                $file .= DIRECTORY_SEPARATOR . "tar.tmp";
            }

            $result = file_put_contents($file, $content);
            if ($result === false) {
                throw new \RuntimeException('Error creating the temporary file');
            }

            $content = $file;
        }

        if (is_dir($content)) {
            // collect all file infos
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($content, \RecursiveDirectoryIterator::KEY_AS_PATHNAME),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $directory => $info) {
                if ($info->isFile()) {
                    $file[] = $directory;
                }
            }

            $content = $file;
        }

        $result = $archive->create($content);
        if ($result === false) {
            throw new \RuntimeException('Error creating the Tar archive');
        }

        return $this->getArchive();
    }

    /**
     * @param $content
     * @return mixed|string
     * @throws \RuntimeException
     */
    public function decompress($content)
    {
        $archive = $this->getArchive();
        if (empty($archive) || !file_exists($archive)) {
            throw new \RuntimeException('Tar Archive not found');
        }

        $archive = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, realpath($content));
        $archive = new Archive_Tar($archive, $this->getMode());
        $target = $this->getTarget();
        if (!is_dir($target)) {
            $target = dirname($target) . DIRECTORY_SEPARATOR;
        }

        $result = $archive->extract($target);
        if ($result === false) {
            throw new \RuntimeException('Error while extracting the Tar archive');
        }

        return $target;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return 'Tar';
    }
}
