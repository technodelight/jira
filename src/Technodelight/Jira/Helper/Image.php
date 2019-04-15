<?php

namespace Technodelight\Jira\Helper;

use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\ITermConfiguration;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Api\ITermImage\Image as ITermImage;
use Technodelight\Jira\Api\ITermImage\ITermVersion;

class Image
{
    /**
     * @var ImageProvider
     */
    private $imageProvider;
    /**
     * @var ITermVersion
     */
    private $itermVersion;
    /**
     * @var int
     */
    private $thumbnailWidth;
    /**
     * @var bool
     */
    private $displayImages;

    public function __construct(ImageProvider $imageProvider, ITermConfiguration $config)
    {
        $this->imageProvider = $imageProvider;
        $this->itermVersion = new ITermVersion();
        $this->displayImages = $config->renderImages();
        $this->thumbnailWidth = $config->thumbnailWidth();
    }

    public function render($body, Issue $issue)
    {
        if (preg_match_all('~!([^|]+)(\|thumbnail!)?~', $body, $matches)) {
            $replacePairs = [];
            foreach ($matches[1] as $k => $embeddedImage) {
                if (empty(trim($embeddedImage))) {
                    continue;
                }

                if (!$this->isIterm() || !$this->displayImages) {
                    $image = '<comment>jira download ' . $embeddedImage . '</>';
                } else {
                    try {
                        $image = $this->renderThumbnail($issue, $embeddedImage);
                    } catch (\Exception $e) {
                        continue;
                    }
                }
                $replacePairs[$matches[0][$k]] = $image;
            }
            $body = strtr($body, $replacePairs);
        }
        return $body;
    }

    /**
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @param $imageFilename
     * @return string
     */
    protected function renderThumbnail(Issue $issue, $imageFilename)
    {
        return ITermImage::fromContents($this->getImageContents($issue, $imageFilename));
    }

    /**
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @param $imageFilename
     * @return string
     */
    protected function getImageContents(Issue $issue, $imageFilename)
    {
        return $this->imageProvider->contents(
            $this->findAttachment($issue, $imageFilename)
        );
    }

    private function findAttachment(Issue $issue, $filename)
    {
        foreach ($issue->attachments() as $attachment) {
            if ($attachment->filename() == $filename) {
                return $attachment->url();
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Attachment "%s" cannot be found', $filename)
        );
    }

    private function isIterm()
    {
        if (empty($this->itermVersion)) {
            return false;
        }
        return version_compare($this->itermVersion, '3.0.0', '>=');
    }
}
