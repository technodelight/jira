<?php

namespace Technodelight\Jira\Helper;

use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Api\Shell\NativeShell;
use Technodelight\Jira\Api\Shell\Shell;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Domain\Issue;

class Image
{
    /**
     * @var \Technodelight\Jira\Helper\ImageProvider
     */
    private $imageProvider;
    /**
     * @var string
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

    public function __construct(ImageProvider $imageProvider, ApplicationConfiguration $config)
    {
        $this->imageProvider = $imageProvider;
        $this->itermVersion = (string) new ITermVersion();
        $this->displayImages = $config->iterm()['renderImages'];
        $this->thumbnailWidth = $config->iterm()['thumbnailWidth'];
    }

    public function render($body, Issue $issue)
    {
        if (preg_match_all('~!([^|]+)(\|thumbnail!)?~', $body, $matches)) {
            $replacePairs = [];
            foreach ($matches[1] as $k => $embeddedImage) {
                if (empty($embeddedImage)) {
                    continue;
                }

                if (!$this->isIterm() || !$this->displayImages) {
                    $image = '<comment>jira download ' . $embeddedImage . '</>';
                } else {
                    try {
                        $image = $this->renderThumbnail($issue, $embeddedImage);
                    } catch (\Exception $e) {
                        $image = $embeddedImage;
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
        return chr(27) .
            ']1337;File=inline=1;width=' . $this->thumbnailWidth . 'px;preserveAspectRatio=1:'
            . base64_encode($this->getImageContents($issue, $imageFilename))
            . chr(7);
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
