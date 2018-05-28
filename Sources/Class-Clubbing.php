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
	public static function hooks()
	{
		add_integration_function('integrate_load_theme', 'Clubbing::load_theme', false);
		add_integration_function('integrate_actions', 'Clubbing::actions', false);
		add_integration_function('integrate_load_permissions', 'Clubbing::load_permissions', false);
		add_integration_function('integrate_display_buttons', 'Clubbing::display_buttons', false);
		add_integration_function('integrate_prepare_display_context', 'Clubbing::prepare_display_context', false);
		add_integration_function('integrate_profile_areas', 'Clubbing::profile_areas', false);
	}

	public static function load_theme()
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

	public static function actions(&$action_array)
	{
		$action_array['clubbings'] = array('Subs-Clubbing.php', 'clubbingActions');
	}

	public static function load_permissions(&$permissionGroups, &$permissionList)
	{
		global $context;

		$permissionGroups['membergroup']['simple']  = array('clubbings');
		$permissionGroups['membergroup']['classic'] = array('clubbings');

		$permissionList['membergroup']['make_clubbings'] = array(false, 'clubbings', 'clubbings');

		$context['non_guest_permissions'] = array_merge($context['non_guest_permissions'], array('make_clubbings'));
	}

	public static function display_buttons(&$normal_buttons)
	{
		global $context, $user_info, $sourcedir, $smcFunc, $scripturl, $txt;

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

			$clubbing   = $row['id'];
			$price      = $row['price'];
			$currency   = $row['currency'];
			$requisites = un_preparsecode($row['requisites']);
		}

		$smcFunc['db_free_result']($request);

		$counter = isset($normal_buttons['reply']) ? 2 : 1;

		$normal_buttons = array_merge(
			array_slice($normal_buttons, 0, $counter, true),
			empty($clubbing) ? array(
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

		$currency = empty($clubbing) ? 'RUB' : $currency;

		$context['insert_after_template'] .= '
		<div id="modal" data-iziModal-title="' . (empty($clubbing) ? $txt['cb_clubbing_creating'] : $txt['cb_clubbing_editing']) . '" data-iziModal-icon="icon-home">
			<div class="modal-content">
				<form name="clubbing_form" method="post" action="javascript:void(null);">
					<input type="hidden" name="topic" value="' . $context['current_topic'] . '" />
					<input type="hidden" name="user" value="' . $user_info['id'] . '" />
					<div>
						<input type="number" name="price" min="0" step="0.01" ' . (!empty($price) ? 'value="' . $price . '" ' : '') . 'placeholder="' . $txt['cb_enter_price'] . '" required />
						<select name="currency">
							<option' . ($currency == 'RUB' ? ' selected="selected"' : '') . '>RUB</option>
							<option' . ($currency == 'UAH' ? ' selected="selected"' : '') . '>UAH</option>
							<option' . ($currency == 'USD' ? ' selected="selected"' : '') . '>USD</option>
							<option' . ($currency == 'EUR' ? ' selected="selected"' : '') . '>EUR</option>
						</select>
					</div>
					<div>
						<textarea name="requisites" placeholder="' . $txt['cb_enter_requisites'] . '" required>' . (!empty($requisites) ? $requisites : '') . '</textarea>
					</div>
					<div class="centertext">
						<button type="button" class="button_submit" data-izimodal-close data-izimodal-transitionout="bounceOutDown">' . $txt['find_close'] . '</button>
						<button type="submit" name="submit" class="button_submit">' . $txt['post'] . '</button>
					</div>
				</form>
			</div>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$(".button_strip_' . (empty($clubbing) ? 'add' : 'edit') . '_clubbing").attr("data-izimodal-open", "#modal").attr("data-izimodal-transitionin", "fadeInDown");
				$("#modal").iziModal();
				$("form[name=clubbing_form]").on("submit", function(){
					msg = $(this).serialize();
					$.ajax({
						type: "POST",
						url: "' . $scripturl . '?action=clubbings;sa=' . (empty($clubbing) ? 'add' : 'edit') . '",
						data: msg,
						success: function(){
							$("#modal").iziModal("close", {
								transition: "bounceOutDown"
							});
							window.location = smf_prepareScriptUrl(smf_scripturl) + \'topic=' . $context['current_topic'] . '.0\';
						},
						error: function(){
							alert("' . JavaScriptEscape($txt['cb_is_error']) . '" + xhr.responseCode);
						}
					});
				});
			});
		</script>';
	}

	public static function prepare_display_context(&$output, &$message)
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

	public static function profile_areas(&$profile_areas)
	{
		global $txt;

		$profile_areas['info']['areas']['clubbings'] = array(
			'label'       => $txt['cb_clubbings'],
			'file'        => 'Subs-Clubbing.php',
			'function'    => 'clubbingProfile',
			'subsections' => array(),
			'permission'  => array(
				'own' => 'profile_view_own',
				'any' => 'profile_view_any',
			),
		);
	}
}
