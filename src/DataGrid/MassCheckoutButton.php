<?php
namespace Zofe\Rapyd\DataGrid;


class MassCheckoutButton
{
    /**
     * @var string $name
     */
    public $name;

    /**
     * @var string $link
     */
    private $link;

    /**
     * @var array $classes
     */
    private $classes = ['btn'];

    /**
     * @var string $title
     */
    private $title;

    /**
     * @var string $label
     */
    private $label;

    /**
     * @var string $method
     */
    public $method = 'post';

    /**
     * @var array $customAttributes
     */
    private $customAttributes = ['data-masscheckbox-button'];


    /**
     * @var array $customFormAttributes
     */
    private $customFormAttributes = [];

    /**
     * AllCheckoutButton constructor.
     *
     * @param string $name
     * @param string $link
     */
    public function __construct($name, $link)
    {
        $this->name = $name;
        $this->link = $link;
    }


    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }


    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }


    /**
     * @param string $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }


    /**
     * Add class.
     *
     * @param string|array $class
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
     * @param $data
     * @param string $value
     */
    public function setDataAttribute($data, $value = '')
    {
        if ($value) {
            $this->customAttributes[$data] = $value;
        }
        else {
            $this->customAttributes[] = $data;
        }
        return $this;
    }


    /**
     * @param $data
     * @param string $value
     */
    public function setFormDataAttribute($data, $value = '')
    {
        if ($value) {
            $this->customFormAttributes[$data] = $value;
        }
        else {
            $this->customFormAttributes[] = $data;
        }
        return $this;
    }


    /**
     * @return string
     */
    public function getCustomFormAttributes()
    {
        $customAttributes = [];
        foreach ($this->customFormAttributes as $attribute => $value) {
            if (is_numeric($attribute)) {
                $customAttributes[] = ' '.$value;
            }
            else if (is_bool($value)) {
                $customAttributes[] = $value ? ' ' . $attribute : '';
            } else {
                $customAttributes[] = ' ' . $attribute . '="' . $value . '"';
            }
        }

        return $customAttributes;
    }


    public function toHtml()
    {
        try {
            if (is_callable($this->link)) {
                $method = $this->link;
                $link = $method($entity);
            } else {
                $link = $this->link;
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid action link for action `' . $this->name . '`.', 0, $e);
        }

        $customAttributes = '';
        foreach ($this->customAttributes as $attribute => $value) {
            if (is_numeric($attribute)) {
                $customAttributes .= ' '.$value;
            }
            else if (is_bool($value)) {
                $customAttributes .= $value ? ' ' . $attribute : '';
            } else {
                $customAttributes .= ' ' . $attribute . '="' . $value . '"';
            }
        }

        $label = $this->label;
        $title = $this->title;
        if (!$label && $this->title)
        {
            $label = $this->title;
        }
        if (!$title)
        {
            $title = $this->label;
        }

        return  '<button class="' . implode(' ', $this->classes) . '" title="' . $title . '" 
                    data-name="'.$this->name.'" 
                    data-url="' . $link . '"' . $customAttributes . '                    
                 >'.$label.'</button>';
    }
}