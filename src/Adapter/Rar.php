<?php

namespace Ions\Compress\Adapter;

use Ions\Std\AbstractOptions;

/**
 * Class Rar
 * @package Ions\Compress\Adapter
 */
class Rar extends AbstractOptions implements CompressionInterface
{
    /**
     * @var array
     */
    protected $options = [
        'callback' => null,
        'archive'  => null,
        'password' => null,
        'target'   => '.',
    ];

    /**
     * Rar constructor.
     * @param null $options
     * @throws \RuntimeException
     */
    public function __construct($options = null)
    {
        if (! extension_loaded('rar')) {
            throw new \RuntimeException('This filter needs the rar extension');
        }
        parent::__construct($options);
    }

    /**
     * @return mixed
     */
    public function getCallback()
    {
        return $this->options['callback'];
    }

    /**
     * @param $callback
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setCallback($callback)
    {
        if (! is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid callback provided');
        }

        $this->options['callback'] = $callback;
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
        $archive = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $archive);
        $this->options['archive'] = (string) $archive;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->options['password'];
    }

    /**
     * @param $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->options['password'] = (string) $password;
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
        $callback = $this->getCallback();
        if ($callback === null) {
            throw new \RuntimeException('No compression callback available');
        }

        $options = $this->getOptions();
        unset($options['callback']);

        $result = call_user_func($callback, $options, $content);
        if ($result !== true) {
            throw new \RuntimeException('Error compressing the RAR Archive');
        }

        return $this->getArchive();
    }

    /**
     * @param $content
     * @return bool
     * @throws \RuntimeException
     */
    public function decompress($content)
    {
        if (! file_exists($content)) {
            throw new \RuntimeException('RAR Archive not found');
        }

        $archive  = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, realpath($content));
        $password = $this->getPassword();
        if ($password !== null) {
            $archive = rar_open($archive, $password);
        } else {
            $archive = rar_open($archive);
        }

        if (! $archive) {
            throw new \RuntimeException("Error opening the RAR Archive");
        }

        $target = $this->getTarget();
        if (! is_dir($target)) {
            $target = dirname($target);
        }

        $filelist = rar_list($archive);
        if (! $filelist) {
            throw new \RuntimeException("Error reading the RAR Archive");
        }

        foreach ($filelist as $file) {
            $file->extract($target);
        }

        rar_close($archive);
        return true;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return 'Rar';
    }
}
