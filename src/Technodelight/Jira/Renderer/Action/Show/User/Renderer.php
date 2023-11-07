<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Action\Show\User;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\ITermImage\Image;
use Technodelight\Jira\Renderer\Action\Renderer as ActionRenderer;
use Technodelight\Jira\Renderer\Action\Result;
use Technodelight\Jira\Renderer\Action\StyleGuide;

class Renderer implements ActionRenderer
{
    public function __construct(
        private readonly TemplateHelper $templateHelper,
        private readonly StyleGuide $styleGuide
    ) {
    }

    public function canProcess(Result $result): bool
    {
        return $result instanceof Success || $result instanceof Error;
    }

    public function render(OutputInterface $output, Result $result): int
    {
        if ($result instanceof Success) {
            return $this->renderSuccess($output, $result);
        }

        if ($result instanceof Error) {
            return $this->renderError($output, $result);
        }

        return 1;
    }

    /**
     * @param OutputInterface $output
     * @param Success $result
     * @return int
     */
    protected function renderSuccess(OutputInterface $output, Success $result): int
    {
        $user = $result->user();
        $image = Image::fromUri($user->avatarUrls()['48x48'], 48);
        if (!empty($image)) {
            $output->writeln((string) $image);
        }
        $output->writeln(
            sprintf('%s (%s)', $this->styleGuide->formatUsername($user->key()), $user->displayName())
        );
        $dataToDisplay = [
            'account id' => $user->id(),
            'username' => $user->displayName(),
            'email address' => $user->emailAddress(),
            'active' => $user->active() ? 'yes' : 'no',
            'time zone' => $user->timeZone(),
            'locale' => $user->locale()
        ];
        foreach ($dataToDisplay as $column => $info) {
            $output->writeln(
                $this->templateHelper->tabulate(
                    sprintf(
                        '%s: %s',
                        $this->styleGuide->formatFirstLevelInfo($column),
                        $info
                    )
                )
            );
        }

        return 0;
    }

    private function renderError(OutputInterface $output, Error $error): int
    {
        if ($output->getVerbosity() === OutputInterface::VERBOSITY_QUIET) {
            return $error->exception()->getCode() ?: 1;
        }

        $output->writeln(
            $this->styleGuide->error(
                vsprintf($error->phrase(), array_filter($error->data()))
            )
        );

        if ($output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln($error->exception()->getTraceAsString()); //@TODO: a nice formatting would be good here
        }

        return $error->exception()->getCode() ?: 1;
    }
}
