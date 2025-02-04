<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item($lang->security_questions, "index.php?module=config-questions");

$plugins->run_hooks("admin_config_questions_begin");

if($mybb->input['action'] == "add")
{
	$plugins->run_hooks("admin_config_questions_add");

	if($mybb->request_method == "post")
	{
		if(!trim($mybb->input['question']))
		{
			$errors[] = $lang->error_missing_question;
		}

		if(!trim($mybb->input['answer']))
		{
			$errors[] = $lang->error_missing_answer;
		}

		if(!$errors)
		{
			if(!isset($mybb->input['preview']))
			{
				$answer = preg_replace("#(\r\n|\r|\n)#s", "\n", trim($mybb->input['answer']));

				$new_question = array(
					"question" => $db->escape_string($mybb->input['question']),
					"answer" => $db->escape_string($answer),
					"active" => $mybb->get_input('active', MyBB::INPUT_INT)
				);
				$qid = $db->insert_query("questions", $new_question);

				$plugins->run_hooks("admin_config_questions_add_commit");

				// Log admin action
				log_admin_action($qid, $mybb->input['question']);

				flash_message($lang->success_question_created, 'success');
				admin_redirect("index.php?module=config-questions");
			}
		}
	}

	$page->add_breadcrumb_item($lang->add_new_question);
	$page->output_header($lang->security_questions." - ".$lang->add_new_question);

	$sub_tabs['security_questions'] = array(
		'title' => $lang->security_questions,
		'link' => "index.php?module=config-questions"
	);

	$sub_tabs['add_new_question'] = array(
		'title' => $lang->add_new_question,
		'link' => "index.php?module=config-questions&amp;action=add",
		'description' => $lang->add_new_question_desc
	);

	$page->output_nav_tabs($sub_tabs, 'add_new_question');
	
	if(isset($mybb->input['preview']) && !$errors)
	{
		$table = new Table();

		require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser;

		$parser_options = array(
			"allow_html" => 0,
			"allow_mycode" => 1,
			"allow_smilies" => 1,
			"allow_imgcode" => 1,
			"allow_videocode" => 1,
			"filter_badwords" => 1,
			"me_username" => 0,
			"shorten_urls" => 0,
			"highlight" => 0,
		);	

		$table->construct_cell($parser->parse_message($mybb->input['question'], $parser_options));
		$table->construct_row();
		$table->output($lang->preview_question);
	}

	$form = new Form("index.php?module=config-questions&amp;action=add", "post", "add");

	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input['active'] = '1';
	}

	$form_container = new FormContainer($lang->add_new_question);
	$form_container->output_row($lang->question." <em>*</em>", $lang->question_desc, $form->generate_text_area('question', $mybb->get_input('question'), array('id' => 'question')), 'question');
	$form_container->output_row($lang->answers." <em>*</em>", $lang->answers_desc, $form->generate_text_area('answer', $mybb->get_input('answer'), array('id' => 'answer')), 'answer');
	$form_container->output_row($lang->active." <em>*</em>", "", $form->generate_yes_no_radio('active', $mybb->input['active']));
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_question);
	$buttons[] = $form->generate_submit_button($lang->preview_question, array('name' => 'preview'));

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "edit")
{
	$query = $db->simple_select("questions", "*", "qid='".$mybb->get_input('qid', MyBB::INPUT_INT)."'");
	$question = $db->fetch_array($query);

	if(!$question['qid'])
	{
		flash_message($lang->error_invalid_question, 'error');
		admin_redirect("index.php?module=config-questions");
	}

	$plugins->run_hooks("admin_config_questions_edit");

	if($mybb->request_method == "post")
	{
		if(!trim($mybb->input['question']))
		{
			$errors[] = $lang->error_missing_question;
		}

		if(!trim($mybb->input['answer']))
		{
			$errors[] = $lang->error_missing_answer;
		}

		if(!$errors)
		{
			if(!isset($mybb->input['preview']))
			{
				$answer = preg_replace("#(\r\n|\r|\n)#s", "\n", trim($mybb->input['answer']));

				$updated_question = array(
					"question" => $db->escape_string($mybb->input['question']),
					"answer" => $db->escape_string($answer),
					"active" => $mybb->get_input('active', MyBB::INPUT_INT)
				);

				$plugins->run_hooks("admin_config_questions_edit_commit");

				$db->update_query("questions", $updated_question, "qid='{$question['qid']}'");

				// Log admin action
				log_admin_action($question['qid'], $mybb->input['question']);

				flash_message($lang->success_question_updated, 'success');
				admin_redirect("index.php?module=config-questions");
			}
		}
	}

	$page->add_breadcrumb_item($lang->edit_question);
	$page->output_header($lang->security_questions." - ".$lang->edit_question);
	
	$sub_tabs['edit_question'] = array(
		'title' => $lang->edit_question,
		'link' => "index.php?module=config-questions&amp;action=edit&amp;qid={$question['qid']}",
		'description' => $lang->edit_question_desc
	);

	$page->output_nav_tabs($sub_tabs, 'edit_question');
	
	$form = new Form("index.php?module=config-questions&amp;action=edit&amp;qid={$question['qid']}", "post", "add");

	$show_preview = false;
	if(isset($mybb->input['preview_list']))
	{
		$show_preview = true;
	}
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		if(!isset($mybb->input['preview']))
		{
			$mybb->input = $question;
		}
	}
	
	if((isset($mybb->input['preview']) || $show_preview === true) && !$errors)
	{
		$table = new Table();

		require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser;

		$parser_options = array(
			"allow_html" => 0,
			"allow_mycode" => 1,
			"allow_smilies" => 1,
			"allow_imgcode" => 1,
			"allow_videocode" => 1,
			"filter_badwords" => 1,
			"me_username" => 0,
			"shorten_urls" => 0,
			"highlight" => 0,
		);	

		$table->construct_cell($parser->parse_message($mybb->input['question'], $parser_options));
		$table->construct_row();
		$table->output($lang->preview_question);
	}
	
	$form_container = new FormContainer($lang->edit_question);
	$form_container->output_row($lang->question." <em>*</em>", $lang->question_desc, $form->generate_text_area('question', $mybb->input['question'], array('id' => 'question')), 'question');
	$form_container->output_row($lang->answers." <em>*</em>", $lang->answers_desc, $form->generate_text_area('answer', $mybb->input['answer'], array('id' => 'answer')), 'answer');
	$form_container->output_row($lang->active." <em>*</em>", "", $form->generate_yes_no_radio('active', $mybb->input['active']));
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_question);
	$buttons[] = $form->generate_submit_button($lang->preview_question, array('name' => 'preview'));

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}


