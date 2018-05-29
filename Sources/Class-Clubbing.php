<?php

/**
 * Class-Clubbing.php
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

class Clubbing
{
	/**
	 * Подключаем используемые хуки
	 *
	 * @return void
	 */
	public static function hooks()
	{
		add_integration_function('integrate_load_theme', 'Clubbing::loadTheme', false);
		add_integration_function('integrate_actions', 'Clubbing::actions', false);
		add_integration_function('integrate_load_permissions', 'Clubbing::loadPermissions', false);
		add_integration_function('integrate_display_buttons', 'Clubbing::displayButtons', false);
		add_integration_function('integrate_prepare_display_context', 'Clubbing::prepareDisplayContext', false);
		add_integration_function('integrate_profile_areas', 'Clubbing::profileAreas', false);
	}

	/**
	 * Подключаем языковой файл, а также используемые стили и скрипты
	 *
	 * @return void
	 */
	public static function loadTheme()
	{
		global $context, $settings;

		loadLanguage('Clubbing/');

		$context['make_clubbings'] = allowedTo('make_clubbings');

		if (empty($context['make_clubbings']))
			return;

		$context['html_headers'] .= '
	<link rel="stylesheet" type="text/css" href="' . $settings['default_theme_url'] . '/css/clubbing/iziModal.min.css" />
	<link rel="stylesheet" type="text/css" href="' . $settings['default_theme_url'] . '/css/clubbing/clubbing.css" />';

		$context['insert_after_template'] .= '
		<script type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/clubbing/jquery.min.js"></script>
		<script type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/clubbing/iziModal.min.js"></script>';
	}

	/**
	 * Подключаем область clubbings для работы со складчинами
	 *
	 * @param array $action_array
	 * @return void
	 */
	public static function actions(&$action_array)
	{
		$action_array['clubbings'] = array('Class-Clubbing.php', array('Clubbing', 'subactions'));
	}

	/**
	 * Объявляем массив возможных действий (создание, редактирование)
	 *
	 * @return void
	 */
	public static function subactions()
	{
		$subActions = array(
			'add'  => array('Clubbing', 'addClubbing'),
			'edit' => array('Clubbing', 'editClubbing')
		);

		if (isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]))
			return call_user_func($subActions[$_REQUEST['sa']]);
	}

	/**
	 * Добавление складчины
	 *
	 * @return void
	 */
	private static function addClubbing()
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

	/**
	 * Редактирование складчины
	 *
	 * @return void
	 */
	private static function editClubbing()
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

	/**
	 * Объявляем права доступа для создания/управления складчинами
	 *
	 * @param array $permissionGroups
	 * @param array $permissionList
	 * @return void
	 */
	public static function loadPermissions(&$permissionGroups, &$permissionList)
	{
		global $context;

		$permissionGroups['membergroup']['simple']  = array('clubbings');
		$permissionGroups['membergroup']['classic'] = array('clubbings');

		$permissionList['membergroup']['make_clubbings'] = array(false, 'clubbings', 'clubbings');

		$context['non_guest_permissions'] = array_merge($context['non_guest_permissions'], array('make_clubbings'));
	}

	/**
	 * Добавляем кнопки для создания/редактирования складчин на странице темы
	 *
	 * @param array $normal_buttons
	 * @return void
	 */
	public static function displayButtons(&$normal_buttons)
	{
		global $context, $user_info, $sourcedir, $smcFunc;

		// Если текущий пользователь не автор темы или не может создавать складчины, дальше идти смысла нет
		if ($context['topic_starter_id'] != $user_info['id'] || !allowedTo('make_clubbings'))
			return;

		require_once($sourcedir . '/Subs-Post.php');

		$request = $smcFunc['db_query']('', '
			SELECT id, price, currency, requisites
			FROM {db_prefix}cb_items
			WHERE topic_id = {int:current_topic}
			LIMIT 1',
			array(
				'current_topic' => $context['current_topic']
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))	{
			censorText($row['requisites']);

			$context['clubbing'] = array(
				'id'         => $row['id'],
				'price'      => $row['price'],
				'currency'   => $row['currency'],
				'requisites' => un_preparsecode($row['requisites'])
			);
		}

		$smcFunc['db_free_result']($request);

		$counter = isset($normal_buttons['reply']) ? 2 : 1;

		$normal_buttons = array_merge(
			array_slice($normal_buttons, 0, $counter, true),
			empty($context['clubbing']['id']) ? array(
				'add_clubbing' => array(
					'test'  => 'make_clubbings',
					'text'  => 'cb_add_clubbing',
					'image' => 'im_reply.gif',
					'lang'  => true,
					'url'   => '#',
				),
			) : array(
				'edit_clubbing' => array(
					'test'  => 'make_clubbings',
					'text'  => 'cb_edit_clubbing',
					'image' => 'im_reply.gif',
					'lang'  => true,
					'url'   => '#',
				),
			),
			array_slice($normal_buttons, $counter, null, true)
		);

		loadTemplate('Clubbing');
		$context['template_layers'][] = 'display';
	}

	/**
	 * Выводим список текущих участников складчины, либо количество участников
	 *
	 * @param array $output
	 * @return void
	 */
	public static function prepareDisplayContext(&$output)
	{
		global $context, $smcFunc, $sourcedir, $txt, $boardurl, $user_info, $scripturl;

		if ($output['id'] == $context['topic_first_message']) {
			// Список текущих участников
			$request = $smcFunc['db_query']('', '
				SELECT member_id AS id, IFNULL(member_name, 0) AS member_name
				FROM {db_prefix}cb_members
					INNER JOIN {db_prefix}members ON (id_member = member_id)
				WHERE topic_id = {int:current_topic}',
				array(
					'current_topic' => $context['current_topic']
				)
			);

			$members = [];
			while ($row = $smcFunc['db_fetch_assoc']($request))
				$members[$row['id']] = $row['member_name'];

			$smcFunc['db_free_result']($request);

			if (!empty($members)) {
				if ($output['is_message_author']) {
					$output['body'] .= '
				<div class="clubbing_list information smalltext">
					<div class="centertext">
						<h5>' . $txt['cb_clubbing_list'] . '</h5>
						<hr />
					</div>
					<ul class="columns">';

					foreach ($members as $id => $member)
						$output['body'] .= '
						<li><a href="' . $scripturl . '?action=profile;u=' . $id . '">' . $member . '</a></li>';

					$output['body'] .= '
					</ul>
				</div>';
				} else {
					$output['body'] .= '
				<hr />
				<div class="smalltext">' . $txt['cb_clubbing_list1'] . count($members) . '</div>';
				}
			}

			// Вывод кнопки "Присоединиться"
			if (!$output['is_message_author']) {
				$request = $smcFunc['db_query']('', '
					SELECT requisites
					FROM {db_prefix}cb_items
					WHERE topic_id = {int:current_topic}
					LIMIT 1',
					array(
						'current_topic' => $context['current_topic']
					)
				);

				list ($requisites) = $smcFunc['db_fetch_row']($request);
				$smcFunc['db_free_result']($request);

				if (!empty($requisites)) {
					require_once($sourcedir . '/Subs-Post.php');

					if (isset($members[$user_info['id']])) {
						$output['body'] .= '
				<input class="button_submit" type="button" value="' . $txt['cb_join_ready'] . '" disabled />';
					} else {
						$output['body'] .= '
				<input class="button_submit" name="cb_button_join" type="button" value="' . $txt['cb_join_to_clubbing'] . '" />
				<div id="modal" data-iziModal-title="' . $txt['cb_clubbing_info'] . '" data-iziModal-icon="icon-home">
					<div class="modal-content">
						<div class="centertext">
							<div class="information">' . strtr($txt['cb_clubbing_text'], array('{DATA}' => un_preparsecode($requisites), '{LINK}' => $boardurl, '{USER}' => $user_info['id'])) . '</div>
							<button type="button" class="button_submit" data-izimodal-close data-izimodal-transitionout="bounceOutDown">' . $txt['cb_clubbing_ok'] . '</button>
						</div>
					</div>
				</div>';

						$context['insert_after_template'] .= '
				<script type="text/javascript">
					jQuery(document).ready(function($){
						$("input[name=cb_button_join]").attr("data-izimodal-open", "#modal").attr("data-izimodal-transitionin", "fadeInDown");
						$("#modal").iziModal();
					});
				</script>';
					}
				}
			}
		}
	}

	/**
	 * Создаем раздел "Складчины" в профиле пользователя
	 *
	 * @param array $profile_areas
	 * @return void
	 */
	public static function profileAreas(&$profile_areas)
	{
		global $txt;

		$profile_areas['info']['areas']['clubbings'] = array(
			'label'       => $txt['cb_clubbings'],
			'function'    => 'clubbingProfile',
			'subsections' => array(),
			'permission'  => array(
				'own' => 'profile_view_own',
				'any' => 'profile_view_any',
			),
		);
	}
}

/**
 * Вывод списка складчин в профиле пользователя, с возможностью добавления участников
 *
 * @param int $memID
 * @return void
 */
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
		// Добавляем участников
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

		// Удаляем складчину
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
