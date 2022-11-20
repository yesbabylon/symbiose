<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use documents\Document;
use equal\data\DataAdapter;

list($params, $providers) = announce([
    'description'   => 'Return raw data (with original MIME) of a document identified by given hash.',
    'params'        => [
        'name' =>  [
            'description'   => 'class parameters.',
            'type'          => 'string',
            'required'      => true
        ],
        'field_name' =>  [
            'description'   => 'class parameters.',
            'type'          => 'array'
        ],
        'description' =>  [
            'description'   => 'class parameters.',
            'type'          => 'array',
            'required'      => true
        ],
        'types' =>  [
            'description'   => 'class parameters.',
            'type'          => 'array',

        ],
        'multilang' =>  [
            'description'   => 'class parameters.',
            'type'          => 'array',

        ],
        'unique' =>  [
            'description'   => 'class parameters.',
            'type'          => 'array',

        ],
        'usage' =>  [
            'description'   => 'class parameters.',
            'type'          => 'array',

        ],
        'foreign_object' =>  [
            'description'   => 'class parameters.',
            'type'          => 'array',

        ],
        'foreign_field' =>  [
            'description'   => 'class parameters.',
            'type'          => 'array',

        ],
        'rel_table' =>  [
            'description'   => 'class parameters.',
            'type'          => 'array',

        ],
        'rel_foreign_key' =>  [
            'description'   => 'class parameters.',
            'type'          => 'array',

        ],
        'rel_local_key' =>  [
            'description'   => 'class parameters.',
            'type'          => 'array',

        ],
        'package' =>  [
            'description'   => 'class parameters.',
            'type'          => 'string',

        ],
        'subpackage' =>  [
            'description'   => 'class parameters.',
            'type'          => 'string',

        ],

    ],
    'access' => [
        'visibility'        => 'public'
    ],
    'response'      => [
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

list($context, $om, $auth) = [ $providers['context'], $providers['orm'], $providers['auth'] ];


// only creates
$myfile = fopen('packages/'.$params['package'].'/classes'.($params['subpackage']?'/'.$params['subpackage']:'').'/'.$params['name'].'.class.php','w');


$text = "";
foreach($params['field_name'] as $key => $field){
    $description = isset($params['description'][$key])?"description => {$params['description'][$key]},":"";
    $field_name = isset($params['description'][$key])?"description => {$params['description'][$key]},":"";;
    $types = isset($params['description'][$key])?"description => {$params['description'][$key]},":"";;
    $multilang = isset($params['description'][$key])?"description => {$params['description'][$key]},":"";;
    $unique = isset($params['description'][$key])?"description => {$params['description'][$key]},":"";;
    $usage = isset($params['description'][$key])?"description => {$params['description'][$key]},":"";;
    $foreign_object = isset($params['description'][$key])?"description => {$params['description'][$key]},":"";;
    $foreign_field = isset($params['description'][$key])?"description => {$params['description'][$key]},":"";;
    $rel_table = isset($params['description'][$key])?"description => {$params['description'][$key]},":"";;
    $rel_foreign_key = isset($params['description'][$key])?"description => {$params['description'][$key]},":"";;
    $rel_local_key = isset($params['description'][$key])?"description => {$params['description'][$key]},":"";;
    $text .= "{$params['field_name']} => [
            {$params['description']}
            {$params['types']}
            {$params['multilang']}
            {$params['unique']}
            {$params['usage']}
            {$params['foreign_object']}
            {$params['foreign_field']}
            {$params['rel_table']}
            {$params['rel_foreign_key']}
            {$params['rel_local_key']}
        ]";
};

$txt = "
<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
namespace '{$params['package']}'
use equal\orm\Model;
class {$params['name']} extends Model {
    public static function getColumns() {
        return ["
."
        ]
    }
}
";

fwrite($myfile, $txt);

$context->httpResponse()
        ->body('ok')
        ->send();