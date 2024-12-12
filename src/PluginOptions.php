<?php

declare(strict_types=1);

namespace Extism;

/**
 * Options for initializing a plugin.
 */
class PluginOptions 
{
    /**
     * Enable WASI support.
     *
     * @var bool
     */
    private $withWasi = false;

    /**
     * Limits number of instructions that can be executed by the plugin.
     *
     * @var int|null
     */
    private $fuelLimit = null;

    /**
     * Constructor
     *
     * @param bool $withWasi Enable WASI support
     * @param int|null $fuelLimit Fuel limit for instruction execution
     */
    public function __construct(bool $withWasi = false, ?int $fuelLimit = null)
    {
        $this->withWasi = $withWasi;
        $this->fuelLimit = $fuelLimit;
    }

    /**
     * @return bool
     */
    public function getWithWasi(): bool
    {
        return $this->withWasi;
    }

    /**
     * @param bool $withWasi
     * @return self
     */
    public function setWithWasi(bool $withWasi): self
    {
        $this->withWasi = $withWasi;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getFuelLimit(): ?int
    {
        return $this->fuelLimit;
    }

    /**
     * @param int|null $fuelLimit
     * @return self
     */
    public function setFuelLimit(?int $fuelLimit): self
    {
        $this->fuelLimit = $fuelLimit;
        return $this;
    }
}