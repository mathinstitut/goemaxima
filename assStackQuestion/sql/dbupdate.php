<#1>
<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 *
 *
 * Database creation script.
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 *
 * $Id$
 */
/*
 * Create the new question type
 */
global $DIC;
$db = $DIC->database();

$res = $db->queryF("SELECT * FROM qpl_qst_type WHERE type_tag = %s", array('text'), array('assStackQuestion'));

if ($res->numRows() == 0)
{
	$res = $db->query("SELECT MAX(question_type_id) maxid FROM qpl_qst_type");
	$data = $db->fetchAssoc($res);
	$max = $data["maxid"] + 1;

	$affectedRows = $db->manipulateF("INSERT INTO qpl_qst_type (question_type_id, type_tag, plugin) VALUES (%s, %s, %s)", array("integer", "text", "integer"), array($max, 'assStackQuestion', 1));
}
?>
<#2>
<?php
/*
 * STACK name: options "Stores the main options for each Stack question"
 */
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('xqcas_options'))
{
	$fields = array('id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'question_id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'question_variables' => array('type' => 'clob', 'notnull' => true), 'specific_feedback' => array('type' => 'clob', 'notnull' => true), 'specific_feedback_format' => array('type' => 'integer', 'length' => 2, 'notnull' => true, 'default' => 0), 'question_note' => array('type' => 'text', 'length' => 255, 'notnull' => true), 'question_simplify' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 1), 'assume_positive' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0), 'prt_correct' => array('type' => 'clob', 'notnull' => true), 'prt_correct_format' => array('type' => 'integer', 'length' => 2, 'notnull' => true, 'default' => 0), 'prt_partially_correct' => array('type' => 'clob', 'notnull' => true), 'prt_partially_correct_format' => array('type' => 'integer', 'length' => 2, 'notnull' => true, 'default' => 0), 'prt_incorrect' => array('type' => 'clob', 'notnull' => true), 'prt_incorrect_format' => array('type' => 'integer', 'length' => 2, 'notnull' => true, 'default' => 0), 'multiplication_sign' => array('type' => 'text', 'length' => 8, 'notnull' => true, 'default' => 'dot'), 'sqrt_sign' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 1), 'complex_no' => array('type' => 'text', 'length' => 8, 'notnull' => true, 'default' => 'i'), 'inverse_trig' => array('type' => 'text', 'length' => 8, 'notnull' => true, 'default' => 'cos-1'), 'variants_selection_seed' => array('type' => 'text', 'length' => 255, 'notnull' => false, 'default' => NULL));
	$db->createTable("xqcas_options", $fields);
	$db->createSequence("xqcas_options");
	$db->addPrimaryKey("xqcas_options", array("id"));

	/*
	 * 2 indexes to be created
	 */
}
?>
<#3>
<?php
/*
 * STACK name: inputs "One row for each input in the question."
 */
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('xqcas_inputs'))
{
	$fields = array('id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'question_id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'name' => array('type' => 'text', 'length' => 32, 'notnull' => true), 'type' => array('type' => 'text', 'length' => 32, 'notnull' => true), 'tans' => array('type' => 'text', 'length' => 255, 'notnull' => true), 'box_size' => array('type' => 'integer', 'length' => 8, 'notnull' => true, 'default' => 15), 'strict_syntax' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 1), 'insert_stars' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0), 'syntax_hint' => array('type' => 'text', 'length' => 255, 'notnull' => true), 'forbid_words' => array('type' => 'text', 'length' => 255, 'notnull' => true), 'forbid_float' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 1), 'require_lowest_terms' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0), 'check_answer_type' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0), 'must_verify' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 1), 'show_validation' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 1), 'options' => array('type' => 'clob', 'notnull' => true));
	$db->createTable("xqcas_inputs", $fields);
	$db->createSequence("xqcas_inputs");
	$db->addPrimaryKey("xqcas_inputs", array("id"));

	/*
	 * 3 indexes to be created
	 */
}
?>
<#4>
<?php
/*
 * STACK name: prts "One row for each PRT in the question."
 */
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('xqcas_prts'))
{
	$fields = array('id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'question_id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'name' => array('type' => 'text', 'length' => 32, 'notnull' => true), 'value' => array('type' => 'text', 'length' => 21, 'notnull' => true), 'auto_simplify' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 1), 'feedback_variables' => array('type' => 'clob', 'notnull' => true), 'first_node_name' => array('type' => 'text', 'length' => 8, 'notnull' => true));
	$db->createTable("xqcas_prts", $fields);
	$db->createSequence("xqcas_prts");
	$db->addPrimaryKey("xqcas_prts", array("id"));

	/*
	 * 3 indexes to be created
	 */
}
?>
<#5>
<?php
/*
 * STACK name: prt_nodes "One row for each node in each PRT in the question."
 */
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('xqcas_prt_nodes'))
{
	$fields = array('id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'question_id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'prt_name' => array('type' => 'text', 'length' => 32, 'notnull' => true), 'node_name' => array('type' => 'text', 'length' => 8, 'notnull' => true), 'answer_test' => array('type' => 'text', 'length' => 32, 'notnull' => true), 'sans' => array('type' => 'text', 'length' => 255, 'notnull' => true), 'tans' => array('type' => 'text', 'length' => 255, 'notnull' => true), 'test_options' => array('type' => 'text', 'length' => 255, 'notnull' => true), 'quiet' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0), 'true_score_mode' => array('type' => 'text', 'length' => 4, 'notnull' => true, 'default' => '='), 'true_score' => array('type' => 'text', 'length' => 21, 'notnull' => true), 'true_penalty' => array('type' => 'text', 'length' => 21, 'notnull' => false, 'default' => NULL), 'true_next_node' => array('type' => 'text', 'length' => 8, 'notnull' => false, 'default' => NULL), 'true_answer_note' => array('type' => 'text', 'length' => 255, 'notnull' => true), 'true_feedback' => array('type' => 'clob', 'notnull' => true), 'true_feedback_format' => array('type' => 'integer', 'length' => 2, 'notnull' => true, 'default' => 0), 'false_score_mode' => array('type' => 'text', 'length' => 4, 'notnull' => true, 'default' => '='), 'false_score' => array('type' => 'text', 'length' => 21, 'notnull' => true), 'false_penalty' => array('type' => 'text', 'length' => 21, 'notnull' => false, 'default' => NULL), 'false_next_node' => array('type' => 'text', 'length' => 8, 'notnull' => false, 'default' => NULL), 'false_answer_note' => array('type' => 'text', 'length' => 255, 'notnull' => true), 'false_feedback' => array('type' => 'clob', 'notnull' => true), 'false_feedback_format' => array('type' => 'integer', 'length' => 2, 'notnull' => true, 'default' => 0));
	$db->createTable("xqcas_prt_nodes", $fields);
	$db->createSequence("xqcas_prt_nodes");
	$db->addPrimaryKey("xqcas_prt_nodes", array("id"));

	/*
	 * 3 indexes to be created
	 */
}
?>
<#6>
<?php
/*
 * STACK name: cas_cache "Caches the resuts of calls to Maxima."
 */
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('xqcas_cas_cache'))
{
	$fields = array('id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'hash' => array('type' => 'text', 'length' => 40, 'notnull' => true), 'command' => array('type' => 'clob', 'notnull' => true), 'result' => array('type' => 'clob', 'notnull' => true));
	$db->createTable("xqcas_cas_cache", $fields);
	$db->createSequence("xqcas_cas_cache");
	$db->addPrimaryKey("xqcas_cas_cache", array("id"));

	/*
	 * 2 indexes to be created
	 */
}
?>
<#7>
<?php
/*
 * STACK name: qtests "One row for each questiontest for each question."
 */
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('xqcas_qtests'))
{
	$fields = array('id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'question_id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'test_case' => array('type' => 'integer', 'length' => 8, 'notnull' => true));
	$db->createTable("xqcas_qtests", $fields);
	$db->createSequence("xqcas_qtests");
	$db->addPrimaryKey("xqcas_qtests", array("id"));

	/*
	 * 3 indexes to be created
	 */
}
?>
<#8>
<?php
/*
 * STACK name: qtest_inputs "The value for each input for the question tests."
 */
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('xqcas_qtest_inputs'))
{
	$fields = array('id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'question_id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'test_case' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'input_name' => array('type' => 'text', 'length' => 32, 'notnull' => true), 'value' => array('type' => 'text', 'length' => 255, 'notnull' => true));
	$db->createTable("xqcas_qtest_inputs", $fields);
	$db->createSequence("xqcas_qtest_inputs");
	$db->addPrimaryKey("xqcas_qtest_inputs", array("id"));

	/*
	 * 3 indexes to be created
	 */
}
?>
<#9>
<?php
/*
 * STACK name: qtest_expected "Holds the expected outcomes for each PRT for this question t"
 */
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('xqcas_qtest_expected'))
{
	$fields = array('id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'question_id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'test_case' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'prt_name' => array('type' => 'text', 'length' => 32, 'notnull' => true), 'expected_score' => array('type' => 'text', 'length' => 21, 'notnull' => false, 'default' => NULL), 'expected_penalty' => array('type' => 'text', 'length' => 21, 'notnull' => false, 'default' => NULL), 'expected_answer_note' => array('type' => 'text', 'length' => 255, 'notnull' => true));
	$db->createTable("xqcas_qtest_expected", $fields);
	$db->createSequence("xqcas_qtest_expected");
	$db->addPrimaryKey("xqcas_qtest_expected", array("id"));

	/*
	 * 3 indexes to be created
	 */
}
?>
<#10>
<?php
/*
 * STACK name: deployed_seeds "Holds the seeds for the variants of each question that have "
 */
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('xqcas_deployed_seeds'))
{
	$fields = array('id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'question_id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'seed' => array('type' => 'integer', 'length' => 8, 'notnull' => true));
	$db->createTable("xqcas_deployed_seeds", $fields);
	$db->createSequence("xqcas_deployed_seeds");
	$db->addPrimaryKey("xqcas_deployed_seeds", array("id"));

	/*
	 * 3 indexes to be created
	 */
}
?>
<#11>
<#12>
<?php
global $DIC;
$db = $DIC->database();
$allow_words_column = array('type' => 'text', 'length' => 255, 'notnull' => true);
if (!$db->tableColumnExists("xqcas_inputs", "allow_words"))
{
	$db->addTableColumn("xqcas_inputs", "allow_words", $allow_words_column);
}
?>
<#13>
<#14>
<?php
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('xqcas_ilias_specific'))
{
	$fields = array('id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'question_id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'general_feedback' => array('type' => 'clob'));
	$db->createTable("xqcas_ilias_specific", $fields);
	$db->createSequence("xqcas_ilias_specific");
	$db->addPrimaryKey("xqcas_ilias_specific", array("id"));
}
?>
<#15>
<#16>
<#17>
<?php
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('xqcas_configuration'))
{
	$fields = array('parameter_name' => array('type' => 'text', 'length' => 255, 'notnull' => true), 'value' => array('type' => 'clob'), 'group_name' => array('type' => 'text', 'length' => 255));
	$db->createTable("xqcas_configuration", $fields);
	$db->addPrimaryKey("xqcas_configuration", array("parameter_name"));
}
?>
<#18>
<?php
global $DIC;
$db = $DIC->database();
//Check if connection entries in DB have been created, otherwise create it.
$query = 'SELECT * FROM xqcas_configuration WHERE group_name = "connection"';
$result = $db->query($query);
if (!$db->fetchAssoc($result))
{
	//Default values for connection
	$connection_default_values = array('platform_type' => 'unix', 'maxima_version' => '5.31.2', 'cas_connection_timeout' => '5', 'cas_result_caching' => 'db', 'maxima_command' => '', 'plot_command' => '', 'cas_debugging' => '0');
	foreach ($connection_default_values as $paremeter_name => $value)
	{
		$db->insert("xqcas_configuration", array('parameter_name' => array('text', $paremeter_name), 'value' => array('clob', $value), 'group_name' => array('text', 'connection')));
	}
}

