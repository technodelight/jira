<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use SebastianBergmann\Diff\Differ;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\FieldEditor\FieldEditor;
use Technodelight\Jira\Console\Option\Checker;
use Technodelight\Jira\Domain\Issue\Meta\Field;
use Technodelight\Jira\Domain\Issue;

class Edit extends Command
{
    /**
     * @var Api
     */
    private $jira;
    /**
     * @var QuestionHelper
     */
    private $questionHelper;
    /**
     * @var IssueKeyResolver
     */
    private $issueKeyResolver;
    /**
     * @var Checker
     */
    private $checker;
    /**
     * @var FieldEditor
     */
    private $editor;

    public function setJiraApi(Api $jira)
    {
        $this->jira = $jira;
    }

    public function setQuestionHelper(QuestionHelper $questionHelper)
    {
        $this->questionHelper = $questionHelper;
    }

    public function setIssueKeyResolver(IssueKeyResolver $issueKeyResolver)
    {
        $this->issueKeyResolver = $issueKeyResolver;
    }

    public function setOptionChecker(Checker $checker)
    {
        $this->checker = $checker;
    }

    public function setFieldEditor(FieldEditor $editor)
    {
        $this->editor = $editor;
    }

    protected function configure()
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
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Technodelight\Jira\Console\FieldEditor\EditorException
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $fieldKey = $input->getArgument('fieldKey');

        if (empty($fieldKey)) {
            $fields = $this->jira->issueEditMeta($issueKey)->fields();
            $idx = $this->questionHelper->ask($input, $output, new ChoiceQuestion(
                'Select a field to edit',
                array_map(
                    function (Field $field) {
                        return $field->name();
                    },
                    $fields
                )
            ));
            $selectedField = $fields[$idx];
            $input->setArgument('fieldKey', $selectedField->key());
        }

        $options = ['add', 'set', 'remove'];
        $field = $this->jira->issueEditMeta($issueKey)->field($fieldKey);
        foreach ($options as $option) {
            if ($this->checker->hasOptionWithoutValue($input, $option)) {
                $input->setOption($option, $this->editField($input, $output, $field, $issueKey, $option));
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $fieldKey = $input->getArgument('fieldKey');

        if (empty((string) $issueKey) || empty($fieldKey)) {
            throw new \InvalidArgumentException('Nothing to do');
        }

        $field = $this->jira->issueEditMeta($issueKey)->field($fieldKey);

        $beforeUpdate = $this->jira->retrieveIssue($issueKey);
        $this->jira->updateIssue(
            $issueKey,
            ['update' => $this->prepareUpdateData($field, $input)],
            ['notifyUsers' => $input->getOption('no-notifiy') ? false : null]
        );
        $afterUpdate = $this->jira->retrieveIssue($issueKey);

        $this->renderChange($field, $beforeUpdate, $afterUpdate, $output);
    }

    private function renderChange(Field $field, Issue $before, Issue $after, OutputInterface $output)
    {
        if ($field->schemaType() == 'string') {
            $differ = new Differ;
            $before = $before->findField($field->key()) ?: '';
            $after = $after->findField($field->key()) ?: '';

            $output->writeln(
                sprintf(
                    '<comment>%s</comment> was changed: ' . PHP_EOL . '%s',
                    $field->name(),
                    $this->formatDiff(
                        $differ->diff($before, $after)
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
                $this->renderChangeset('Added', $field->name(), $added)
            );
        }
        if (!empty($removed)) {
            $output->writeln(
                $this->renderChangeset('Removed', $field->name(), $removed)
            );
        }
        if (!empty($unchanged)) {
            $output->writeln(
                $this->renderChangeset('Unchanged', $field->name(), $unchanged)
            );
        }
    }

    private function formatDiff($diff)
    {
        $lines = explode(PHP_EOL, $diff);
        foreach ($lines as $idx => $line) {
            if (substr($line, 0, 1) == '-') {
                $lines[$idx] = '<fg=red>' . $line . '</>';
            } else if (substr($line, 0, 1) == '+') {
                $lines[$idx] = '<fg=green>' . $line . '</>';
            }
        }
        return join(PHP_EOL, $lines);
    }

    private function renderChangeset($text, $field, array $changes)
    {
        return sprintf(
            '<comment>%s %s for %s</comment>',
            $text,
            '<info>' . join(', ', $changes) . '</info>',
            $field
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Field $field
     * @param \Technodelight\Jira\Console\Argument\IssueKey $issueKey
     * @param string $option
     * @return string
     * @throws \Technodelight\Jira\Console\FieldEditor\EditorException
     */
    private function editField(InputInterface $input, OutputInterface $output, Field $field, IssueKey $issueKey, $option)
    {
        return $this->editor->edit(
            $input,
            $output,
            $issueKey,
            $field,
            $option
        );
    }

    private function prepareUpdateData(Field $field, InputInterface $input)
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
            throw new \InvalidArgumentException('Nothing to do');
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
        if ($field->schemaItemType() == 'string' || $field->schemaType() == 'string') {
            return $value;
        }

        return ['name' => $value];
    }

    private function arrayOfNamesFromField(array $valueArray)
    {
        return array_map(
            function ($value) {
                if (is_array($value)) {
                    return $value['name'];
                }
                return $value;
            },
            $valueArray
        );
    }
}
