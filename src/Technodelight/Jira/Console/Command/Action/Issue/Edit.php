<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use InvalidArgumentException;
use SebastianBergmann\Diff\Differ;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\FieldEditor\EditorException;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\FieldEditor\FieldEditor;
use Technodelight\Jira\Console\Option\Checker;
use Technodelight\Jira\Domain\Issue\Meta\Field;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Issue\MinimalHeader;

class Edit extends Command
{
    private const ACTION_OPTS = ['add', 'set', 'remove'];

    public function __construct(
        private readonly Api $jira,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly Checker $checker,
        private readonly QuestionHelper $questionHelper,
        private readonly FieldEditor $editor,
        private readonly TemplateHelper $templateHelper,
        private readonly MinimalHeader $minimalRenderer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('issue:edit')
            ->setAliases(['edit'])
            ->setDescription('Edit issue fields (experimental)')
            ->addArgument(
                IssueKeyResolver::ARGUMENT,
                InputArgument::OPTIONAL,
                'IssueKey to edit'
            )
            ->addArgument(
                'fieldKey',
                InputArgument::OPTIONAL,
                'Field name to edit'
            )
            ->addOption(
                'add',
                '',
                InputOption::VALUE_OPTIONAL,
                'Value(s) to add',
                ''
            )
            ->addOption(
                'remove',
                '',
                InputOption::VALUE_OPTIONAL,
                'Value(s) to remove',
                ''
            )
            ->addOption(
                'set',
                '',
                InputOption::VALUE_OPTIONAL,
                'Value(s) to set',
                ''
            )
            ->addOption(
                'no-notifiy',
                '',
                InputOption::VALUE_NONE,
                'Skip notifying watchers about the change'
            )
            ->addOption(
                'list',
                '',
                InputOption::VALUE_NONE,
                'List available values, if it is a set of choices'
            );
    }

    /** @throws EditorException */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $fieldKey = $input->getArgument('fieldKey');

        if (empty($fieldKey)) {
            $fields = $this->jira->issueEditMeta($issueKey)->fields();
            $idx = $this->questionHelper->ask($input, $output, new ChoiceQuestion(
                'Select a field to edit',
                array_map(static function (Field $field) {
                    return $field->name();
                }, $fields)
            ));
            $selectedField = $fields[$idx];
            $input->setArgument('fieldKey', $selectedField->key());
            $fieldKey = $selectedField->key();
        }

        $field = $this->jira->issueEditMeta($issueKey)->field($fieldKey);
        $hasAction = false;
        foreach (self::ACTION_OPTS as $option) {
            if ($input->getOption($option)) {
                $hasAction = true;
            }
        }
        if ($hasAction === false) {
            $this->interactivelySelectAction($input, $output);
        }

