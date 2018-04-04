<?php namespace Zofe\Rapyd\DataGrid;

use Zofe\Rapyd\DataSet as DataSet;
use Zofe\Rapyd\Persistence;
use Illuminate\Support\Facades\Config;
use Zofe\Rapyd\Rapyd;

class DataGrid extends DataSet
{

    protected $fields = array();
    /** @var Column[]  */
    public $columns = array();
    /** @var ActionColumn[]  */
    public $actionColumns = array();
    public $checkboxColumns = array();
    public $headers = array();
    public $rows = array();
    public $output = "";
    public $attributes = array("class" => "table");
    public $checkbox_form = false;
    public $massCheckoutButtons = [];
    
    protected $row_callable = array();
    /**
     * @var []
     */
    protected $rowsBefore = [];
    /**
     * @var []
     */
    protected $rowsAfter = [];

    /**
     * @param string $name
     * @param string $label
     * @param bool $orderby
     *
     * @return Column
     * @throws \Exception
     */
    public function add($name, $label = null, $orderby = false)
    {
        $column = new Column($name, $label, $orderby);
        $this->columns[$column->name] = $column;
        if (!in_array($name,array("_edit"))) {
            $this->headers[] = $label;
        }
        if ($orderby) {
            $this->addOrderBy($column->orderby_field);
        }
        return $column;
    }

    //todo: like "field" for DataForm, should be nice to work with "cell" as instance and "row" as collection of cells
    public function build($view = '')
    {
        if ($this->output != '') return;
        ($view == '') and $view = 'rapyd::datagrid';
        parent::build();

        Persistence::save();

        $this->buildAfterAndBeforeRows($this->rowsBefore);

        foreach ($this->data as $tablerow) {

            $row = new Row($tablerow);

            foreach ($this->columns as $column) {

                if ($column instanceof CheckboxColumn) {
                    $actionCell = new Cell($column->name);
                    $actionCell->attributes(['class' => 'checkbox-collumn']);
                    $actionCell->value($column->toHtml($tablerow));
                    $row->add($actionCell);
                }
                else {
                    $cell = new Cell($column->name);
                    $sanitize = (count($column->filters) || $column->cell_callable) ? false : true;
                    $value = $this->getCellValue($column, $tablerow, $sanitize);
                    $cell->value($value);
                    $cell->parseFilters($column->filters);
                    if ($column->cell_callable) {
                        $callable = $column->cell_callable;
                        $cell->value($callable($cell->value, $tablerow));
                    }
                    $row->add($cell);
                }
            }

            if (count($this->row_callable)) {
                foreach ($this->row_callable as $callable) {
                    $callable($row);
                }
            }

            if (sizeof($this->actionColumns) > 0) {
                $actionCell = new Cell('Actions');
                $actionCellValue = '';
                /** @var ActionColumn $actionColumn */
                foreach ($this->actionColumns as $actionColumn) {
                    $actionCellValue .= $actionColumn->toHtml($tablerow);
                }
                $actionCell->attributes(['class' => 'action-collumn']);
                $actionCell->value($actionCellValue);
                $row->add($actionCell);
            }

            $this->rows[] = $row;
        }

        $this->buildAfterAndBeforeRows($this->rowsAfter);

        $this->output = \View::make($view, array('dg' => $this, 'buttons'=>$this->button_container, 'label'=>$this->label))->render();
        return $this->output;
    }


    /**
     * @param array $rows
     */
    private function buildAfterAndBeforeRows($rows)
    {
        /** @var Row $row */
        foreach ($rows as $row) {
            foreach ($this->columns as $column) {
                if (!$row->cell($column->name)) {
                    $row->add(new Cell($column->name));
                }
            }
            $this->rows[] = $row;
        }
    }