if($mybb->input['action'] == "delete")
{
	if($mybb->get_input('no'))
	{
		admin_redirect("index.php?module=config-questions");
	}

	$query = $db->simple_select("questions", "*", "qid='".$mybb->get_input('qid', MyBB::INPUT_INT)."'");
	$question = $db->fetch_array($query);

	if(!$question['qid'])
	{
		flash_message($lang->error_invalid_question, 'error');
		admin_redirect("index.php?module=config-questions");
	}

	$plugins->run_hooks("admin_config_questions_delete");

	if($mybb->request_method == "post")
	{
		$db->delete_query("questions", "qid='{$question['qid']}'");
		$db->delete_query("questionsessions", "qid='{$question['qid']}'");

		$plugins->run_hooks("admin_config_questions_delete_commit");

		// Log admin action
		log_admin_action($question['qid'], $question['question']);

		flash_message($lang->success_question_deleted, 'success');
		admin_redirect("index.php?module=config-questions");
	}
	else
	{
		$page->output_confirm_action("index.php?module=config-questions&amp;action=delete&amp;qid={$question['qid']}", $lang->confirm_question_deletion);
	}
}

if($mybb->input['action'] == "disable")
{
	$query = $db->simple_select("questions", "*", "qid='".$mybb->get_input('qid', MyBB::INPUT_INT)."'");
	$question = $db->fetch_array($query);

	if(!$question['qid'])
	{
		flash_message($lang->error_invalid_question, 'error');
		admin_redirect("index.php?module=config-questions");
	}

	$plugins->run_hooks("admin_config_questions_disable");

	$update_question = array(
		"active" => 0
	);

	$plugins->run_hooks("admin_config_questions_disable_commit");

	$db->update_query("questions", $update_question, "qid = '{$question['qid']}'");

	// Log admin action
	log_admin_action($question['qid'], $question['question']);

	flash_message($lang->success_question_disabled, 'success');
	admin_redirect("index.php?module=config-questions");
}

if($mybb->input['action'] == "enable")
{
	$query = $db->simple_select("questions", "*", "qid='".$mybb->get_input('qid', MyBB::INPUT_INT)."'");
	$question = $db->fetch_array($query);

	if(!$question['qid'])
	{
		flash_message($lang->error_invalid_question, 'error');
		admin_redirect("index.php?module=config-questions");
	}

	$plugins->run_hooks("admin_config_questions_enable");

	$update_question = array(
		"active" => 1
	);

	$plugins->run_hooks("admin_config_questions_enable_commit");

	$db->update_query("questions", $update_question, "qid = '{$question['qid']}'");

	// Log admin action
	log_admin_action($question['qid'], $question['question']);

	flash_message($lang->success_question_enabled, 'success');
	admin_redirect("index.php?module=config-questions");
}

