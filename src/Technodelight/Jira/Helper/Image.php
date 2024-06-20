<?php

declare(strict_types=1);

namespace Technodelight\Jira\Helper;

use LogicException;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\ITermConfiguration;
use Technodelight\Jira\Domain\Issue;
use Technodelight\ITermImage\Image as ITermImage;
use Technodelight\ITermImage\ITermVersion;

class Image
{
    private const IMAGE_TAG_REGEX = '~!([^|!]+)(\|thumbnail)?(\|width=\d*(,height=\d*)?(,alt="[^"]*")?)?!~';

    public function __construct(
        private readonly ImageProvider $imageProvider,
        private readonly ITermConfiguration $config,
        private readonly ITermVersion $itermVersion = new ITermVersion()
    ) {
    }

    public function render(string $body, Issue $issue): string
    {
        if (preg_match_all(self::IMAGE_TAG_REGEX, $body, $matches)) {
            $replacePairs = [];
            foreach ($matches[1] as $k => $embeddedImage) {
                if (empty(trim($embeddedImage))) {
                    continue;
                }

                if (!$this->isIterm() || !$this->config->renderImages()) {
                    $image = sprintf('<comment>jira download %s %s</>', $issue->issueKey(), $embeddedImage);
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

    private function renderThumbnail(Issue $issue, string $imageFilename): ITermImage
    {
        return ITermImage::fromContents($this->getImageContents($issue, $imageFilename));
    }

    private function getImageContents(Issue $issue, string $imageFilename): string
    {
        return $this->imageProvider->contents(
            $this->findAttachment($issue, $imageFilename)
        );
    }

    private function findAttachment(Issue $issue, string $filename): string
    {
        foreach ($issue->attachments() as $attachment) {
            if ($attachment->filename() == $filename) {
                return $attachment->url();
            }
        }

        throw new LogicException(
            sprintf('Attachment "%s" cannot be found', $filename)
        );
    }

    private function isIterm(): bool
    {
        if (empty((string)$this->itermVersion)) {
            return false;
        }

        return version_compare((string)$this->itermVersion, '3.0.0', '>=');
    }
}