    public function buildCSV($file = '', $timestamp = '', $sanitize = true,$del = array())
    {
        $this->limit = null;
        parent::build();

        $segments = \Request::segments();

        $filename = ($file != '') ? basename($file, '.csv') : end($segments);
        $filename = preg_replace('/[^0-9a-z\._-]/i', '',$filename);
        $filename .= ($timestamp != "") ? date($timestamp).".csv" : ".csv";

        $save = (bool) strpos($file,"/");

        //Delimiter
        $delimiter = array();
        $delimiter['delimiter'] = isset($del['delimiter']) ? $del['delimiter'] : ';';
        $delimiter['enclosure'] = isset($del['enclosure']) ? $del['enclosure'] : '"';
        $delimiter['line_ending'] = isset($del['line_ending']) ? $del['line_ending'] : "\n";

        if ($save) {
            $handle = fopen(public_path().'/'.dirname($file)."/".$filename, 'w');

        } else {

            $headers  = array(
                'Content-Type' => 'text/csv',
                'Pragma'=>'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Content-Disposition' => 'attachment; filename="' . $filename.'"');

            $handle = fopen('php://output', 'w');
            ob_start();
        }

        fputs($handle, $delimiter['enclosure'].implode($delimiter['enclosure'].$delimiter['delimiter'].$delimiter['enclosure'], $this->headers) .$delimiter['enclosure'].$delimiter['line_ending']);

        foreach ($this->data as $tablerow) {
            $row = new Row($tablerow);

            foreach ($this->columns as $column) {

                if (in_array($column->name,array("_edit")))
                    continue;

                $cell = new Cell($column->name);
                $value =  str_replace('"', '""',str_replace(PHP_EOL, '', strip_tags($this->getCellValue($column, $tablerow, $sanitize))));

                // Excel for Mac is pretty stupid, and will break a cell containing \r, such as user input typed on a
                // old Mac.
                // On the other hand, PHP will not deal with the issue for use, see for instance:
                // http://stackoverflow.com/questions/12498337/php-preg-replace-replacing-line-break
                // We need to normalize \r and \r\n into \n, otherwise the CSV will break on Macs
                $value = preg_replace('/\r\n|\n\r|\n|\r/', "\n", $value);

                $cell->value($value);
                $row->add($cell);
            }

            if (count($this->row_callable)) {
                foreach ($this->row_callable as $callable) {
                    $callable($row);
                }
            }

            fputs($handle, $delimiter['enclosure'] . implode($delimiter['enclosure'].$delimiter['delimiter'].$delimiter['enclosure'], $row->toArray()) . $delimiter['enclosure'].$delimiter['line_ending']);
        }

        fclose($handle);
        if ($save) {
            //redirect, boolean or filename?
        } else {
            $output = ob_get_clean();

            return \Response::make(rtrim($output, "\n"), 200, $headers);
        }
    }

    protected function getCellValue($column, $tablerow, $sanitize = true)
    {
        //blade
        if (strpos($column->name, '{{') !== false || 
            strpos($column->name, '{!!') !== false) {

            if (is_object($tablerow) && method_exists($tablerow, "getAttributes")) {
                $fields = $tablerow->getAttributes();
                $relations = $tablerow->getRelations();
                $array = array_merge($fields, $relations) ;

                $array['row'] = $tablerow;

            } else {
                $array = (array) $tablerow;
            }

            $value = $this->parser->compileString($column->name, $array);

        //eager loading smart syntax  relation.field
        } elseif (preg_match('#^[a-z0-9_-]+(?:\.[a-z0-9_-]+)+$#i',$column->name, $matches) && is_object($tablerow) ) {
            //switch to blade and god bless eloquent
            $_relation = '$'.trim(str_replace('.','->', $column->name));
            $expression = '{{ isset('. $_relation .') ? ' . $_relation . ' : "" }}';
            $fields = $tablerow->getAttributes();
            $relations = $tablerow->getRelations();
            $array = array_merge($fields, $relations) ;
            $value = $this->parser->compileString($expression, $array);

        //fieldname in a collection
        } elseif (is_object($tablerow)) {

            $value = @$tablerow->{$column->name};
            if ($sanitize) {
                $value = $this->sanitize($value);
            }
        //fieldname in an array
        } elseif (is_array($tablerow) && isset($tablerow[$column->name])) {

            $value = $tablerow[$column->name];

        //none found, cell will have the column name
        } else {
            $value = $column->name;
        }

        //decorators, should be moved in another method
        if ($column->link) {
            if (is_object($tablerow) && method_exists($tablerow, "getAttributes")) {
                $array = $tablerow->getAttributes();
                $array['row'] = $tablerow;
            } else {
                $array = (array) $tablerow;
            }
            $value =  '<a href="'.$this->parser->compileString($column->link, $array).'">'.$value.'</a>';
        }
        if (count($column->actions)>0) {
            $key = ($column->key != '') ?  $column->key : $this->key;
            $keyvalue = @$tablerow->{$key};

            $value = \View::make('rapyd::datagrid.actions', array('uri' => $column->uri, 'id' => $keyvalue, 'actions' => $column->actions));

        }

        return $value;
    }

