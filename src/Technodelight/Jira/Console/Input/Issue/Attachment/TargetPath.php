<?php

namespace Technodelight\Jira\Console\Input\Issue\Attachment;

use Symfony\Component\Console\Input\InputInterface;

class TargetPath
{
    /**
     * @TODO maybe an abstracted file-system provider would be nice? would improve testability
     * @param InputInterface $input
     * @return string
     */
    public function resolve(InputInterface $input)
    {
        $targetPath = $input->getArgument('targetPath');
        if (!$targetPath) {
            $targetPath = getcwd();
        }

        return rtrim($targetPath, '/\\' . DIRECTORY_SEPARATOR);
    }
}
