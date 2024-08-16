<?php

declare(strict_types=1);

namespace Technodelight\Jira\Helper;

use Exception;
use LogicException;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\ITermConfiguration;
use Technodelight\Jira\Domain\Issue;
use Technodelight\ITermImage\Image as ITermImage;
use Technodelight\ITermImage\ITermVersion;

class Image
{
    private const IMAGE_TAG_REGEX = '~!([^|!]+)(\|thumbnail)?(\|width=\d*)(,height=\d*)?(,alt="[^"]*")?!~';

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

                $replacePairs[$matches[0][$k]] = $this->getImageString($issue, $embeddedImage, $matches, $k);
            }
            $body = strtr($body, $replacePairs);
        }
        return $body;
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    private function renderThumbnail(
        Issue $issue,
        string $imageFilename,
        int $thumbWidth
    ): ITermImage {
        return ITermImage::fromContents(
            $this->getImageContents($issue, $imageFilename), $thumbWidth, $this->itermVersion
        );
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

    private function getThumbnailWidth(array $matches, int $index): int
    {
        $width = $this->config->thumbnailWidth();
        if (isset($matches[3][$index])) {
            [,$width] = explode('=', $matches[3][$index], 2) + ['', $this->config->thumbnailWidth()];
        }

        return (int)$width > 0 ? (int)$width : $this->config->thumbnailWidth();
    }

    private function getImageString(Issue $issue, string $embeddedImage, array $matches, int|string $index): string|ITermImage
    {
        if (!$this->isIterm() || !$this->config->renderImages()) {
            return sprintf('<comment>jira download %s %s</>', $issue->issueKey(), $embeddedImage);
        }

        try {
            return $this->renderThumbnail(
                $issue,
                $embeddedImage,
                $this->getThumbnailWidth($matches, $index)
            );
        } catch (Exception $e) {
            trigger_error(
                sprintf("(%s) %s", get_class($e), $e->getMessage()),
                E_USER_WARNING
            );
            return '';
        }
    }
}
