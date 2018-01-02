<?php

namespace Ions\Compress;

/**
 * Class Compression
 * @package Ions\Compress
 */
class Compression
{
    /**
     * @var string
     */
    protected $adapter = 'Gz';
    /**
     * @var array
     */
    protected $options = [];

    /**
     * Compression constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if ($options) {
            $this->setOptions($options);
        }
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return Adapter\CompressionInterface|string
     * @throws \RuntimeException|\InvalidArgumentException
     */
    public function getAdapter()
    {
        if ($this->adapter) {
            return $this->adapter;
        }

        $adapter = $this->adapter;
        $options = $this->getOptions();
        $adapter = 'Adapter\\' . ucfirst($adapter);

        if (!class_exists($adapter)) {
            throw new \RuntimeException(sprintf(
                '%s unable to load adapter; class "%s" not found',
                __METHOD__,
                $this->adapter
            ));
        }

        $this->adapter = new $adapter($options);

        if (!$this->adapter instanceof Adapter\CompressionInterface) {
            throw new \InvalidArgumentException(
                "Compression adapter '" . $adapter
                . "' does not implement " . Adapter\CompressionInterface::class
            );
        }

        return $this->adapter;
    }

    /**
     * @param $adapter
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setAdapter($adapter)
    {
        if ($adapter instanceof Adapter\CompressionInterface) {
            $this->adapter = $adapter;
            return $this;
        }
        if (!is_string($adapter)) {
            throw new \InvalidArgumentException(
                'Invalid adapter provided; must be string or instance of '
                . Adapter\CompressionInterface::class
            );
        }
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * @param $value
     * @return mixed
     * @throws \RuntimeException|\InvalidArgumentException
     */
    public function compress($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return $this->getAdapter()->compress($value);
    }

    /**
     * @param $value
     * @return mixed
     * @throws \RuntimeException|\InvalidArgumentException
     */
    public function decompress($value)
    {
        if (!is_string($value) && $value !== null) {
            return $value;
        }

        return $this->getAdapter()->decompress($value);
    }
}
