<?php
/**
 * Created by PhpStorm.
 * User: Pavel Žůrek <PavelZrk@gmail.com>
 * Date: 25.10.17
 * Time: 9:32
 */

namespace Zofe\Rapyd\DataGrid;


class ActionColumn
{
    /** @var string $name */
    private $name;

    /** @var string $link */
    private $link = null;

    /** @var null|string $label */
    private $label;

    /** @var null|string $title */
    private $title;

    /** @var array $linkClasses */
    private $linkClasses = [];

    /** @var array $spanClasses */
    private $spanClasses = [];

    /** @var callable|string|null $customLink */
    private $customLink = null;

    /** @var array $customAttributes */
    private $customAttributes = [];

    /**
     * ActionColumn constructor.
     *
     * @param string $name
     * @param string|null $link
     * @param string|null $label
     */
    public function __construct($name, $link = null, $label = null)
    {
        $this->name = $name;
        $this->link = $link;
        $this->label = $label;
        $this->title = $label;
    }

    /**
     * Add class.
     *
     * @param string $class
     * @param array $array
     * @return $this
     */
    private function addClass($class, &$array)
    {
        if (!is_array($class)) {
            $class = explode(' ', $class);
        }

        foreach ($class as $cl) {
            if (!in_array($cl, $array)) {
                $array[] = $cl;
            }
        }

        return $this;
    }

    /**
     * Remove class by class name.
     *
     * @param string|array $class
     * @param array $array
     * @return $this
     */
    private function removeClass($class, &$array)
    {
        if (!is_array($class)) {
            $class = explode(' ', $class);
        }

        foreach ($class as $cl) {
            if (in_array($cl, $array)) {
                $key = array_search($cl, $array);
                unset($array[$key]);
            }
        }

        return $this;
    }

    /**
     * Add inner span class.
     *
     * @param array|string $class
     * @return $this
     */
    public function addLinkClass($class)
    {
        return $this->addClass($class, $this->linkClasses);
    }

    /**
     * Remove span class by class name.
     *
     * @param array|string $class
     * @return $this
     */
    public function removeLinkClass($class)
    {
        return $this->removeClass($class, $this->linkClasses);
    }

    /**
     * Add inner span class.
     *
     * @param array|string $class
     * @return $this
     */
    public function addSpanClass($class)
    {
        return $this->addClass($class, $this->spanClasses);
    }

    /**
     * Remove span class by class name.
     *
     * @param array|string $class
     * @return $this
     */
    public function removeSpanClass($class)
    {
        return $this->removeClass($class, $this->spanClasses);
    }

    /**
     * Set link title.
     *
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Call with `null` parameter to remove custom href.
     *
     * @param callable|string|null $link = null Callbacks first parameter is Model
     * @return $this
     */
    public function setCustomHref($link = null)
    {
        $this->customLink = $link;
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
    public function setCustomAttribute($attribute, $value, $appendValue = false)
    {
        if (in_array($attribute, ['class', 'href', 'title'])) {
            throw new \InvalidArgumentException('Can\'t set attribute `' . $attribute . '` with method setCustomAttribute. Use concrete method instead.`');
        }

        if ($appendValue && array_key_exists($attribute, $this->customAttributes)) {
            $this->customAttributes[$attribute] .= $value;
        } else {
            $this->customAttributes[$attribute] = $value;
        }

        return $this;
    }

    /**
     * Get HTML link.
     *
     * @param $entity
     * @return string
     */
    public function toHtml($entity)
    {
        try {
            if (null == $this->customLink) {
                $link = route($this->link, $entity);
            } else if (is_callable($this->customLink)) {
                $method = $this->customLink;
                $link = $method($entity);
            } else {
                $link = $this->customLink;
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid action link for action `' . $this->name . '`.', 0, $e);
        }

        $custonAttributes = '';
        foreach ($this->customAttributes as $attribute => $value) {
            if (is_bool($value)) {
                $custonAttributes .= $value ? ' ' . $attribute : '';
            } else {
                $custonAttributes .= ' ' . $attribute . '="' . $value . '"';
            }
        }

        $innerSpan = (count($this->spanClasses) > 0) ? '<span class="' . implode(' ', $this->spanClasses) . '"></span>' : '';

        return  '<a class="' . implode(' ', $this->linkClasses) . '" title="' . $this->title . '" href="' . $link . '"' . $custonAttributes . '>'.
                    $innerSpan.
                    $this->label.
                '</a>';
    }
}
