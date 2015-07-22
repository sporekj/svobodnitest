<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\Components\Filters;

/**
 * Date input filter.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 *
 * @property string $dateFormatInput
 * @property string $dateFormatOutput
 */
class Date extends Text
{
    const DEFAULT_CONDITION = '= ?';

    /** @var string */
    protected $condition = self::DEFAULT_CONDITION;

    /** @var string */
    protected $formatValue;

    /**
     * @var string
     */
    protected $dateFormatInput = 'd.m.Y';

    /**
     * @var string
     */
    protected $dateFormatOutput = 'Y-m-d';

    /**
     * Sets date-input format.
     * @param string $format
     * @return Date
     */
    public function setDateFormatInput($format)
    {
        $this->dateFormatInput = $format;
        return $this;
    }

    /**
     * Returns date-input format.
     * @return string
     */
    public function getDateFormatInput()
    {
        return $this->dateFormatInput;
    }

    /**
     * Sets date-output format.
     * @param string $format
     * @return Date
     */
    public function setDateFormatOutput($format)
    {
        $this->dateFormatOutput = $format;
        return $this;
    }

    /**
     * Returns date-output format.
     * @return string
     */
    public function getDateFormatOutput()
    {
        return $this->dateFormatOutput;
    }

    /**
     * @return \Nette\Forms\Controls\TextInput
     */
    protected function getFormControl()
    {
        $control = parent::getFormControl();
        $control->getControlPrototype()->class[] = 'date';
        $control->getControlPrototype()->attrs['autocomplete'] = 'off';

        return $control;
    }

    /**
     * @param string $value
     * @return Condition
     * @throws \Exception
     * @internal
     */
    public function __getCondition($value)
    {
        if ($this->where === NULL && $this->condition == self::DEFAULT_CONDITION) {
            return ($date = \DateTime::createFromFormat($this->dateFormatInput, $value))
                ? Condition::setupFromArray(array($this->getColumn(), $this->condition, $date->format($this->dateFormatOutput)))
                : Condition::setupEmpty();
        }

        return parent::__getCondition($value);
    }
}