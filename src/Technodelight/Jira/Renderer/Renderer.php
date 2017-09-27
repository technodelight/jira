<?php
/**
 * @category Heidelberg
 * @package Heidelberg_<module>
 * @subpackage Heidelberg_<module>_<classtype>
 * @author Zsolt Gal <zgal@inviqa.com>
 * @copyright 2017 Inviqa
 * @license http://inviqa.de Inviqa
 * @link http://inviqa.de
 */

namespace Technodelight\Jira\Renderer;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;

interface Renderer
{
    public function render(OutputInterface $output, Issue $issue);
}
