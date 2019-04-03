<?php

namespace Technodelight\Jira\Console\Input\Issue\Attachment;

use GlobIterator;
use SplFileInfo;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Technodelight\Jira\Domain\Attachment;

class UploadableAttachment
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \ErrorException
     */
    public function resolve(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('filename');

        if (!is_readable($filename)) {
            $output->writeln(sprintf('Cannot read "%s"', $filename));
            $filename = null;
        }

        if (empty($filename)) {
            $helper = new QuestionHelper;
            $question = new ChoiceQuestion(
                '<comment>Select file to upload</comment>',
                array_map(function (Attachment $attachment) {
                    return $attachment->filename();
                }, $this->listCurrentDirectory()),
                0
            );
            $question->setErrorMessage('Filename %s is invalid.');

            return $helper->ask($input, $output, $question);
        }

        return $filename;
    }

    private function listCurrentDirectory()
    {
        $iterator = new GlobIterator(getcwd(), GlobIterator::CURRENT_AS_FILEINFO | GlobIterator::KEY_AS_FILENAME);
        $filteredIterator = new \CallbackFilterIterator($iterator, function(SplFileInfo $file) {
            return !$file->isDir() && $file->isReadable();
        });

        return iterator_to_array($filteredIterator);
    }
}