//Check if display entries in DB have been created, otherwise create it.
$query = 'SELECT * FROM xqcas_configuration WHERE group_name = "display"';
$result = $db->query($query);
if (!$db->fetchAssoc($result))
{
	$display_default_values = array('instant_validation' => '0', 'maths_filter' => 'mathjax', 'replace_dollars' => '1');
	foreach ($display_default_values as $paremeter_name => $value)
	{
		$db->insert("xqcas_configuration", array('parameter_name' => array('text', $paremeter_name), 'value' => array('clob', $value), 'group_name' => array('text', 'display')));
	}
}

//Check if default options entries in DB have been created, otherwise create it.
$query = 'SELECT * FROM xqcas_configuration WHERE group_name = "options"';
$result = $db->query($query);
if (!$db->fetchAssoc($result))
{
	$options_default_values = array('options_question_simplify' => '1', 'options_assume_positive' => '0', 'options_prt_correct' => 'Correct answer, well done.', 'options_prt_partially_correct' => 'Your answer is partially correct.', 'options_prt_incorrect' => 'Incorrect answer.', 'options_multiplication_sign' => 'dot', 'options_sqrt_sign' => '1', 'options_complex_numbers' => 'i', 'options_inverse_trigonometric' => 'cos-1');
	foreach ($options_default_values as $paremeter_name => $value)
	{
		$db->insert("xqcas_configuration", array('parameter_name' => array('text', $paremeter_name), 'value' => array('clob', $value), 'group_name' => array('text', 'options')));
	}
}