if(!$mybb->input['action'])
{
	$plugins->run_hooks("admin_config_questions_start");

	$page->output_header($lang->security_questions);

	$sub_tabs['security_questions'] = array(
		'title' => $lang->security_questions,
		'link' => "index.php?module=config-questions",
		'description' => $lang->security_questions_desc
	);
	$sub_tabs['add_new_question'] = array(
		'title' => $lang->add_new_question,
		'link' => "index.php?module=config-questions&amp;action=add",
	);

	$page->output_nav_tabs($sub_tabs, 'security_questions');

	$query = $db->simple_select("questions", "COUNT(qid) AS questions");
	$total_rows = $db->fetch_field($query, "questions");

	$pagenum = $mybb->get_input('page', MyBB::INPUT_INT);
	if($pagenum)
	{
		$start = ($pagenum - 1) * 20;
		$pages = ceil($total_rows / 20);
		if($pagenum > $pages)
		{
			$start = 0;
			$pagenum = 1;
		}
	}
	else
	{
		$start = 0;
		$pagenum = 1;
	}

	$table = new Table;
	$table->construct_header($lang->question);
	$table->construct_header($lang->answers, array("width" => "35%"));
	$table->construct_header($lang->shown, array("width" => "5%", "class" => "align_center"));
	$table->construct_header($lang->correct, array("width" => "5%", "class" => "align_center"));
	$table->construct_header($lang->incorrect, array("width" => "5%", "class" => "align_center"));
	$table->construct_header($lang->controls, array("class" => "align_center", "width" => 150));

	$query = $db->simple_select("questions", "*", "", array('limit_start' => $start, 'limit' => 20, 'order_by' => 'question'));
	while($questions = $db->fetch_array($query))
	{
		$questions['question'] = htmlspecialchars_uni($questions['question']);
		$questions['answer'] = htmlspecialchars_uni($questions['answer']);
		$questions['answer'] = preg_replace("#(\n)#s", "<br />", trim($questions['answer']));
		$questions['shown'] = my_number_format($questions['shown']);
		$questions['correct'] = my_number_format($questions['correct']);
		$questions['incorrect'] = my_number_format($questions['incorrect']);

		if($questions['active'] == 1)
		{
			$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_on.png\" alt=\"({$lang->alt_enabled})\" title=\"{$lang->alt_enabled}\"  style=\"vertical-align: middle;\" /> ";
		}
		else
		{
			$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_off.png\" alt=\"({$lang->alt_disabled})\" title=\"{$lang->alt_disabled}\"  style=\"vertical-align: middle;\" /> ";
		}

		$table->construct_cell("<div>{$icon}{$questions['question']}</div>");
		$table->construct_cell($questions['answer']);
		$table->construct_cell($questions['shown'], array("class" => "align_center"));
		$table->construct_cell($questions['correct'], array("class" => "align_center"));
		$table->construct_cell($questions['incorrect'], array("class" => "align_center"));
		$popup = new PopupMenu("questions_{$questions['qid']}", $lang->options);
		$popup->add_item($lang->edit_question, "index.php?module=config-questions&amp;action=edit&amp;qid={$questions['qid']}");
		$popup->add_item($lang->preview_question, "index.php?module=config-questions&amp;action=edit&amp;qid={$questions['qid']}&amp;preview_list");
		if($questions['active'] == 1)
		{
			$popup->add_item($lang->disable_question, "index.php?module=config-questions&amp;action=disable&amp;qid={$questions['qid']}&amp;my_post_key={$mybb->post_code}");
		}
		else
		{
			$popup->add_item($lang->enable_question, "index.php?module=config-questions&amp;action=enable&amp;qid={$questions['qid']}&amp;my_post_key={$mybb->post_code}");
		}
		$popup->add_item($lang->delete_question, "index.php?module=config-questions&amp;action=delete&amp;qid={$questions['qid']}&amp;my_post_key={$mybb->post_code}", "return AdminCP.deleteConfirmation(this, '{$lang->confirm_question_deletion}')");
		$table->construct_cell($popup->fetch(), array("class" => "align_center"));
		$table->construct_row();
	}

	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_security_questions, array('colspan' => 6));
		$table->construct_row();
	}

	$table->output($lang->security_questions);

	echo "<br />".draw_admin_pagination($pagenum, "20", $total_rows, "index.php?module=config-questions&amp;page={page}");

	$page->output_footer();
}

