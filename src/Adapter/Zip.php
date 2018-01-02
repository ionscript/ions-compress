<?php

namespace Ions\Compress\Adapter;

use ZipArchive;
use Ions\Std\AbstractOptions;

/**
 * Class Zip
 * @package Ions\Compress\Adapter
 */
class Zip extends AbstractOptions implements CompressionInterface
{
    /**
     * @var array
     */
    protected $options = [
        'archive' => null,
        'target'  => null,
    ];

    /**
     * Zip constructor.
     * @param null $options
     * @throws \RuntimeException
     */
    public function __construct($options = null)
    {
        if (! extension_loaded('zip')) {
            throw new \RuntimeException('This filter needs the zip extension');
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
        $archive = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $archive);
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
        if (! file_exists(dirname($target))) {
            throw new \InvalidArgumentException("The directory '$target' does not exist");
        }

        $target = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $target);
        $this->options['target'] = $target;
        return $this;
    }

    /**
     * @param $content
     * @return mixed
     * @throws \RuntimeException
     */
    public function compress($content)
    {
        $zip = new ZipArchive();
        $res = $zip->open($this->getArchive(), ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($res !== true) {
            throw new \RuntimeException($this->errorString($res));
        }

        if (file_exists($content)) {
            $content  = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, realpath($content));
            $basename = substr($content, strrpos($content, DIRECTORY_SEPARATOR) + 1);
            if (is_dir($content)) {
                $index    = strrpos($content, DIRECTORY_SEPARATOR) + 1;
                $content .= DIRECTORY_SEPARATOR;
                $stack    = [$content];
                while (! empty($stack)) {
                    $current = array_pop($stack);
                    $files   = [];

                    $dir = dir($current);
                    while (false !== ($node = $dir->read())) {
                        if (($node == '.') || ($node == '..')) {
                            continue;
                        }

                        if (is_dir($current . $node)) {
                            array_push($stack, $current . $node . DIRECTORY_SEPARATOR);
                        }

                        if (is_file($current . $node)) {
                            $files[] = $node;
                        }
                    }

                    $local = substr($current, $index);
                    $zip->addEmptyDir(substr($local, 0, -1));

                    foreach ($files as $file) {
                        $zip->addFile($current . $file, $local . $file);
                        if ($res !== true) {
                            throw new \RuntimeException($this->errorString($res));
                        }
                    }
                }
            } else {
                $res = $zip->addFile($content, $basename);
                if ($res !== true) {
                    throw new \RuntimeException($this->errorString($res));
                }
            }
        } else {
            $file = $this->getTarget();
            if (! is_dir($file)) {
                $file = basename($file);
            } else {
                $file = "zip.tmp";
            }

            $res = $zip->addFromString($file, $content);
            if ($res !== true) {
                throw new \RuntimeException($this->errorString($res));
            }
        }

        $zip->close();
        return $this->options['archive'];
    }

    /**
     * @param $content
     * @return mixed|string
     * @throws \RuntimeException
     */
    public function decompress($content)
    {
        $archive = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, realpath($content));

        if (empty($archive) || ! file_exists($archive)) {
            throw new \RuntimeException('ZIP Archive not found');
        }

        $zip     = new ZipArchive();
        $res     = $zip->open($archive);

        $target = $this->getTarget();
        if (! empty($target) && ! is_dir($target)) {
            $target = dirname($target);
        }

        if (! empty($target)) {
            $target = rtrim($target, '/\\') . DIRECTORY_SEPARATOR;
        }

        if (empty($target) || ! is_dir($target)) {
            throw new \RuntimeException('No target for ZIP decompression set');
        }

        if ($res !== true) {
            throw new \RuntimeException($this->errorString($res));
        }

        $res = $zip->extractTo($target);
        if ($res !== true) {
            throw new \RuntimeException($this->errorString($res));
        }

        $zip->close();
        return $target;
    }

    /**
     * @param $error
     * @return string
     */
    public function errorString($error)
    {
        switch ($error) {
            case ZipArchive::ER_MULTIDISK:
                return 'Multidisk ZIP Archives not supported';

            case ZipArchive::ER_RENAME:
                return 'Failed to rename the temporary file for ZIP';

            case ZipArchive::ER_CLOSE:
                return 'Failed to close the ZIP Archive';

            case ZipArchive::ER_SEEK:
                return 'Failure while seeking the ZIP Archive';

            case ZipArchive::ER_READ:
                return 'Failure while reading the ZIP Archive';

            case ZipArchive::ER_WRITE:
                return 'Failure while writing the ZIP Archive';

            case ZipArchive::ER_CRC:
                return 'CRC failure within the ZIP Archive';

            case ZipArchive::ER_ZIPCLOSED:
                return 'ZIP Archive already closed';

            case ZipArchive::ER_NOENT:
                return 'No such file within the ZIP Archive';

            case ZipArchive::ER_EXISTS:
                return 'ZIP Archive already exists';

            case ZipArchive::ER_OPEN:
                return 'Can not open ZIP Archive';

            case ZipArchive::ER_TMPOPEN:
                return 'Failure creating temporary ZIP Archive';

            case ZipArchive::ER_ZLIB:
                return 'ZLib Problem';

            case ZipArchive::ER_MEMORY:
                return 'Memory allocation problem while working on a ZIP Archive';

            case ZipArchive::ER_CHANGED:
                return 'ZIP Entry has been changed';

            case ZipArchive::ER_COMPNOTSUPP:
                return 'Compression method not supported within ZLib';

            case ZipArchive::ER_EOF:
                return 'Premature EOF within ZIP Archive';

            case ZipArchive::ER_INVAL:
                return 'Invalid argument for ZLIB';

            case ZipArchive::ER_NOZIP:
                return 'Given file is no zip archive';

            case ZipArchive::ER_INTERNAL:
                return 'Internal error while working on a ZIP Archive';

            case ZipArchive::ER_INCONS:
                return 'Inconsistent ZIP archive';

            case ZipArchive::ER_REMOVE:
                return 'Can not remove ZIP Archive';

            case ZipArchive::ER_DELETED:
                return 'ZIP Entry has been deleted';

            default:
                return 'Unknown error within ZIP Archive';
        }
    }

    /**
     * @return string
     */
    public function toString()
    {
        return 'Zip';
    }
}
