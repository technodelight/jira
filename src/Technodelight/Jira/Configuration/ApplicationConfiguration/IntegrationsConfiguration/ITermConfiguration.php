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

    public static function fromArray(array $config)
    {
        $instance = new self;
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

    public function servicePrefix()
    {
        return 'iterm';
    }

    private function __construct()
    {
    }
}
