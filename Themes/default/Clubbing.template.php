<?php

/**
 * Clubbing.template.php
 *
 * @package Clubbing
 * @link https://dragomano.ru/mods/clubbing
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2018 Bugo
 *
 * @version 0.1 alpha
 */

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

			if ($context['cb_can_delete'])
				echo '
					<div class="list_posts">', $item['requisites'], '</div>
					<div class="list_posts">', implode(', ', $context['members'][$item['topic']]), '</div>';

			echo '
				</div>';

			if ($context['cb_can_delete'])
				echo '
				<div class="modal" data-iziModal-title="' . $txt['cb_clubbing_adding_members'] . '" data-iziModal-icon="icon-home">
					<div class="modal-content">
						<form name="clubbing_form_', $item['topic'], '" method="post" action="javascript:void(null);">
							<input type="hidden" name="topic" value="', $item['topic'], '" />
							<div>
								<input type="text" name="members" placeholder="' . $txt['cb_enter_members'] . '" required />
							</div>
							<div class="centertext">
								<button type="button" class="button_submit" data-izimodal-close data-izimodal-transitionout="bounceOutDown">' . $txt['find_close'] . '</button>
								<button type="submit" name="submit" class="button_submit">' . $txt['post'] . '</button>
							</div>
						</form>
					</div>
				</div>';

			if ($context['cb_can_delete'])
				echo '
				<div class="floatright">
					<ul class="reset smalltext quickbuttons">
						<li class="approve_button">
							<a href="#"><span>', $txt['cb_add_member'], '</span></a>
						</li>
						<li class="remove_button">
							<a href="', $scripturl, '?action=profile;area=clubbings;u=', $context['member']['id'], ';start=', $context['start'], ';', $context['session_var'], '=', $context['session_id'], ';del_item=', $item['id'], '"><span>', $txt['remove'], '</span></a>
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
		<p class="information">', $txt['cb_no_items'], '</p>
		<br class="clear" />';
	}

	if ($context['cb_can_delete'])
		$context['insert_after_template'] .= '
	<script type="text/javascript">
		jQuery(document).ready(function($){
			$(".approve_button").attr("data-izimodal-open", ".modal").attr("data-izimodal-transitionin", "fadeInDown");
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
