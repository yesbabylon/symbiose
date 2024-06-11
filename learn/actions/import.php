<?php

use learn\Course;
use learn\Module;
use learn\Chapter;
use learn\Page;
use learn\Leaf;
use learn\Group;
use learn\Widget;

list($params, $providers) = announce([
    'description' => "",
    'params' => [
    ],
    'response' => [
        'content-type' => 'application/json',
        'charset' => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers' => ['context', 'orm', 'auth'],
    'constants' => ['ROOT_APP_URL'],
]);

$pack = Course::search([['id', '=', 1]])->first();

if (!$pack) {
    $title = 'YesBabylon test pack';
    Course::create([
        'id' => 1,
        'title' => $title,
        'subtitle' => 'Découvrer notre pack de test',
        'description' => 'lorem ipsum dolor sit amet babylon',
        'link' => constant('ROOT_APP_URL') . '/learning/#/' . $title
    ]);
}


for ($i = 1; $i <= 8; $i++) {
    $data = file_get_contents(dirname(__DIR__) . "/init/modules/Module" . $i . ".json");

    $module = json_decode($data, true);

    $oModule = Module::create([
        'identifier' => $module['identifier'],
        'title' => $module['title'],
        'description' => $module['description'],
        'duration' => $module['duration'],
        'course_id' => 1,
        'order' => $module['identifier']
    ])->first();

    foreach ($module['chapters'] as $chapter_index => $chapter) {
        $oChapter = Chapter::create([
            'identifier' => $chapter_index + 1,
            'title' => $chapter['title'],
            'module_id' => $oModule['id']
        ])->first();

        foreach ($chapter['pages'] as $page_index => $page) {

            $object = $page;

            if (isset($object['id'])) {
                unset($object['id']);
            }

            if (isset($page['next_active'])) {
                $object['next_active'] = _json_to_string($page['next_active']);
            }
            $object['identifier'] = $page_index + 1;
            $object['chapter_id'] = $oChapter['id'];

            $oPage = Page::create($object)->first();

            if (!isset($page['leaves'])) continue;

            foreach ($page['leaves'] as $leaf_index => $leaf) {

                $object = $leaf;
                if (isset($object['id'])) {
                    unset($object['id']);
                }
                if (isset($object['groups'])) {
                    unset($object['groups']);
                }
                $object['identifier'] = $leaf_index + 1;
                $object['page_id'] = $oPage['id'];

                if (isset($object['visible'])) {
                    $object['visible'] = _json_to_string($leaf['visible']);
                }

                $oLeaf = Leaf::create($object)->first();

                if (!isset($leaf['groups'])) continue;

                foreach ($leaf['groups'] as $group_index => $group) {

                    $object = $group;
                    if (isset($object['id'])) {
                        unset($object['id']);
                    }
                    if (isset($object['widgets'])) {
                        unset($object['widgets']);
                    }
                    if (isset($object['visible'])) {
                        $object['visible'] = _json_to_string($group['visible']);
                    }
                    $object['identifier'] = $group_index + 1;
                    $object['leaf_id'] = $oLeaf['id'];

                    $oGroup = Group::create($object)->first();

                    if (!isset($group['widgets'])) continue;

                    foreach ($group['widgets'] as $widget_index => $widget) {

                        if (isset($widget['id'])) {
                            unset($widget['id']);
                        }

                        $widget['group_id'] = $oGroup['id'];
                        $widget['identifier'] = $widget_index + 1;

                        if (isset($widget['content'])) {
                            $widget['content'] = _md_to_html($widget['content']);
                        }

                        Widget::create($widget);
                    }
                }
            }
        }
    }
}


function _json_to_string($a): string
{
    $res = '';
    if (count($a)) {
        list($operand, $operator, $value) = $a;
        $res = "'$operand','$operator',";
        if (is_numeric($value) || is_bool($value)) {
            if (is_bool($value)) {
                $res .= ($value) ? 'true' : 'false';
            } else {
                $res .= "$value";
            }
        } else {
            $res .= "'$value'";
        }
    }
    return '[' . $res . ']';
}


function _md_to_html($s): string
{

    $s = preg_replace('/ {2}\* /im', ' * ', $s);
    $s = preg_replace('/ {2}([0-9]{1,2}\.) /im', '┌$1', $s);
    $s = preg_replace('/ {2}/im', '┐', $s);
    $s = preg_replace('/\*\*(.*?)\*\*/im', '<b>$1</b>', $s);
    $s = preg_replace('/\* ([^*┐]*)/im', '<ul><li>$1</li></ul>', $s);
    $s = preg_replace('/\*([^*]*)\*/im', '<em>$1</em>', $s);
    $s = preg_replace('/([0-9]{1,2})\. ([^┐┌]*)/im', '<ol start="$1"><li>$2</li></ol>', $s);
    $s = preg_replace('/┌/m', '', $s);
    $s = preg_replace('/┐/m', '</p><p>', $s);

    return '<p>' . $s . '</p>';
}