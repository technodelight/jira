<?php

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
    /**
     * @var FormatterHelper
     */
    private $formatterHelper;
    /**
     * @var TemplateHelper
     */
    private $templateHelper;
    /**
     * @var StyleGuide
     */
    private $styleGuide;

    public function __construct(
        FormatterHelper $formatterHelper,
        TemplateHelper $templateHelper,
        StyleGuide $styleGuide
    )
    {
        $this->formatterHelper = $formatterHelper;
        $this->styleGuide = $styleGuide;
        $this->templateHelper = $templateHelper;
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

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param Result $result
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
        $output->writeln(
            $this->templateHelper->tabulate([
                sprintf('%s %s', $this->styleGuide->formatFirstLevelInfo('email address:'), $user->emailAddress()),
                sprintf('%s %s', $this->styleGuide->formatFirstLevelInfo('active:'), $user->active() ? 'yes' : 'no'),
                sprintf('%s %s', $this->styleGuide->formatFirstLevelInfo('time zone:'), $user->timeZone()),
                sprintf('%s %s', $this->styleGuide->formatFirstLevelInfo('locale:'), $user->locale()),
            ])
        );

        return 0;
    }

    private function renderError(OutputInterface $output, Error $error): int
    {
        if ($output->getVerbosity() == OutputInterface::VERBOSITY_QUIET) {
            return $error->exception()->getCode() ?: 1;
        }

        $output->writeln(
            $this->styleGuide->error(
                vsprintf($error->phrase(), array_filter(array_merge([$error->issueKey()], $error->data())))
            )
        );

        if ($output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln($error->exception()->getTraceAsString()); //@TODO: a nice formatting would be good here
        }

        return $error->exception()->getCode() ?: 1;
    }
}
