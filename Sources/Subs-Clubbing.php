<?php

/**
 * Subs-Clubbing.php
 *
 * @package Clubbing
 * @link https://dragomano.ru/mods/clubbing
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2018 Bugo
 *
 * @version 0.1 beta
 */

if (!defined('SMF'))
	die('Hacking attempt...');

function clubbingActions()
{
	$subActions = array(
		'add'  => array('clubbingAdd'),
		'edit' => array('clubbingEdit')
	);

	if (isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]))
		return $subActions[$_REQUEST['sa']][0]();
}

function clubbingAdd()
{
	global $context, $sourcedir, $smcFunc;

	isAllowedTo('make_clubbings');

	loadTemplate('Clubbing');
	$context['sub_template'] = 'post';

	if (empty($_POST))
		return;

	require_once($sourcedir . '/Subs-Editor.php');

	if (isset($_POST['requisites'])) {
		$_POST['requisites'] = un_htmlspecialchars($_POST['requisites']);

		censorText($_POST['requisites']);

		$smcFunc['db_insert']('',
			'{db_prefix}cb_items',
			array(
				'topic_id'   => 'int',
				'price'      => 'float',
				'currency'   => 'string-3',
				'requisites' => 'string'
			),
			array(
				(int) $_POST['topic'],
				(float) $_POST['price'],
				(string) $_POST['currency'],
				$smcFunc['htmlspecialchars']($_POST['requisites'], ENT_QUOTES)
			),
			array('id')
		);
	}
}

function clubbingEdit()
{
	global $context, $sourcedir, $smcFunc;

	isAllowedTo('make_clubbings');

	loadTemplate('Clubbing');
	$context['sub_template'] = 'post';

	if (empty($_POST))
		return;

	require_once($sourcedir . '/Subs-Editor.php');

	if (isset($_POST['requisites'])) {
		$_POST['requisites'] = un_htmlspecialchars($_POST['requisites']);

		censorText($_POST['requisites']);

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}cb_items
			SET	price = {float:price}, currency = {string:currency}, requisites = {string:requisites}
			WHERE topic_id = {int:topic}',
			array(
				'topic'      => (int) $_POST['topic'],
				'price'      => (float) $_POST['price'],
				'currency'   => (string) $_POST['currency'],
				'requisites' => $smcFunc['htmlspecialchars']($_POST['requisites'], ENT_QUOTES)
			)
		);
	}
}

function clubbingProfile($memID)
{
	global $context, $txt, $scripturl, $user_info, $user_profile, $smcFunc;

	$context['start']          = (int) $_REQUEST['start'];
	$context['current_member'] = $memID;

	loadLanguage('Modlog');

	$context[$context['profile_menu_name']]['tab_data'] = array(
		'title' => $txt['cb_clubbings'],
		'icon'  => 'profile_sm.gif',
		'tabs'  => array()
	);

	$num = 10;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(id)
		FROM {db_prefix}cb_items',
		array()
	);

	list ($total) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	$context['page_index'] = constructPageIndex($scripturl . '?action=profile;u=' . $memID . ';area=clubbings', $context['start'], $total, $num);

	$start   = $context['start'];
	$reverse = $context['start'] > $num / 2;
	if ($reverse) {
		$num   = $total < $context['start'] + $num + 1 && $total > $context['start'] ? $total - $context['start'] : $num;
		$start = $total < $context['start'] + $num + 1 || $total < $context['start'] + $num ? 0 : $total - $context['start'] - $num;
	}
	$counter = $reverse ? $context['start'] + $num + 1 : $context['start'];

	$request = $smcFunc['db_query']('', '
		SELECT c.id, c.price, c.requisites, t.id_topic, m.subject, m.body, m.poster_time
		FROM {db_prefix}cb_items AS c
			INNER JOIN {db_prefix}topics AS t ON (t.id_topic = c.topic_id)
			INNER JOIN {db_prefix}messages AS m ON (m.id_msg = t.id_first_msg)
		WHERE t.id_member_started = {int:current_user}
		ORDER BY m.poster_time ' . ($reverse ? 'ASC' : 'DESC') . '
		LIMIT {int:start}, {int:num}',
		array(
			'current_user' => $context['current_member'],
			'start'        => $start,
			'num'          => $num
		)
	);

	$topics = [];
	while ($row = $smcFunc['db_fetch_assoc']($request))	{
		censorText($row['subject']);

		$topics[] = $row['id_topic'];
		$context['items'][$counter += $reverse ? -1 : 1] = array(
			'id'         => $row['id'],
			'counter'    => $counter,
			'alternate'  => $counter % 2,
			'topic'      => $row['id_topic'],
			'title'      => $row['subject'],
			'date'       => timeformat($row['poster_time']),
			'text'       => parse_bbc($row['body']),
			'requisites' => parse_bbc($row['requisites'])
		);
	}

	$smcFunc['db_free_result']($request);

	$request = $smcFunc['db_query']('', '
		SELECT topic_id, IFNULL(member_name, 0) AS member_name
		FROM {db_prefix}cb_members
			INNER JOIN {db_prefix}members ON (id_member = member_id)
		WHERE topic_id IN ({array_int:topics})',
		array(
			'topics' => $topics
		)
	);

	$context['clubbing_members'] = [];
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['clubbing_members'][$row['topic_id']][] = $row['member_name'];

	$smcFunc['db_free_result']($request);

	if ($reverse)
		$context['items'] = array_reverse($context['items'], true);

	$context['cb_can_delete'] = $context['current_member'] == $user_info['id'];

	$redirect = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '?action=profile;area=clubbings;u=' . $memID;

	if ($context['cb_can_delete']) {
		if (isset($_POST['new_member'])) {
			$members = explode(',', trim($_POST['new_member'], ' '));

			foreach ($members as $member) {
				$smcFunc['db_insert']('replace',
					'{db_prefix}cb_members',
					array(
						'member_id' => 'int',
						'topic_id'  => 'int'
					),
					array(
						(int) $member,
						(int) $_POST['topic']
					),
					array()
				);
			}
		}

		if (isset($_REQUEST['del_item'])) {
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}cb_items
				WHERE id = {int:item}',
				array(
					'item' => (int) $_REQUEST['del_item']
				)
			);


			if (isset($_REQUEST['profile'], $_REQUEST['start'], $_REQUEST['u']))
				redirectexit('action=profile;area=clubbings;start=' . $_REQUEST['start'] . ';u=' . $_REQUEST['u']);
			else
				redirectexit($redirect);
		}
	}

	loadTemplate('Clubbing');
	$context['sub_template'] = 'profile';
	$context['page_title']   = $txt['cb_clubbings'] . ' - ' . $user_profile[$memID]['real_name'];
}
