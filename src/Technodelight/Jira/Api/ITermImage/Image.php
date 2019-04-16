<?php

namespace Technodelight\Jira\Api\ITermImage;

class Image
{
    /**
     * @var string
     */
    private $uri;
    /**
     * @var string
     */
    private $contents;
    /**
     * @var int
     */
    private $thumbnailWidth;
    /**
     * @var ITermVersion
     */
    private $itermVersion;

    public static function fromUri($uri, $thumbWidth = 300, ITermVersion $itermVersion = null)
    {
        $instance = new self;
        $instance->uri = $uri;
        $instance->thumbnailWidth = $thumbWidth;
        $instance->itermVersion = $itermVersion ?: new ITermVersion;

        return $instance;
    }

    public static function fromContents($contents, $thumbWidth = 300, ITermVersion $itermVersion = null)
    {
        $instance = new self;
        $instance->contents = $contents;
        $instance->thumbnailWidth = $thumbWidth;
        $instance->itermVersion = $itermVersion ?: new ITermVersion;

        return $instance;
    }

    public function render()
    {
        try {
            if (!$this->isItermCapable()) {
                return $this->uri;
            }

            return $this->renderThumbnail($this->getImageContents());
        } catch (\Exception $e) {
            return '';
        }
    }

    public function __toString()
    {
        return $this->render();
    }

    /**
     * @return string
     */
    protected function getImageContents()
    {
        if (!empty($this->contents)) {
            return $this->contents;
        }

        return file_get_contents($this->uri);
    }

    /**
     * @param $contents
     * @return string
     */
    protected function renderThumbnail($contents)
    {
        return chr(27) .
            ']1337;File=inline=1;width=' . $this->thumbnailWidth . 'px;preserveAspectRatio=1:'
            . base64_encode($contents)
            . chr(7);
    }

    private function isItermCapable()
    {
        return version_compare((string) $this->itermVersion, '3.0.0', '>=');
    }

    private function __construct()
    {
    }
}