    public function getGrid($view = '')
    {
        $this->build($view);

        return $this->output;
    }

    public function __toString()
    {
        if ($this->output == "") {

           //to avoid the error "toString() must not throw an exception"
           //http://stackoverflow.com/questions/2429642/why-its-impossible-to-throw-exception-from-tostring/27307132#27307132
           try {
               $this->getGrid();
           }
           catch (\Exception $e) {
               $previousHandler = set_exception_handler(function (){ });
               restore_error_handler();
               call_user_func($previousHandler, $e);
               die;
           }

        }

        return $this->output;
    }

    public function edit($uri, $label='Edit', $actions='show|modify|delete', $key = '')
    {
        return $this->add('_edit', $label)->actions($uri, explode('|', $actions))->key($key);
    }

    public function getColumn($column_name)
    {
        if (isset($this->columns[$column_name])) {
            return $this->columns[$column_name];
        }
    }

    public function addActions($uri, $label='Edit', $actions='show|modify|delete', $key = '')
    {
        return $this->edit($uri, $label, $actions, $key);
    }

    public function row(\Closure $callable)
    {
        $this->row_callable[] = $callable;

        return $this;
    }

    protected function sanitize($string)
    {
        $result = nl2br(htmlspecialchars($string));
        return Config::get('rapyd.sanitize.num_characters') > 0 ? str_limit($result, Config::get('rapyd.sanitize.num_characters')) : $result;
    }

    /**
     * @param string $name
     * @param null|string $link
     * @param null|string $label
     * @return ActionColumn
     */
    public function addActionColumn($name, $link = null, $label = null, $asForm = false) {
        $actionColumn = new ActionColumn($name, $link, $label, $asForm);
        $this->actionColumns[$name] = $actionColumn;
        return $actionColumn;
    }

    /**
     * @param string $name
     * @param null|string $link
     * @param null|string $label
     * @return ActionColumnForm
     */
    public function addActionColumnForm($name, $link = null, $label = null, $asForm = false) {
        $actionColumn = new ActionColumnForm($name, $link, $label, $asForm);
        $this->actionColumns[$name] = $actionColumn;
        return $actionColumn;
    }


    /**
     * @param $name
     * @return CheckboxColumn
     */
    public function addMassCheckbox() {
        Rapyd::js('masscheckbox/masscheckbox.js');
        Rapyd::css('masscheckbox/masscheckbox.css');
        $this->columns['masscheckbox'] = new CheckboxColumn();;
        return $this->columns['masscheckbox'];
    }


    public function addMassCheckboxButton($name, $link) {
        $this->massCheckoutButtons[$name] = new MassCheckoutButton($name, $link);
        return $this->massCheckoutButtons[$name];
    }


    /**
     * @param array $data
     * @return Row
     */
    private function createRow($data = [])
    {
        $row = new Row($data);
        foreach ($data as $column => $value) {
            $cell = new Cell($column);
            $cell->value($value);
            $row->add($cell);
        }
        // create all columns for row
        foreach ($this->columns as $column) {
            if (!$row->cell($column->name)) {
                $cell = new Cell($column->name);
                $row->add($cell);
            }
        }

        return $row;
    }


    /**
     * @param string $name
     * @param array $data
     * @return mixed
     */
    public function addRowBefore($name, $data = [])
    {
        $this->rowsBefore[$name] = $this->createRow($data);

        return $this->rowsBefore[$name];
    }


    /**
     * @param string $name
     * @param array $data
     * @return mixed
     */
    public function addRowAfter($name, $data = [])
    {
        $this->rowsAfter[$name] = $this->createRow($data);

        return $this->rowsAfter[$name];
    }
}
