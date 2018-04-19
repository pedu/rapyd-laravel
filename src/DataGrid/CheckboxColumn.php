<?php
/**
 * Created by PhpStorm.
 * User: Pavel Žůrek <PavelZrk@gmail.com>
 * Date: 25.10.17
 * Time: 9:32
 */

namespace Zofe\Rapyd\DataGrid;


class CheckboxColumn
{
    /** @var array $classes */
    protected $classes = [];

    /** @var array $customAttributes */
    protected $customAttributes = ['data-masscheckbox'];

    public $name = 'masscheckbox';

    /**
     * @var string
     */
    protected $placeholder = '';
    protected $displayWhenMethods = [];


    /**
     * Add class.
     *
     * @param string $class
     * @return $this
     */
    public function addClass($classes)
    {
        if (!is_array($classes))
        {
            $classes = explode(' ', $classes);
        }

        foreach ($classes as $cl)
        {
            if (!in_array($cl, $this->classes))
            {
                $this->classes[] = $cl;
            }
        }

        return $this;
    }

    /**
     * Set attribute other then class, href or title.
     *
     * @param string $attribute
     * @param string|bool $value
     * @param bool $appendValue If attribute already exists, append value to it, instead rewriting.
     * @return $this
     */
    public function setCustomAttribute($attribute, $value = null, $appendValue = false)
    {
        if (in_array($attribute, ['class', 'title']))
        {
            throw new \InvalidArgumentException('Can\'t set attribute `'.$attribute.'` with method setCustomAttribute. Use concrete method instead.`');
        }

        if ($appendValue && array_key_exists($attribute, $this->customAttributes))
        {
            $this->customAttributes[$attribute] .= $value;
        }
        else
        {
            $this->customAttributes[$attribute] = $value;
        }

        return $this;
    }


    /**
     * can be used to specify, if action button displays or not
     *
     * @param $method
     * @return ActionColumnForm
     */
    public function setDisplayWhen($method) {
        $this->displayWhenMethods[] = $method;

        return $this;
    }


    /**
     * @param string $s
     */
    public function setCanNotDisplayPlaceholder(string $s) {
        $this->placeholder = $s;
    }


    /**
     * @param $entity
     * @return bool
     */
    protected function canDisplay($entity) {
        foreach ($this->displayWhenMethods as $method) {
            if (is_callable($method)) {
                if (!$method($entity)) {
                    return false;
                }
            }
            else {
                if (!$method) {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * Get HTML link.
     *
     * @param $entity
     * @return string
     */
    public function toHtml($entity)
    {
        if (!$this->canDisplay($entity)) {
            return $this->placeholder;
        }

        $customAttribute = '';
        foreach ($this->customAttributes as $attribute => $value)
        {
            if (is_numeric($attribute)) {
                $customAttribute .= ' '.$value;
            }
            else if (is_bool($value)) {
                $customAttribute .= $value ? ' '.$value : '';
            }
            else {
                $customAttribute .= ' '.$attribute.'="'.$value.'"';
            }
        }

        return '<input type="checkbox" value="1" data-id="'.$entity->id.'" class="'.implode(' ', $this->classes).'" '.$customAttribute.'/>';
    }


    public function buildAttributes()
    {
        return '';
    }
}