//Check if default input entries in DB have been created, otherwise create it.
$query = 'SELECT * FROM xqcas_configuration WHERE group_name = "inputs"';
$result = $db->query($query);
if (!$db->fetchAssoc($result))
{
	$inputs_default_values = array('input_type' => 'algebraic', 'input_box_size' => '15', 'input_strict_syntax' => '1', 'input_insert_stars' => '0', 'input_forbidden_words' => '', 'input_forbid_float' => '1', 'input_require_lowest_terms' => '0', 'input_check_answer_type' => '0', 'input_must_verify' => '1', 'input_show_validation' => '1');
	foreach ($inputs_default_values as $paremeter_name => $value)
	{
		$db->insert("xqcas_configuration", array('parameter_name' => array('text', $paremeter_name), 'value' => array('clob', $value), 'group_name' => array('text', 'inputs')));
	}
}

require_once('./Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/model/configuration/class.assStackQuestionConfig.php');
$config = new assStackQuestionConfig();
$config->setDefaultSettingsForConnection();
?>
<#19>
<?php
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('xqcas_ilias_specific'))
{

//Inserting index
//Inputs
	$db->addIndex('xqcas_inputs', array('question_id', 'name'), 'i1', FALSE);
//PRT Nodes
	$db->addIndex('xqcas_prt_nodes', array('question_id', 'prt_name', 'node_name'), 'i2', FALSE);
//Cache
	$db->addIndex('xqcas_cas_cache', array('hash'), 'i3', FALSE);
//Tests
	$db->addIndex('xqcas_qtest_inputs', array('question_id', 'test_case', 'input_name'), 'i4', FALSE);
	$db->addIndex('xqcas_qtest_expected', array('question_id', 'test_case', 'prt_name'), 'i5', FALSE);
//Seeds
	$db->addIndex('xqcas_deployed_seeds', array('question_id', 'seed'), 'i6', FALSE);
}
?>
<#20>
<?php
global $DIC;
$db = $DIC->database();
//Adding extra fields in moodle XML
//Penalty
$penalty_column = array('type' => 'text', 'length' => 21);
if (!$db->tableColumnExists("xqcas_ilias_specific", "penalty"))
{
	$db->addTableColumn("xqcas_ilias_specific", "penalty", $penalty_column);
}
//Hidden

