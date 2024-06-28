<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace learn;

use equal\orm\Model;

class Widget extends Model {

    public static function getColumns() {
        return [

            'identifier' => [
                'type'              => 'integer',
                'description'       => 'Unique identifier of the widget within the group.',
                'default'           => 1
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Position of the widget in the group.',
                'default'           => 1
            ],

            'content' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Content of the widget (markdown support).',
                'default'           => '',
//                 'onupdate'          => 'learn\Widget::onupdateContent'
                'multilang'         => true
            ],

            'group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'learn\Group',
                'description'       => 'Parent group.',
                'ondelete'          => 'cascade'         // delete widget when parent group is deleted
            ],

            'type' => [
                'type'              => 'string',
                'onupdate'          => 'onupdateType',
                'selection'         => [
                    'text'                  => 'Text (free format)',
                    'code'                  => 'Code (highlight)',
                    'chapter_number'        => 'Chapter number',
                    'chapter_title'         => 'Chapter title',
                    'chapter_description'   => 'Chapter description',
                    'page_title'            => 'Page title',
                    'headline'              => 'Headline',
                    'subtitle'              => 'Subtitle',
                    'head_text'             => 'Head text',
                    'tooltip'               => 'Tooltip',
                    'sound'                 => 'Sound',
                    'video'                 => 'Video',
                    'image_popup'           => 'Image',
                    'first_capital'         => 'Acronym (caps on first letter)',
                    'submit_button'         => 'Button: submit',
                    'selector'              => 'Button: selector',
                    'selector_wide'         => 'Button: selector wide',
                    'selector_yes_no'       => 'Button: yes/no',
                    'selector_choice'       => 'Button: selector choice',
                    'selector_section'      => 'Button: selector section',
                    'selector_section_wide' => 'Button: selector section wide',
                    'selector_popup'        => 'Button: selector popup'
                ],
                'default'           => 'text'
            ],

            'image_url' => [
                'type'              => 'string',
                'description'       => "URL of the image.",
                'visible'           => ['type', 'in', ['image_popup', 'selector_popup', 'selector_section', 'selector_section_wide']]
            ],

            'video_url' => [
                'type'              => 'string',
                'description'       => "URL of the video.",
                'visible'           => ['type', '=', 'video']
            ],

            'sound_url' => [
                'type'              => 'string',
                'description'       => "URL of the sound.",
                'visible'           => ['type', '=', 'sound']
            ],

            'has_separator_left' => [
                'type'              => 'boolean',
                'default'           => false
            ],

            'has_separator_right' => [
                'type'              => 'boolean',
                'default'           => false
            ],

            'align' => [
                'type'              => 'string',
                'selection'         => [
                    'none'      => 'inherit',
                    'left'      => 'left',
                    'right'     => 'right'
                ],
                'default'           => 'none'
            ],

            'on_click' => [
                'type'              => 'string',
                'selection'         => [
                    'ignore'        => 'do nothing',
//                    'select()'    => 'select',
                    'select_one()'  => 'select',
                    'submit()'      => 'submit',
                    'image_full()'  => 'show image',
                    'play()'        => 'play media'
                ],
                'default'           => 'ignore'
            ]

        ];
    }

    public static function onupdateContent($orm, $oids, $values, $lang) {
        trigger_error("ORM::calling learn\Widget:onupdateContent", QN_REPORT_DEBUG);
        $res = $orm->read(__CLASS__, $oids, ['content'], $lang);

        if($res > 0 && count($res)) {
            foreach($res as $oid => $odata) {
                if(strpos($odata['content'], '</p>') !== false) {
                    $str = str_replace(["\r\n", "\n"], '', $odata['content']);
                    // $str = str_replace("/p><p", "/p><br /><p", $odata['content']);
                    $str = str_replace('</p>', '┐', $str);
                    $str = preg_replace('/<p[^>]*>([^┐]*)┐/im', '$1', $str);
                    $str = str_replace('<br>', '<br />', $str);
                    $str = str_replace('<br /><br />', '<br />', $str);
                    $orm->write(__CLASS__, $oid, ['content' => $str], $lang);
                }
            }
        }
    }

    public static function onupdateType($orm, $oids, $values, $lang) {
        $res = $orm->read(__CLASS__, $oids, ['type'], $lang);

        if($res > 0 && count($res)) {
            foreach($res as $oid => $odata) {
                switch($odata['type']) {
                    case 'submit_button':
                        $orm->write(__CLASS__, $oid, ['on_click' => 'submit()'], $lang);
                        break;
                    case 'selector_popup':
                        $orm->write(__CLASS__, $oid, ['on_click' => 'image_full()'], $lang);
                        break;
                    case 'sound':
                    case 'video':
                        $orm->write(__CLASS__, $oid, ['on_click' => 'play()'], $lang);
                        break;
                    case 'selector':
                    case 'selector_wide':
                    case 'selector_yes_no':
                    case 'selector_choice':
                    case 'selector_section':
                    case 'selector_section_wide':
                        $orm->write(__CLASS__, $oid, ['on_click' => 'select_one()'], $lang);
                        break;
                    default:
                }
            }
        }
    }

}