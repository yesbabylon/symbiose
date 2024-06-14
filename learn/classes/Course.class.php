<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace learn;

use equal\log\Logger;
use equal\orm\Model;
use equal\orm\ObjectManager;

class Course extends Model
{

	public static function getColumns(): array
	{
		return [
			'name' => [
				'type'        => 'string',
				'description' => 'Unique slug of the program.'
			],

			'title' => [
				'type'      => 'string',
				'multilang' => true
			],

			'subtitle' => [
				'type'      => 'string',
				'multilang' => true
			],

			'description' => [
				'type'      => 'string',
				'usage'     => 'text/plain',
				'multilang' => true
			],

			'view_link' => [
				'type'        => 'computed',
				'description' => 'URL of the program page in view mode.',
				'function'    => 'calcViewLink',
				'result_type' => 'string',
				'usage'       => 'uri/url',
				'multilang'   => true
			],

			'edit_link' => [
				'type'        => 'computed',
				'description' => 'URL of the program page in edit mode.',
				'function'    => 'calcEditLink',
				'result_type' => 'string',
				'usage'       => 'uri/url',
				'multilang'   => true
			],

			'modules' => [
				'type'  => 'alias',
				'alias' => 'modules_ids'
			],

			'modules_ids' => [
				'type'           => 'one2many',
				'foreign_object' => 'learn\Module',
				'foreign_field'  => 'course_id',
				'order'          => 'order',
				'sort'           => 'asc',
				'ondetach'       => 'delete',
			],

			'quizzes_ids' => [
				'type'           => 'one2many',
				'foreign_object' => 'learn\Quiz',
				'foreign_field'  => 'course_id',
				'ondetach'       => 'delete'
			],

			'bundles_ids' => [
				'type'           => 'one2many',
				'foreign_object' => 'learn\Bundle',
				'foreign_field'  => 'course_id',
				'ondetach'       => 'delete'
			],

			'langs_ids' => [
				'type'            => 'many2many',
				'foreign_object'  => 'learn\Lang',
				'foreign_field'   => 'courses_ids',
				'rel_table'       => 'learn_rel_lang_course',
				'rel_foreign_key' => 'lang_id',
				'rel_local_key'   => 'course_id',
				'description'     => "List of languages in which the program is available"
			]

		];
	}

	/**
	 * @param ObjectManager $om
	 * @param array $oids
	 * @param $lang
	 * @return array
	 */
	public static function calcViewLink(ObjectManager $om, array $oids, $lang): array
	{
		$result = [];

		$courses = $om->read(__CLASS__, $oids, [
			'id',
			'title'
		], $lang);

		foreach ($courses as $oid => $course) {
			$id = self::formatLinkIdNumber($course['id']);
			$title = self::createSlug($course['title']);

			$result[$oid] = '/learning/#/course/' . $id . '/' . $title . '?mode=view' . '&lang=' . $lang;
		}

		return $result;
	}

	/**
	 * @param ObjectManager $om
	 * @param array $oids
	 * @param $lang
	 * @return array
	 */
	public static function calcEditLink(ObjectManager $om, array $oids, $lang): array
	{
		$result = [];

		$courses = $om->read(__CLASS__, $oids, [
			'id',
			'title'
		], $lang);

		foreach ($courses as $oid => $course) {
			$id = self::formatLinkIdNumber($course['id']);
			$title = self::createSlug($course['title']);

			$result[$oid] = '/learning/#/course/' . $id . '/' . $title . '?mode=edit';
		}

		return $result;
	}

	/**
	 * Convert a sentence to a slug
	 *
	 * @param $title
	 * @return string
	 */
	public static function createSlug($title): string
	{
		// Convert the title to lowercase
		$slug = strtolower($title);

		// Replace any non-alphanumeric characters (except for hyphens) with spaces
		$slug = preg_replace('/[^a-z0-9-]+/', ' ', $slug);

		// Replace spaces with hyphens
		$slug = str_replace(' ', '-', $slug);

		// Remove any leading or trailing hyphens
		return trim($slug, '-');
	}

	/**
	 * Format a number to an 8-digit string
	 *
	 * @param $num
	 * @return string
	 */
	public static function formatLinkIdNumber($num): string
	{
		return str_pad($num, 8, '0', STR_PAD_LEFT);
	}
}