$hidden_column = array('type' => 'integer', 'length' => 4);
if (!$db->tableColumnExists("xqcas_ilias_specific", "hídden"))
{
	$db->addTableColumn("xqcas_ilias_specific", "hidden", $hidden_column);
}
?>
<#21>
<#22>
<#23>
<?php
global $DIC;
$db = $DIC->database();
//Change name to ilias_specific and sequence
if ($db->tableExists('xqcas_ilias_specific'))
{
	$db->dropTable("xqcas_ilias_specific", FALSE);
	$db->dropTable("xqcas_ilias_specific_seq", FALSE);
}
if (!$db->tableExists('xqcas_extra_info'))
{
	$fields = array('id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'question_id' => array('type' => 'integer', 'length' => 8, 'notnull' => true), 'general_feedback' => array('type' => 'clob'), 'penalty' => array('type' => 'text', 'length' => 21), 'hidden' => array('type' => 'integer', 'length' => 4));
	$db->createTable("xqcas_extra_info", $fields);
	$db->createSequence("xqcas_extra_info");
	$db->addPrimaryKey("xqcas_extra_info", array("id"));
}
?>
<#24>
<#25>
<#26>
<#27>
<?php
/*
 * add id to old version of the plugin
 */
global $DIC;
$db = $DIC->database();
$res = $db->queryF("SELECT * FROM qpl_qst_type WHERE type_tag = %s", array('text'), array('assCasQuestion'));

if ($res->numRows() != 0)
{
	//Update the old plugin name
	$res = $db->query("UPDATE qpl_qst_type SET type_tag = 'assStackQuestion' WHERE type_tag = 'assCasQuestion'");
	//Get last id
	$res = $db->query("SELECT MAX(question_type_id) maxid FROM qpl_qst_type");
	$data = $db->fetchAssoc($res);
	$max = $data["maxid"];
	//Delete new plugin
	$res = $db->query("DELETE FROM qpl_qst_type WHERE question_type_id = " . $max);
}
?>
<#28>
<?php
global $DIC;
$db = $DIC->database();
//Add matrix parens column for STACK 3.3
$matrix_parens = array('type' => 'text', 'length' => 8);
if ($db->tableExists('xqcas_options'))
{
	if (!$db->tableColumnExists("xqcas_options", "matrix_parens"))
	{
		$db->addTableColumn("xqcas_options", "matrix_parens", $matrix_parens);
	}
}
?>
<#29>
<?php
global $DIC;
$db = $DIC->database();
$lng = $DIC->language();//Adding of all feedback placeholder in question specific feedback
if ($db->tableExists('xqcas_options') AND $db->tableExists('xqcas_prts'))
{
	$counter = 0;

	//Get specific feedback text and question_id
	$options_result = $db->query("SELECT question_id, specific_feedback FROM xqcas_options");
	while ($options_row = $db->fetchAssoc($options_result))
	{
		$question_id = $options_row['question_id'];
		$specific_feedback_text = $options_row['specific_feedback'];

		//Get question text of those STACK questions
		$question_result = $db->query("SELECT question_text FROM qpl_questions WHERE question_id = '" . $question_id . "'");
		$question_row = $db->fetchAssoc($question_result);
		$question_text = $question_row['question_text'];

		//If no feedback placeholder in question text and specific_feedback
		if (!preg_match('/\[\[feedback:(.*?)\]\]/', $question_text) AND !preg_match('/\[\[feedback:(.*?)\]\]/', $specific_feedback_text))
		{
			require_once('./Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/model/ilias_object/class.assStackQuestionOptions.php');
			$options = assStackQuestionOptions::_read($question_id);

			//get PRT name
			$prt_results = $db->query("SELECT name FROM xqcas_prts WHERE question_id = '" . $question_id . "'");
			while ($prt_row = $db->fetchAssoc($prt_results))
			{
				$specific_feedback_text .= "<p>[[feedback:";
				$specific_feedback_text .= $prt_row['name'];
				$specific_feedback_text .= "]]</p>";
			}

			//Add placeholder to specific_feedback
			$options->setSpecificFeedback($specific_feedback_text);
			$options->save();
			$counter++;
		}
	}
	ilUtil::sendInfo($lng->txt("qpl_qst_xqcas_questions_updated_new_feedback_system") . ": " . $counter . ". " . $lng->txt("qpl_qst_xqcas_questions_updated_new_feedback_system"));
}
?>
<#30>
<?php
global $DIC;
$db = $DIC->database();
if ($db->tableExists('xqcas_options'))
{
	//Get matrix parens field
	require_once('./Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/model/ilias_object/class.assStackQuestionOptions.php');
	$options_result = $db->query("SELECT question_id, matrix_parens FROM xqcas_options");
	while ($options_row = $db->fetchAssoc($options_result))
	{
		$matrix_parens = $options_row['matrix_parens'];
		$question_id = $options_row['question_id'];
		switch ($matrix_parens)
		{
			case "]":
				$options = assStackQuestionOptions::_read($question_id);
				$options->setMatrixParens("[");
				$options->save();
				break;
			case ")":
				$options = assStackQuestionOptions::_read($question_id);
				$options->setMatrixParens("(");
				$options->save();
				break;
			case "}":
				$options = assStackQuestionOptions::_read($question_id);
				$options->setMatrixParens("{");
				$options->save();
				break;
			default:
				break;
		}
	}
}
?>
<#31>
<?php
global $DIC;
$db = $DIC->database();
if ($db->tableExists('xqcas_options'))
{
	$db->modifyTableColumn("xqcas_options", "question_variables", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "specific_feedback", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "specific_feedback_format", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "question_note", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "question_simplify", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "assume_positive", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "prt_correct", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "prt_correct_format", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "prt_partially_correct", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "prt_partially_correct_format", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "prt_incorrect", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "prt_incorrect_format", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "multiplication_sign", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "sqrt_sign", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "complex_no", array("notnull" => false));
	$db->modifyTableColumn("xqcas_options", "inverse_trig", array("notnull" => false));
}
if ($db->tableExists('xqcas_inputs'))
{
	$db->modifyTableColumn("xqcas_inputs", "name", array("notnull" => false));
	$db->modifyTableColumn("xqcas_inputs", "type", array("notnull" => false));
	$db->modifyTableColumn("xqcas_inputs", "tans", array("notnull" => false));
	$db->modifyTableColumn("xqcas_inputs", "box_size", array("notnull" => false));
	$db->modifyTableColumn("xqcas_inputs", "strict_syntax", array("notnull" => false));
	$db->modifyTableColumn("xqcas_inputs", "insert_stars", array("notnull" => false));
	$db->modifyTableColumn("xqcas_inputs", "syntax_hint", array("notnull" => false));
	$db->modifyTableColumn("xqcas_inputs", "forbid_words", array("notnull" => false));
	$db->modifyTableColumn("xqcas_inputs", "forbid_float", array("notnull" => false));
	$db->modifyTableColumn("xqcas_inputs", "require_lowest_terms", array("notnull" => false));
	$db->modifyTableColumn("xqcas_inputs", "check_answer_type", array("notnull" => false));
	$db->modifyTableColumn("xqcas_inputs", "must_verify", array("notnull" => false));
	$db->modifyTableColumn("xqcas_inputs", "show_validation", array("notnull" => false));
	$db->modifyTableColumn("xqcas_inputs", "options", array("notnull" => false));
	$db->modifyTableColumn("xqcas_inputs", "allow_words", array("notnull" => false));
}
if ($db->tableExists('xqcas_prts'))
{
	$db->modifyTableColumn("xqcas_prts", "name", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prts", "value", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prts", "auto_simplify", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prts", "feedback_variables", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prts", "first_node_name", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prts", "name", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prts", "name", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prts", "name", array("notnull" => false));
}

if ($db->tableExists('xqcas_prt_nodes'))
{
	$db->modifyTableColumn("xqcas_prt_nodes", "prt_name", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "node_name", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "answer_test", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "sans", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "tans", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "test_options", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "quiet", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "true_score_mode", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "true_score", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "true_answer_note", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "true_feedback", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "true_feedback_format", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "false_score_mode", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "false_score", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "false_answer_note", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "false_feedback", array("notnull" => false));
	$db->modifyTableColumn("xqcas_prt_nodes", "false_feedback_format", array("notnull" => false));
}
if ($db->tableExists('xqcas_qtests'))
{
	$db->modifyTableColumn("xqcas_qtests", "test_case", array("notnull" => false));
}
if ($db->tableExists('xqcas_qtest_inputs'))
{
	$db->modifyTableColumn("xqcas_qtest_inputs", "test_case", array("notnull" => false));
	$db->modifyTableColumn("xqcas_qtest_inputs", "input_name", array("notnull" => false));
	$db->modifyTableColumn("xqcas_qtest_inputs", "value", array("notnull" => false));
}
if ($db->tableExists('xqcas_qtest_expected'))
{
	$db->modifyTableColumn("xqcas_qtest_expected", "test_case", array("notnull" => false));
	$db->modifyTableColumn("xqcas_qtest_expected", "prt_name", array("notnull" => false));
	$db->modifyTableColumn("xqcas_qtest_expected", "expected_answer_note", array("notnull" => false));
}
?>
<#32>
<?php

/*
 */

try
{
	global $DIC;
	$db = $DIC->database();

	$lng = $DIC->language();
	require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

//DB Update Script TEST
//Check for STACK questions IDs

//Change questions base
//Change all question Texts in qpl_questions of questions with question_fi same as STACK type, collect all

//Get assStackQuestion question type id in current platform
	$type_id_query = "SELECT question_type_id FROM qpl_qst_type WHERE type_tag = \"assStackQuestion\"";
	$res = $db->query($type_id_query);
	$data = $db->fetchAssoc($res);
	$type_id = $data["question_type_id"];

//Change question texts and get all question_id of stack questions
	$select_stack_questions_query = "SELECT question_id, question_text FROM qpl_questions WHERE question_type_fi = " . $type_id;
	$stack_questions_result = $db->query($select_stack_questions_query);
	while ($row = $db->fetchAssoc($stack_questions_result))
	{
		$fieldData = array("question_text" => array("clob", assStackQuestionUtils::_casTextConverter($row["question_text"])), "tstamp" => array("integer", time()));

		$db->update("qpl_questions", $fieldData, array('question_id' => array('integer', $row["question_id"])));
	}

//Change all question note and specific feedback
	$select_question_note_specific_feedback_query = "SELECT question_id, question_note, specific_feedback FROM xqcas_options";
	$stack_options_result = $db->query($select_question_note_specific_feedback_query);
	while ($row = $db->fetchAssoc($stack_options_result))
	{
		if ($row["question_note"] != "")
		{
			$fieldData = array("question_note" => array("clob", assStackQuestionUtils::_casTextConverter($row["question_note"])));

			$db->update("xqcas_options", $fieldData, array('question_id' => array('integer', $row["question_id"])));
		}

		if ($row["specific_feedback"] != "")
		{
			$fieldData = array("specific_feedback" => array("clob", assStackQuestionUtils::_casTextConverter($row["specific_feedback"])));

			$db->update("xqcas_options", $fieldData, array('question_id' => array('integer', $row["question_id"])));
		}
	}

//General feedback from xqcas_extra_info
	$select_general_feedback_query = "SELECT question_id, general_feedback FROM xqcas_extra_info";
	$stack_general_feedback_result = $db->query($select_general_feedback_query);
	while ($row = $db->fetchAssoc($stack_general_feedback_result))
	{
		if ($row["general_feedback"] != "")
		{
			$fieldData = array("general_feedback" => array("clob", assStackQuestionUtils::_casTextConverter($row["general_feedback"])));

			$db->update("xqcas_extra_info", $fieldData, array('question_id' => array('integer', $row["question_id"])));
		}
	}
//True feedback and false feedback from xqcas_prt_nodes
	$select_truefalse_feedback_query = "SELECT question_id, prt_name, node_name, true_feedback, false_feedback FROM xqcas_prt_nodes";
	$stack_truefalse_feedback_result = $db->query($select_truefalse_feedback_query);
	while ($row = $db->fetchAssoc($stack_truefalse_feedback_result))
	{
		if ($row["true_feedback"] != "")
		{
			$fieldData = array("true_feedback" => array("clob", assStackQuestionUtils::_casTextConverter($row["true_feedback"])));

			$db->update("xqcas_prt_nodes", $fieldData, array('question_id' => array('integer', $row["question_id"]),'prt_name' => array('text', $row["prt_name"]),'node_name' => array('text', $row["node_name"])));
		}

		if ($row["false_feedback"] != "")
		{
			$fieldData = array("false_feedback" => array("clob", assStackQuestionUtils::_casTextConverter($row["false_feedback"])));

			$db->update("xqcas_prt_nodes", $fieldData, array('question_id' => array('integer', $row["question_id"]),'prt_name' => array('text', $row["prt_name"]),'node_name' => array('text', $row["node_name"])));
		}
	}

	ilUtil::sendInfo($lng->txt("qpl_qst_xqcas_update_to_version_3"), TRUE);
} catch (ResponseSendingException $e)
{
	ilUtil::sendFailure($e->getMessage(), TRUE);
	throw new Exception("Error in the update script of all current questions of the platform, try to run the db update again");
}

?>
<#33>
<?php
global $DIC;
$db = $DIC->database();
if ($db->tableExists('xqcas_configuration'))
{
	$db->replace("xqcas_configuration", array('parameter_name' => array('text', 'cas_maxima_libraries'), 'value' => array('clob', ''), 'group_name' => array('text', 'connection')), array());
}
?>
<#34>
<?php
global $DIC;
$db = $DIC->database();

//Inserting index that were not inserted in step 19

//Inputs
if (!$db->indexExistsByFields('xqcas_inputs', array('question_id', 'name')))
{
	$db->addIndex('xqcas_inputs', array('question_id', 'name'), 'i1', FALSE);
}

//PRT Nodes
if (!$db->indexExistsByFields('xqcas_prt_nodes', array('question_id', 'prt_name', 'node_name')))
{
	$db->addIndex('xqcas_prt_nodes', array('question_id', 'prt_name', 'node_name'), 'i2', FALSE);
}

//Cache
if (!$db->indexExistsByFields('xqcas_cas_cache', array('hash')))
{
	$db->addIndex('xqcas_cas_cache', array('hash'), 'i3', FALSE);
}

//Tests
if (!$db->indexExistsByFields('xqcas_qtest_inputs', array('question_id', 'test_case', 'input_name')))
{
	$db->addIndex('xqcas_qtest_inputs', array('question_id', 'test_case', 'input_name'), 'i4', FALSE);
}
if (!$db->indexExistsByFields('xqcas_qtest_expected', array('question_id', 'test_case', 'prt_name')))
{
	$db->addIndex('xqcas_qtest_expected', array('question_id', 'test_case', 'prt_name'), 'i5', FALSE);
}

//Seeds
if (!$db->indexExistsByFields('xqcas_deployed_seeds', array('question_id', 'seed')))
{
	$db->addIndex('xqcas_deployed_seeds', array('question_id', 'seed'), 'i6', FALSE);
}
?>
<#35>
<?php
global $DIC;
$db = $DIC->database();
//Default Option for Matrix Parenthesis
if ($db->tableExists('xqcas_configuration'))
{
    //Options
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "options_matrix_parents"), 'value' => array('clob', '['), 'group_name' => array('text', 'options')));
	//Inputs
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "input_syntax_hint"), 'value' => array('clob', ''), 'group_name' => array('text', 'inputs')));
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "input_allow_words"), 'value' => array('clob', ''), 'group_name' => array('text', 'inputs')));
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "input_extra_options"), 'value' => array('clob', ''), 'group_name' => array('text', 'inputs')));
	//PRTs
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "prt_simplify"), 'value' => array('clob', '1'), 'group_name' => array('text', 'prts')));
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "prt_node_answer_test"), 'value' => array('clob', 'AlgEquiv'), 'group_name' => array('text', 'prts')));
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "prt_node_options"), 'value' => array('clob', ''), 'group_name' => array('text', 'prts')));
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "prt_node_quiet"), 'value' => array('clob', '1'), 'group_name' => array('text', 'prts')));
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "prt_pos_mod"), 'value' => array('clob', '+'), 'group_name' => array('text', 'prts')));
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "prt_pos_score"), 'value' => array('clob', '1'), 'group_name' => array('text', 'prts')));
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "prt_pos_penalty"), 'value' => array('clob', '0'), 'group_name' => array('text', 'prts')));
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "prt_pos_answernote"), 'value' => array('clob', 'prt1-0-T'), 'group_name' => array('text', 'prts')));
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "prt_neg_mod"), 'value' => array('clob', '+'), 'group_name' => array('text', 'prts')));
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "prt_neg_score"), 'value' => array('clob', '0'), 'group_name' => array('text', 'prts')));
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "prt_neg_penalty"), 'value' => array('clob', '0'), 'group_name' => array('text', 'prts')));
	$db->insert("xqcas_configuration", array('parameter_name' => array('text', "prt_neg_answernote"), 'value' => array('clob', 'prt1-0-F'), 'group_name' => array('text', 'prts')));
}
?>