<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/
use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;

use sale\booking\CompositionItem;


list($params, $providers) = announce([
    'description'   => "Returns a view populated with a collection of objects and outputs it as a PDF document.",
    'params'        => [
        'view_id' =>  [
            'description'   => 'The identifier of the view <type.name>.',
            'type'          => 'string',
            'default'       => 'list.print_form'
        ],
        'domain' =>  [
            'description'   => 'List of unique identifiers of the objects to read.',
            'type'          => 'array',
            'default'       => []
        ],
        'lang' =>  [
            'description'   => 'Language in which labels and multilang field have to be returned (2 letters ISO 639-1).',
            'type'          => 'string',
            'default'       => DEFAULT_LANG
        ]
    ],
    'access' => [
        'visibility'        => 'protected'
    ],
    'response'      => [
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\orm\ObjectManager    $orm
 */
list($context, $orm) = [$providers['context'], $providers['orm']];

$entity = $orm->getModel(CompositionItem::getType());

// get the complete schema of the object (including special fields)
$schema = $entity->getSchema();

// retrieve view schema
$json = run('get', 'model_view', [
    'entity'        => CompositionItem::getType(),
    'view_id'       => $params['view_id']
]);

// decode json into an array
$data = json_decode($json, true);

// relay error if any
if(isset($data['errors'])) {
    foreach($data['errors'] as $name => $message) {
        throw new Exception($message, qn_error_code($name));
    }
}

if(!isset($data['layout']['items'])) {
    throw new Exception('invalid_view', QN_ERROR_INVALID_CONFIG);
}

$view_schema = $data;


$view_fields = [];

foreach($view_schema['layout']['items'] as $item) {
    if(isset($item['type']) && isset($item['value']) && $item['type'] == 'field') {
        $view_fields[] = $item;
    }
}


/*
    Read targeted objects
*/

$fields_to_read = [];

// adapt fields to force retrieving name for m2o fields
foreach($view_fields as $item) {
    $field =  $item['value'];
    $descr = $schema[$field];
    if($descr['type'] == 'many2one') {
        $fields_to_read[$field] = ['id', 'name'];
    }
    else {
        $fields_to_read[] = $field;
    }
}


$limit = (isset($params['params']['limit']))?$params['params']['limit']:25;
$start = (isset($params['params']['start']))?$params['params']['start']:0;
$order = (isset($params['params']['order']))?$params['params']['order']:'id';
$sort = (isset($params['params']['sort']))?$params['params']['sort']:'asc';
if(is_array($order)) {
    $order = $order[0];
}
$values = CompositionItem::search($params['domain'], ['sort' => [$order => $sort]])->shift($start)->limit($limit)->read($fields_to_read)->get();




/*
    Retrieve Model translations
*/

// retrieve translation data (for fields names), if any
$json = run('get', 'config_i18n', [
    'entity'        => CompositionItem::getType(),
    'lang'          => $params['lang']
]);

// decode json into an array
$i18n = json_decode($json, true);
$translations = [];
if(!isset($i18n['errors']) && isset($i18n['model'])) {
    foreach($i18n['model'] as $field => $descr) {
        $translations[$field] = $descr;
    }
}

// retrieve view title
$view_title = $view_schema['name'];
$view_legend = $view_schema['description'];
if(isset($i18n['view'][$params['view_id']])) {
    $view_title = $i18n['view'][$params['view_id']]['name'];
    $view_legend = $i18n['view'][$params['view_id']]['description'];
}


/*
    Generate HTML
*/

$css = "
    @page {
        margin: 1cm;
        margin-top: 2cm;
        margin-bottom: 1.5cm;
    }
    body {
        font-family:sans-serif;
        font-size: 0.7em;
        color: black;
    }
    table {
        table-layout: fixed;
        border-left: 1px solid #aaa;
        border-right: 0;
        border-top: 1px solid #aaa;
        border-bottom: 0;
    }
    table, tr {
        width: 100%;
    }
    table, tr, td {
        border-spacing:0;
    }
    th, td {
        vertical-align: top;
        border-left: 0;
        border-right: 1px solid #aaa;
        border-top: 0;
        border-bottom: 1px solid #aaa;
    }
    td {
        padding: 0px 5px;
        text-align: left;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    td.allow-wrap {
        white-space: normal;
    }
    td.center {
        text-align: center;
    }
    td.right {
        text-align: right;
    }
    tr.sb-group-row td {
        font-weight: bold;
    }
    p {
        margin: 0;
        padding: 0;
    }";

$doc = new DOMDocument();

$html = $doc->appendChild($doc->createElement('html'));
$head = $html->appendChild($doc->createElement('head'));
$head->appendChild($doc->createElement('style', $css));
$body = $html->appendChild($doc->createElement('body'));
$table = $body->appendChild($doc->createElement('table'));


// 1) create head row

$row = createHeaderRow($doc, $view_fields, $translations);
$table->appendChild($row);


// 2) generate table lines (with group_by support)


foreach($values as $object) {
    $row = createObjectRow($doc, $object, $view_fields, $translations, $schema);
    $table->appendChild($row);
}


$html = $doc->saveHTML();


/*
    Generate PDF content
*/

// instantiate and use the dompdf class
$options = new DompdfOptions();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml((string) $html);
$dompdf->render();

$canvas = $dompdf->getCanvas();
$font = $dompdf->getFontMetrics()->getFont("helvetica", "regular");
$canvas->page_text(550, $canvas->get_height() - 35, "{PAGE_NUM} / {PAGE_COUNT}", $font, 9, array(0,0,0));
$canvas->page_text(30, $canvas->get_height() - 35, "Export - ".$view_legend, $font, 9, array(0,0,0));

$canvas->page_text(30, 30, $view_title, $font, 14, array(0,0,0));

/*
    Output PDF envelope
*/

// get generated PDF raw binary
$output = $dompdf->output();

$context->httpResponse()
        ->header('Content-Type', 'application/pdf')
        // ->header('Content-Disposition', 'attachment; filename="document.pdf"')
        ->header('Content-Disposition', 'inline; filename="document.pdf"')
        ->body($output)
        ->send();


function createHeaderRow($doc, &$view_items, &$translations) {
    $row = $doc->createElement('tr');
    $default_width = round(100.0 / count($view_items), 2);
    foreach($view_items as $item) {
        $field = $item['value'];
        $width = (isset($item['width']))?intval($item['width']):$default_width;
        if($width <= 0) {
            continue;
        }
        $name = isset($translations[$field])?$translations[$field]['label']:$field;
        $cell = $row->appendChild($doc->createElement('th', htmlspecialchars($name)));
        $cell->setAttribute('width', $width.'%');
    }
    return $row;
}

function createObjectRow($doc, $object, &$view_items, &$translations, &$schema) {
    $row = $doc->createElement('tr');
    $default_width = round(100.0 / count($view_items), 2);
    // for each field, create a widget, append to a cell, and append cell to row
    foreach($view_items as $item) {
        $field = $item['value'];
        $width = (isset($item['width']))?intval($item['width']):$default_width;
        if($width <= 0) continue;

        $value = $object[$field];

        $type = $schema[$field]['type'];
        // #todo - handle 'alias'
        if($type == 'computed') {
            $type = $schema[$field]['result_type'];
        }

        $class = 'center';

        if($field == 'rental_unit_id') {
            $value = $value['name'];
        }
        else {
            $value = "";
        }


        $cell = $doc->createElement('td', htmlspecialchars($value));

        $cell->setAttribute('class', $class);

        $row->appendChild($cell);
    }

    return $row;
}