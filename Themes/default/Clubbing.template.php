<?php

/**
 * Clubbing.template.php
 *
 * @package Clubbing
 * @link https://dragomano.ru/mods/clubbing
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2018 Bugo
 *
 * @version 0.1
 */

function template_post()
{
}

function template_display_above()
{
}

function template_display_below()
{
	global $context, $txt, $user_info, $scripturl;

	$currency = empty($context['clubbing']['currency']) ? 'RUB' : $context['clubbing']['currency'];

	echo '
	<div id="clubbing_modal" data-iziModal-title="' . (empty( $context['clubbing']['id']) ? $txt['cb_clubbing_creating'] : $txt['cb_clubbing_editing']) . '" data-iziModal-icon="icon-home">
		<div class="modal-content">
			<form name="clubbing_form" method="post" action="javascript:void(null);">
				<input type="hidden" name="topic" value="' . $context['current_topic'] . '" />
				<input type="hidden" name="user" value="' . $user_info['id'] . '" />
				<div>
					<input type="number" name="price" min="0" step="0.01" ' . (!empty($context['clubbing']['price']) ? 'value="' . $context['clubbing']['price'] . '" ' : '') . 'placeholder="' . $txt['cb_enter_price'] . '" required />
					<select name="currency">
						<option' . ($currency == 'RUB' ? ' selected="selected"' : '') . '>RUB</option>
						<option' . ($currency == 'UAH' ? ' selected="selected"' : '') . '>UAH</option>
						<option' . ($currency == 'USD' ? ' selected="selected"' : '') . '>USD</option>
						<option' . ($currency == 'EUR' ? ' selected="selected"' : '') . '>EUR</option>
					</select>
				</div>
				<div>
					<textarea name="requisites" placeholder="' . $txt['cb_enter_requisites'] . '" required>' . (!empty($context['clubbing']['requisites']) ? $context['clubbing']['requisites'] : '') . '</textarea>
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
			$(".button_strip_' . (empty($context['clubbing']['id']) ? 'add' : 'edit') . '_clubbing").attr("data-izimodal-open", "#clubbing_modal").attr("data-izimodal-transitionin", "fadeInDown");
			$("#clubbing_modal").iziModal();
			$("form[name=clubbing_form]").on("submit", function(){
				msg = $(this).serialize();
				$.ajax({
					type: "POST",
					url: "' . $scripturl . '?action=clubbings;sa=' . (empty($context['clubbing']['id']) ? 'add' : 'edit') . '",
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

function template_profile()
{
	global $context, $scripturl, $txt;

	echo '
		<div class="cat_bar">
			<h3 class="catbg">', $txt['cb_clubbings'], ' - ', $context['member']['name'], '</h3>
		</div>';

	if (!empty($context['items'])) {
		echo '
		<div class="pagesection">
			<span>', $txt['pages'], ': ', $context['page_index'], '</span>
		</div>';

		foreach ($context['items'] as $item) {
			echo '
		<div class="topic">
			<div class="', $item['alternate'] == 0 ? 'windowbg2' : 'windowbg', ' core_posts">
				<span class="topslice"><span></span></span>
				<div class="content">
					<div class="counter">', $item['counter'], '</div>
					<div class="topic_details">
						<h5><strong><a href="', $scripturl, '?topic=', $item['topic'], '.0">', $item['title'], '</a></strong></h5>
						<span class="smalltext">', $item['date'], '</span>
					</div>
					<div class="list_posts">', $item['text'], '</div>';

			if ($context['cb_can_manage']) {
				echo '
					<div class="list_posts">', $item['requisites'], '</div>';

				if (!empty($context['clubbing_members'][$item['topic']]))
					echo '
					<div class="list_posts">', implode(', ', $context['clubbing_members'][$item['topic']]), '</div>';
			}

			echo '
				</div>';

			if ($context['cb_can_manage'])
				echo '
				<div class="modal" id="modal_', $item['topic'], '" data-iziModal-title="' . $txt['cb_clubbing_adding_members'] . '" data-iziModal-icon="icon-home">
					<div class="modal-content">
						<form name="clubbing_form_', $item['topic'], '" method="post" action="javascript:void(null);">
							<input type="hidden" name="topic" value="', $item['topic'], '" />
							<input type="text" name="members" placeholder="' . $txt['cb_enter_members'] . '" required />
							<div class="centertext">
								<button type="button" class="button_submit" data-izimodal-close data-izimodal-transitionout="bounceOutDown">' . $txt['find_close'] . '</button>
								<button type="submit" name="submit" class="button_submit">' . $txt['post'] . '</button>
							</div>
						</form>
					</div>
				</div>';

			if ($context['cb_can_manage'])
				echo '
				<div class="floatright">
					<ul class="reset smalltext quickbuttons">
						<li class="approve_button" data-izimodal-open="#modal_', $item['topic'], '">
							<a href="#"><span>', $txt['cb_add_member'], '</span></a>
						</li>
						<li class="remove_button">
							<a href="', $scripturl, '?action=profile;area=clubbings;u=', $context['member']['id'], ';start=', $context['start'], ';', $context['session_var'], '=', $context['session_id'], ';del_item=', $item['topic'], '"><span>', $txt['remove'], '</span></a>
						</li>
					</ul>
				</div>';

			echo '
				<br class="clear" />
				<span class="botslice"><span></span></span>
			</div>
		</div>';
		}

		echo '
		<div class="pagesection" style="margin-bottom: 0;">
			<span>', $txt['pages'], ': ', $context['page_index'], '</span>
		</div>';
	} else {
		echo '
		<div class="information">', $txt['cb_no_items'], '</div>';
	}

	if ($context['cb_can_manage']) {
		$context['insert_after_template'] .= '
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$(".approve_button").attr("data-izimodal-transitionin", "fadeInDown");
				$(".modal").iziModal();
				$("form[name^=clubbing_form]").on("submit", function(){
					msg = $(this).serialize();
					$.ajax({
						type: "POST",
						url: "' . $scripturl . '?action=profile;area=clubbings;u=' . $context['member']['id'] . '",
						data: msg,
						success: function(){
							$("#modal").iziModal("close", {
								transition: "bounceOutDown"
							});
							window.location = smf_prepareScriptUrl(smf_scripturl) + \'action=profile;area=clubbings;u=' . $context['member']['id'] . '\';
						},
						error: function(){
							alert("' . JavaScriptEscape($txt['cb_is_error']) . '" + xhr.responseCode);
						}
					});
				});
			});
		</script>';
	}
}