        foreach (self::ACTION_OPTS as $option) {
            if ($this->checker->hasOptionWithoutValue($input, $option)) {
                $input->setOption($option, $this->editField($input, $output, $field, $issueKey, $option));
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $fieldKey = $input->getArgument('fieldKey');

        if (empty((string)$issueKey)) {
            throw new InvalidArgumentException('Nothing to do, please select an issue first');
        }

        $field = $this->jira->issueEditMeta($issueKey)->field($fieldKey);
        if ($input->getOption('list')) {
            $output->writeln($field->allowedValues());
            return self::SUCCESS;
        }

        $beforeUpdate = $this->jira->retrieveIssue($issueKey);
        $this->jira->updateIssue(
            $issueKey,
            ['update' => $this->prepareUpdateData($field, $input)],
            ['notifyUsers' => $input->getOption('no-notifiy') ? false : null]
        );
        $afterUpdate = $this->jira->retrieveIssue($issueKey);

        $this->minimalRenderer->render($output, $this->jira->retrieveIssue($issueKey));
        $this->renderChange($field, $beforeUpdate, $afterUpdate, $output);

        return self::SUCCESS;
    }

    private function renderChange(Field $field, Issue $before, Issue $after, OutputInterface $output): void
    {
        if ($field->schemaType() === 'string' || $field->schemaType() === 'number') {
            $differ = new Differ;
            $beforeValue = ($before = $before->findField($field->key())) ? $before : '';
            $afterValue = ($after = $after->findField($field->key())) ? $after : '';

            $output->writeln(
                sprintf(
                    '<comment>%s</comment> was changed: ' . PHP_EOL . '%s',
                    $field->name(),
                    $this->formatDiff(
                        $differ->diff($beforeValue, $afterValue)
                    )
                )
            );
            return;
        }

        $beforeValues = $this->arrayOfNamesFromField($before->findField($field->key()) ?: []);
        $afterValues = $this->arrayOfNamesFromField($after->findField($field->key()) ?: []);
        $added = array_diff($afterValues, $beforeValues);
        $removed = array_diff($beforeValues, $afterValues);
        $unchanged = array_intersect($beforeValues, $afterValues);

        if (!empty($added)) {
            $output->writeln(
                $this->renderChangeSet('Added', $field->name(), $added)
            );
        }
        if (!empty($removed)) {
            $output->writeln(
                $this->renderChangeSet('Removed', $field->name(), $removed)
            );
        }
        if (!empty($unchanged)) {
            $output->writeln(
                $this->renderChangeSet('Unchanged', $field->name(), $unchanged)
            );
        }
    }

    private function formatDiff($diff): string
    {
        $lines = explode(PHP_EOL, $diff);
        foreach ($lines as $idx => $line) {
            $lines[$idx] = match(true) {
                str_starts_with($line, '-') => '<fg=red>' . $line . '</>',
                str_starts_with($line, '+') => '<fg=green>' . $line . '</>'
            };
        }
        return implode(PHP_EOL, $lines);
    }

    private function renderChangeSet($text, $field, array $changes): string
    {
        return $this->templateHelper->tabulate(sprintf(
            '<comment>%s %s for %s</comment>',
            $text,
            '<info>' . implode(', ', $changes) . '</info>',
            $field
        ));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Field $field
     * @param IssueKey $issueKey
     * @param string $option
     * @return string
     * @throws EditorException
     */
    private function editField(
        InputInterface $input,
        OutputInterface $output,
        Field $field,
        IssueKey $issueKey,
        string $option
    ): string {
        return $this->editor->edit(
            $input,
            $output,
            $issueKey,
            $field,
            $option
        );
    }

    private function prepareUpdateData(Field $field, InputInterface $input): array
    {
        $changeSet = [];
        $operations = [
            'add' => $input->getOption('add'),
            'set' => $input->getOption('set'),
            'remove' => $input->getOption('remove')
        ];

        foreach ($operations as $operation => $values) {
            $values = explode(',', $values);
            if (!empty($values)) {
                foreach ($values as $value) {
                    if (!empty(trim($value))) {
                        $value = $this->remapValue($field, $value);
                        $changeSet[] = [$operation => $value];
                    }
                }
            }
        }

        if (empty($changeSet)) {
            throw new InvalidArgumentException('Nothing to do');
        }

        return [$field->key() => $changeSet];
    }

    /**
     * Exchange a [value] to another [value] or [['name'=>value]] array
     *
     * @param Field $field
     * @param string $value
     * @return string|array
     */
    public function remapValue(Field $field, $value)
    {
        if ($field->schemaItemType() === 'string' || $field->schemaType() === 'string') {
            return $value;
        }
        if ($field->schemaItemType() === 'number' || $field->schemaType() === 'number') {
            return (int)$value;
        }

        return ['name' => $value];
    }

    private function interactivelySelectAction(InputInterface $input, OutputInterface $output): void
    {
        $option = $this->questionHelper->ask($input, $output, new ChoiceQuestion(
            sprintf('Choose an action to do with the "%s" field', $input->getArgument('fieldKey')),
                self::ACTION_OPTS
            )
        );
        $input->setOption($option, '');
        $_SERVER['argv'][] = '--' . $option;
    }

    private function arrayOfNamesFromField(array $valueArray): array
    {
        return array_map(
            static function ($value) {
                return is_array($value) ? $value['name'] : $value;
            },
            $valueArray
        );
    }
}
