<?php

namespace Technodelight\Jira\Api\JiraTagConverter\Components;

use Symfony\Component\Console\Helper\TableStyle;

class PrettyTableStyle extends TableStyle
{
    private $topLeftBorderChar = '┌';
    private $topRightBorderChar = '┐';
    private $topCrossChar = '┬';
    private $bottomLeftBorderChar = '└';
    private $bottomRightBorderChar = '┘';
    private $bottomCrossChar = '┴';
    private $leftRowBorderChar = '├';
    private $rightRowBorderChar = '┤';
    private $horizontalBorderChar = '─';
    private $verticalBorderChar = '│';
    private $crossingChar = '┼';

    /**
     * @return string
     */
    public function getTopLeftBorderChar()
    {
        return $this->topLeftBorderChar;
    }

    /**
     * @param string $topLeftBorderChar
     * @return $this
     */
    public function setTopLeftBorderChar($topLeftBorderChar)
    {
        $this->topLeftBorderChar = $topLeftBorderChar;

        return $this;
    }

    /**
     * @return string
     */
    public function getTopRightBorderChar()
    {
        return $this->topRightBorderChar;
    }

    /**
     * @param string $topRightBorderChar
     * @return $this
     */
    public function setTopRightBorderChar($topRightBorderChar)
    {
        $this->topRightBorderChar = $topRightBorderChar;

        return $this;
    }

    /**
     * @return string
     */
    public function getTopCrossChar()
    {
        return $this->topCrossChar;
    }

    /**
     * @param string $topCrossChar
     * @return $this
     */
    public function setTopCrossChar($topCrossChar)
    {
        $this->topCrossChar = $topCrossChar;

        return $this;
    }

    /**
     * @return string
     */
    public function getBottomLeftBorderChar()
    {
        return $this->bottomLeftBorderChar;
    }

    /**
     * @param string $bottomLeftBorderChar
     * @return $this
     */
    public function setBottomLeftBorderChar($bottomLeftBorderChar)
    {
        $this->bottomLeftBorderChar = $bottomLeftBorderChar;

        return $this;
    }

    /**
     * @return string
     */
    public function getBottomRightBorderChar()
    {
        return $this->bottomRightBorderChar;
    }

    /**
     * @param string $bottomRightBorderChar
     * @return $this
     */
    public function setBottomRightBorderChar($bottomRightBorderChar)
    {
        $this->bottomRightBorderChar = $bottomRightBorderChar;

        return $this;
    }

    /**
     * @return string
     */
    public function getBottomCrossChar()
    {
        return $this->bottomCrossChar;
    }

    /**
     * @param string $bottomCrossChar
     * @return $this
     */
    public function setBottomCrossChar($bottomCrossChar)
    {
        $this->bottomCrossChar = $bottomCrossChar;

        return $this;
    }

    /**
     * @return string
     */
    public function getLeftRowBorderChar()
    {
        return $this->leftRowBorderChar;
    }

    /**
     * @param string $leftRowBorderChar
     * @return $this
     */
    public function setLeftRowBorderChar($leftRowBorderChar)
    {
        $this->leftRowBorderChar = $leftRowBorderChar;

        return $this;
    }

    /**
     * @return string
     */
    public function getRightRowBorderChar()
    {
        return $this->rightRowBorderChar;
    }

    /**
     * @param string $rightRowBorderChar
     * @return $this
     */
    public function setRightRowBorderChar($rightRowBorderChar)
    {
        $this->rightRowBorderChar = $rightRowBorderChar;

        return $this;
    }

    /**
     * Sets horizontal border character.
     *
     * @param string $horizontalBorderChar
     *
     * @return $this
     */
    public function setHorizontalBorderChar($horizontalBorderChar)
    {
        $this->horizontalBorderChar = $horizontalBorderChar;

        return $this;
    }

    /**
     * Gets horizontal border character.
     *
     * @return string
     */
    public function getHorizontalBorderChar()
    {
        return $this->horizontalBorderChar;
    }

    /**
     * Sets vertical border character.
     *
     * @param string $verticalBorderChar
     *
     * @return $this
     */
    public function setVerticalBorderChar($verticalBorderChar)
    {
        $this->verticalBorderChar = $verticalBorderChar;

        return $this;
    }

    /**
     * Gets vertical border character.
     *
     * @return string
     */
    public function getVerticalBorderChar()
    {
        return $this->verticalBorderChar;
    }

    /**
     * Sets crossing character.
     *
     * @param string $crossingChar
     *
     * @return $this
     */
    public function setCrossingChar($crossingChar)
    {
        $this->crossingChar = $crossingChar;

        return $this;
    }

    /**
     * Gets crossing character.
     *
     * @return string $crossingChar
     */
    public function getCrossingChar()
    {
        return $this->crossingChar;
    }
}
