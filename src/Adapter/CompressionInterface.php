<?php

namespace Ions\Compress\Adapter;

/**
 * Interface CompressionInterface
 * @package Ions\Compress\Adapter
 */
interface CompressionInterface
{
    /**
     * @param $data
     * @return mixed
     */
    public function compress($data);

    /**
     * @param $data
     * @return mixed
     */
    public function decompress($data);
}
