<?php
/**
 * Created by PhpStorm.
 * User: Pavel Žůrek <PavelZrk@gmail.com>
 * Date: 25.10.17
 * Time: 9:32
 */

namespace Zofe\Rapyd\DataGrid;

use Collective\Html\FormBuilder as Form;

class ActionColumnForm extends ActionColumn
{

    private $formParameters = array(
        'method' => 'POST',
        'class' => 'delete-form pull-left',
        'data-confirm',
    );

    private $buttonParameters = array(
        'type' => 'Submit',
        'class' => 'btn'
    );

    private $displayWhenMethods = [];

    /**
     * if can not display, displays this
     * @var string
     */
    private $placeholder = '';

    /**
     * @param $label
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
    public function setMethod(string $method)
    {
        $this->formParameters['method'] = strtoupper($method);

        return $this;
    }


    /**
     * @param array $parameters
     * @return $this
     */
    public function setFormParameters(array $parameters)
    {
        $this->formParameters = $parameters;

        return $this;
    }


    /**
     * @param array $parameters
     * @return $this
     */
    public function setButtonParameters(array $parameters)
    {
        $this->buttonParameters = $parameters;

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

        try
        {
            if (null == $this->customLink)
            {
                $link = route($this->link, $entity);
            }
            else if (is_callable($this->customLink))
            {
                $method = $this->customLink;
                $link = $method($entity);
            }
            else
            {
                $link = $this->customLink;
            }
        }
        catch (\Exception $e)
        {
            throw new \InvalidArgumentException('Invalid action link for action `'.$this->name.'`.', 0, $e);
        }


        $this->formParameters['url'] = $link;
        $this->buttonParameters['title'] = $this->title;

        $form = app('form');


        return $form->open($this->formParameters)
            .$form->button($this->label, $this->buttonParameters)
            .$form->close();

    }
 }
