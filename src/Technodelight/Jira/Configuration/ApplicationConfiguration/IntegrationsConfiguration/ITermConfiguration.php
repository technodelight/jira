<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class ITermConfiguration implements RegistrableConfiguration
{
    /**
     * @var bool
     */
    private $renderImages;
    /**
     * @var int
     */
    private $thumbnailWidth;
    /**
     * @var int
     */
    private $imageCacheTtl;
    /**
     * @var array
     */
    private $config;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->config = $config;
        $instance->renderImages = $config['renderImages'];
        $instance->thumbnailWidth = (int) $config['thumbnailWidth'];
        $instance->imageCacheTtl = (int) $config['imageCacheTtl'];

        return $instance;
    }

    public function renderImages()
    {
        return $this->renderImages;
    }

    public function thumbnailWidth()
    {
        return $this->thumbnailWidth;
    }

    public function imageCacheTtl()
    {
        return $this->imageCacheTtl;
    }

    public function servicePrefix(): string
    {
        return 'iterm';
    }

    /**
     * @return array
     */
    public function configAsArray(): array
    {
        return $this->config;
    }

    private function __construct()
    {
    }
}
