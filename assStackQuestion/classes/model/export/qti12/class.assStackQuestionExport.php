<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
include_once "./Modules/TestQuestionPool/classes/export/qti12/class.assQuestionExport.php";
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question EXPORT to ILIAS format management class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: 1.8$$
 *
 */
class assStackQuestionExport extends assQuestionExport
{
	/**
	 * Returns a QTI xml representation of the question
	 *
	 * @return string The QTI xml representation of the question
	 * @access public
	 */
	function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false)
	{
		global $ilias;

		include_once("./Services/Xml/classes/class.ilXmlWriter.php");
		$a_xml_writer = new ilXmlWriter;
		// set xml header
		$a_xml_writer->xmlHeader();
		$a_xml_writer->xmlStartTag("questestinterop");
		$attrs = array(
			"ident" => "il_" . IL_INST_ID . "_qst_" . $this->object->getId(),
			"title" => $this->object->getTitle(),
			"maxattempts" => $this->object->getNrOfTries()
		);
		$a_xml_writer->xmlStartTag("item", $attrs);
		// add question description
		$a_xml_writer->xmlElement("qticomment", NULL, $this->object->getComment());
		// add estimated working time
		$workingtime = $this->object->getEstimatedWorkingTime();
		$duration = sprintf("P0Y0M0DT%dH%dM%dS", $workingtime["h"], $workingtime["m"], $workingtime["s"]);
		$a_xml_writer->xmlElement("duration", NULL, $duration);
		// add ILIAS specific metadata
		$a_xml_writer->xmlStartTag("itemmetadata");
		$a_xml_writer->xmlStartTag("qtimetadata");
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "ILIAS_VERSION");
		$a_xml_writer->xmlElement("fieldentry", NULL, $ilias->getSetting("ilias_version"));
		$a_xml_writer->xmlEndTag("qtimetadatafield");
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "QUESTIONTYPE");
		$a_xml_writer->xmlElement("fieldentry", NULL, $this->object->getQuestionType());
		$a_xml_writer->xmlEndTag("qtimetadatafield");
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "AUTHOR");
		$a_xml_writer->xmlElement("fieldentry", NULL, $this->object->getAuthor());
		$a_xml_writer->xmlEndTag("qtimetadatafield");
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "POINTS");
		$a_xml_writer->xmlElement("fieldentry", NULL, $this->object->getPoints());
		$a_xml_writer->xmlEndTag("qtimetadatafield");

		//OPTIONS
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "options");
		$a_xml_writer->xmlElement("fieldentry", NULL, base64_encode(serialize($this->object->getOptions())));
		$a_xml_writer->xmlEndTag("qtimetadatafield");

		//INPUTS
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "inputs");
		$a_xml_writer->xmlElement("fieldentry", NULL, base64_encode(serialize($this->object->getInputs())));
		$a_xml_writer->xmlEndTag("qtimetadatafield");

		//PRTS
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "prts");
		$a_xml_writer->xmlElement("fieldentry", NULL, base64_encode(serialize($this->object->getPotentialResponsesTrees())));
		$a_xml_writer->xmlEndTag("qtimetadatafield");

		//SEEDS
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "seeds");
		$a_xml_writer->xmlElement("fieldentry", NULL, base64_encode(serialize($this->object->getDeployedSeeds())));
		$a_xml_writer->xmlEndTag("qtimetadatafield");

		//TESTS
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "tests");
		$a_xml_writer->xmlElement("fieldentry", NULL, base64_encode(serialize($this->object->getTests())));
		$a_xml_writer->xmlEndTag("qtimetadatafield");

		//EXTRA INFO
		$a_xml_writer->xmlStartTag("qtimetadatafield");
		$a_xml_writer->xmlElement("fieldlabel", NULL, "extra_info");
		$a_xml_writer->xmlElement("fieldentry", NULL, base64_encode(serialize($this->object->getExtraInfo())));
		$a_xml_writer->xmlEndTag("qtimetadatafield");

		// additional content editing information
		$this->addAdditionalContentEditingModeInformation($a_xml_writer);
		$this->addGeneralMetadata($a_xml_writer);

		$a_xml_writer->xmlEndTag("qtimetadata");
		$a_xml_writer->xmlEndTag("itemmetadata");

		// PART I: qti presentation
		$attrs = array(
			"label" => $this->object->getTitle()
		);
		$a_xml_writer->xmlStartTag("presentation", $attrs);
		// add flow to presentation
		$a_xml_writer->xmlStartTag("flow");
		// add material with question text to presentation
		$this->object->addQTIMaterial($a_xml_writer, $this->object->getQuestion());
		$this->object->addQTIMaterial($a_xml_writer, $this->object->getOptions()->getPRTCorrect());
		$this->object->addQTIMaterial($a_xml_writer, $this->object->getOptions()->getPRTIncorrect());
		$this->object->addQTIMaterial($a_xml_writer, $this->object->getOptions()->getPRTPartiallyCorrect());
		$this->object->addQTIMaterial($a_xml_writer, $this->object->getOptions()->getSpecificFeedback());
		$this->object->addQTIMaterial($a_xml_writer, $this->object->getExtraInfo()->getHowToSolve());
		foreach ($this->object->getPotentialResponsesTrees() as $prt_name => $prt) {
			foreach ($prt->getPRTNodes() as $node_name => $node) {
				$this->object->addQTIMaterial($a_xml_writer, $node->getFalseFeedback());
				$this->object->addQTIMaterial($a_xml_writer, $node->getTrueFeedback());
			}
		}

		/*
		// PART III: qti itemfeedback
		$feedback_allcorrect = $this->object->feedbackOBJ->getGenericFeedbackExportPresentation(
			$this->object->getId(), true
		);

		$feedback_onenotcorrect = $this->object->feedbackOBJ->getGenericFeedbackExportPresentation(
			$this->object->getId(), false
		);

		$attrs = array(
			"ident" => "Correct",
			"view" => "All"
		);
		$a_xml_writer->xmlStartTag("itemfeedback", $attrs);
		// qti flow_mat
		$a_xml_writer->xmlStartTag("flow_mat");
		$a_xml_writer->xmlStartTag("material");
		$a_xml_writer->xmlElement("mattext");
		$a_xml_writer->xmlEndTag("material");
		$a_xml_writer->xmlEndTag("flow_mat");
		$a_xml_writer->xmlEndTag("itemfeedback");
		if (strlen($feedback_allcorrect)) {
			$attrs = array(
				"ident" => "response_allcorrect",
				"view" => "All"
			);
			$a_xml_writer->xmlStartTag("itemfeedback", $attrs);
			// qti flow_mat
			$a_xml_writer->xmlStartTag("flow_mat");
			$this->object->addQTIMaterial($a_xml_writer, $feedback_allcorrect);
			$a_xml_writer->xmlEndTag("flow_mat");
			$a_xml_writer->xmlEndTag("itemfeedback");
		}
		if (strlen($feedback_onenotcorrect)) {
			$attrs = array(
				"ident" => "response_onenotcorrect",
				"view" => "All"
			);
			$a_xml_writer->xmlStartTag("itemfeedback", $attrs);
			// qti flow_mat
			$a_xml_writer->xmlStartTag("flow_mat");
			$this->object->addQTIMaterial($a_xml_writer, $feedback_onenotcorrect);
			$a_xml_writer->xmlEndTag("flow_mat");
			$a_xml_writer->xmlEndTag("itemfeedback");
		}


*/
		$a_xml_writer->xmlEndTag("flow");
		$a_xml_writer->xmlEndTag("presentation");
		$a_xml_writer->xmlEndTag("item");
		$a_xml_writer->xmlEndTag("questestinterop");

		$xml = $a_xml_writer->xmlDumpMem(FALSE);
		if (!$a_include_header) {
			$pos = strpos($xml, "?>");
			$xml = substr($xml, $pos + 2);
		}
		return $xml;
	}



	/*
	//OPTIONS
	$options = $this->object->getOptions();
	$attrs = array();

	//Options attributes
	$attrs["question_id"] = $options->getQuestionId();
	$a_xml_writer->xmlStartTag("options", $attrs);

	//Options elements
	$a_xml_writer->xmlElement("options_id", $attrs, $a_xml_writer->xmlEncodeData($options->getOptionsId()));
	$a_xml_writer->xmlElement("question_id", $attrs, $a_xml_writer->xmlEncodeData($options->getQuestionId()));
	$a_xml_writer->xmlElement("question_variables", $attrs, $a_xml_writer->xmlEncodeData($options->getQuestionVariables()));
	$a_xml_writer->xmlElement("specific_feedback", $attrs, $a_xml_writer->xmlEncodeData($options->getSpecificFeedback()));
	$a_xml_writer->xmlElement("question_note", $attrs, $a_xml_writer->xmlEncodeData($options->getQuestionNote()));
	$a_xml_writer->xmlElement("question_simplify", $attrs, $a_xml_writer->xmlEncodeData($options->getQuestionSimplify()));
	$a_xml_writer->xmlElement("assume_positive", $attrs, $a_xml_writer->xmlEncodeData($options->getAssumePositive()));
	$a_xml_writer->xmlElement("prt_correct", $attrs, $a_xml_writer->xmlEncodeData($options->getPRTCorrect()));
	$a_xml_writer->xmlElement("prt_partially_correct", $attrs, $a_xml_writer->xmlEncodeData($options->getPRTPartiallyCorrect()));
	$a_xml_writer->xmlElement("prt_incorrect", $attrs, $a_xml_writer->xmlEncodeData($options->getPRTIncorrect()));
	$a_xml_writer->xmlElement("multiplication_sign", $attrs, $a_xml_writer->xmlEncodeData($options->getMultiplicationSign()));
	$a_xml_writer->xmlElement("sqrt_sign", $attrs, $a_xml_writer->xmlEncodeData($options->getSqrtSign()));
	$a_xml_writer->xmlElement("complex_numbers", $attrs, $a_xml_writer->xmlEncodeData($options->getComplexNumbers()));
	$a_xml_writer->xmlElement("inverse_trig", $attrs, $a_xml_writer->xmlEncodeData($options->getInverseTrig()));
	$a_xml_writer->xmlElement("variants_selection_seeds", $attrs, $a_xml_writer->xmlEncodeData($options->getVariantsSelectionSeeds()));

	$a_xml_writer->xmlEndTag("options");

	//INPUTS
	$inputs = $this->object->getInputs();
	$attrs = array();

	//Inputs attributes
	$attrs["question_id"] = $this->object->getId();
	$a_xml_writer->xmlStartTag("inputs", $attrs);

	foreach ($inputs as $input_name => $input) {
		$attrs = array();
		//Inputs attributes
		$attrs["input_name"] = $input_name;
		$attrs["question_id"] = $input->getQuestionId();
		$a_xml_writer->xmlStartTag("input", $attrs);

		//Inputs elements
		$a_xml_writer->xmlElement("input_id", $attrs, $a_xml_writer->xmlEncodeData($input->getInputId()));
		$a_xml_writer->xmlElement("question_id", $attrs, $a_xml_writer->xmlEncodeData($input->getQuestionId()));
		$a_xml_writer->xmlElement("input_name", $attrs, $a_xml_writer->xmlEncodeData($input->getInputName()));
		$a_xml_writer->xmlElement("input_type", $attrs, $a_xml_writer->xmlEncodeData($input->getInputType()));
		$a_xml_writer->xmlElement("teacher_answer", $attrs, $a_xml_writer->xmlEncodeData($input->getTeacherAnswer()));
		$a_xml_writer->xmlElement("box_size", $attrs, $a_xml_writer->xmlEncodeData($input->getBoxSize()));
		$a_xml_writer->xmlElement("strict_syntax", $attrs, $a_xml_writer->xmlEncodeData($input->getStrictSyntax()));
		$a_xml_writer->xmlElement("insert_stars", $attrs, $a_xml_writer->xmlEncodeData($input->getInsertStars()));
		$a_xml_writer->xmlElement("syntax_hint", $attrs, $a_xml_writer->xmlEncodeData($input->getSyntaxHint()));
		$a_xml_writer->xmlElement("forbid_words", $attrs, $a_xml_writer->xmlEncodeData($input->getForbidWords()));
		$a_xml_writer->xmlElement("allow_words", $attrs, $a_xml_writer->xmlEncodeData($input->getAllowWords()));
		$a_xml_writer->xmlElement("forbid_float", $attrs, $a_xml_writer->xmlEncodeData($input->getForbidFloat()));
		$a_xml_writer->xmlElement("require_lowest_terms", $attrs, $a_xml_writer->xmlEncodeData($input->getRequireLowestTerms()));
		$a_xml_writer->xmlElement("check_answer_type", $attrs, $a_xml_writer->xmlEncodeData($input->getCheckAnswerType()));
		$a_xml_writer->xmlElement("must_verify", $attrs, $a_xml_writer->xmlEncodeData($input->getMustVerify()));
		$a_xml_writer->xmlElement("show_validation", $attrs, $a_xml_writer->xmlEncodeData($input->getShowValidation()));
		$a_xml_writer->xmlElement("options", $attrs, $a_xml_writer->xmlEncodeData($input->getOptions()));

		$a_xml_writer->xmlEndTag("input");
	}
	$a_xml_writer->xmlEndTag("inputs");

	//PRTS and NODES
	$prts = $this->object->getPotentialResponsesTrees();
	$attrs = array();

	//PRTS attributes
	$attrs["question_id"] = $this->object->getId();
	$a_xml_writer->xmlStartTag("prts", $attrs);

	foreach ($prts as $prt_name => $prt) {
		$attrs = array();
		//PRT attributes
		$attrs["prt_name"] = $prt_name;
		$attrs["question_id"] = $prt->getQuestionId();
		$a_xml_writer->xmlStartTag("prt", $attrs);

		//PRT elements
		$a_xml_writer->xmlElement("prt_id", $attrs, $a_xml_writer->xmlEncodeData($prt->getPRTId()));
		$a_xml_writer->xmlElement("question_id", $attrs, $a_xml_writer->xmlEncodeData($prt->getQuestionId()));
		$a_xml_writer->xmlElement("prt_name", $attrs, $a_xml_writer->xmlEncodeData($prt->getPRTName()));
		$a_xml_writer->xmlElement("prt_value", $attrs, $a_xml_writer->xmlEncodeData($prt->getPRTValue()));
		$a_xml_writer->xmlElement("auto_simplify", $attrs, $a_xml_writer->xmlEncodeData($prt->getAutoSimplify()));
		$a_xml_writer->xmlElement("prt_feedback_variables", $attrs, $a_xml_writer->xmlEncodeData($prt->getPRTFeedbackVariables()));
		$a_xml_writer->xmlElement("first_node_name", $attrs, $a_xml_writer->xmlEncodeData($prt->getFirstNodeName()));

		//PRT NODES
		foreach ($prt->getPRTNodes() as $node) {
			$attrs = array();
			//PRT Node attributes
			$attrs["prt"] = $node->getPRTName();
			$attrs["node"] = $node->getNodeName();
			$attrs["question_id"] = $node->getQuestionId();
			$a_xml_writer->xmlStartTag("prt_node", $attrs);

			//PRT Node elements
			$a_xml_writer->xmlElement("node_id", $attrs, $a_xml_writer->xmlEncodeData($node->getNodeId()));
			$a_xml_writer->xmlElement("question_id", $attrs, $a_xml_writer->xmlEncodeData($node->getQuestionId()));
			$a_xml_writer->xmlElement("prt_name", $attrs, $a_xml_writer->xmlEncodeData($node->getPRTName()));
			$a_xml_writer->xmlElement("node_name", $attrs, $a_xml_writer->xmlEncodeData($node->getNodeName()));
			$a_xml_writer->xmlElement("answer_test", $attrs, $a_xml_writer->xmlEncodeData($node->getAnswerTest()));
			$a_xml_writer->xmlElement("student_answer", $attrs, $a_xml_writer->xmlEncodeData($node->getStudentAnswer()));
			$a_xml_writer->xmlElement("teacher_answer", $attrs, $a_xml_writer->xmlEncodeData($node->getTeacherAnswer()));
			$a_xml_writer->xmlElement("test_options", $attrs, $a_xml_writer->xmlEncodeData($node->getTestOptions()));
			$a_xml_writer->xmlElement("quiet", $attrs, $a_xml_writer->xmlEncodeData($node->getQuiet()));
			$a_xml_writer->xmlElement("true_score_mode", $attrs, $a_xml_writer->xmlEncodeData($node->getTrueScoreMode()));
			$a_xml_writer->xmlElement("true_score", $attrs, $a_xml_writer->xmlEncodeData($node->getTrueScore()));
			$a_xml_writer->xmlElement("true_penalty", $attrs, $a_xml_writer->xmlEncodeData($node->getTruePenalty()));
			$a_xml_writer->xmlElement("true_next_node", $attrs, $a_xml_writer->xmlEncodeData($node->getTrueNextNode()));
			$a_xml_writer->xmlElement("true_answer_note", $attrs, $a_xml_writer->xmlEncodeData($node->getTrueAnswerNote()));
			$a_xml_writer->xmlElement("true_feedback", $attrs, $a_xml_writer->xmlEncodeData($node->getTrueFeedback()));
			$a_xml_writer->xmlElement("true_feedback_format", $attrs, $a_xml_writer->xmlEncodeData($node->getTrueFeedbackFormat()));
			$a_xml_writer->xmlElement("false_score_mode", $attrs, $a_xml_writer->xmlEncodeData($node->getFalseScoreMode()));
			$a_xml_writer->xmlElement("false_score", $attrs, $a_xml_writer->xmlEncodeData($node->getFalseScore()));
			$a_xml_writer->xmlElement("false_penalty", $attrs, $a_xml_writer->xmlEncodeData($node->getFalsePenalty()));
			$a_xml_writer->xmlElement("false_next_node", $attrs, $a_xml_writer->xmlEncodeData($node->getFalseNextNode()));
			$a_xml_writer->xmlElement("false_answer_note", $attrs, $a_xml_writer->xmlEncodeData($node->getFalseAnswerNote()));
			$a_xml_writer->xmlElement("false_feedback", $attrs, $a_xml_writer->xmlEncodeData($node->getFalseFeedback()));
			$a_xml_writer->xmlElement("false_feedback_format", $attrs, $a_xml_writer->xmlEncodeData($node->getFalseFeedbackFormat()));

			$a_xml_writer->xmlEndTag("prt_node");

		}
		$a_xml_writer->xmlEndTag("prt");
	}
	$a_xml_writer->xmlEndTag("prts");

	//SEEDS
	$seeds = $this->object->getDeployedSeeds();
	$attrs = array();

	//seeds attributes
	$attrs["question_id"] = $this->object->getId();
	$a_xml_writer->xmlStartTag("seeds", $attrs);

	if (is_array($seeds)) {
		foreach ($seeds as $seed) {
			if (is_a($seed, 'assStackQuestionDeployedSeed')) {
				$attrs = array();
				//Seed attributes
				$attrs["seed"] = $seed->getSeed();
				$attrs["question_id"] = $seed->getQuestionId();
				$a_xml_writer->xmlStartTag("seed", $attrs);

				//Seed elements
				$a_xml_writer->xmlElement("seed_id", $attrs, $a_xml_writer->xmlEncodeData($seed->getSeedId()));
				$a_xml_writer->xmlElement("question_id", $attrs, $a_xml_writer->xmlEncodeData($seed->getQuestionId()));
				$a_xml_writer->xmlElement("seed", $attrs, $a_xml_writer->xmlEncodeData($seed->getSeed()));
				$a_xml_writer->xmlElement("question_note", $attrs, $a_xml_writer->xmlEncodeData($seed->getQuestionNote()));

				$a_xml_writer->xmlEndTag("seed");
			}
		}
	}
	$a_xml_writer->xmlEndTag("seeds");

	//TESTS
	$tests = $this->object->getTests();
	$attrs = array();

	//tests attributes
	$attrs["question_id"] = $this->object->getId();
	$a_xml_writer->xmlStartTag("tests", $attrs);

	foreach ($tests as $testcase => $test) {
		$attrs = array();
		//test attributes
		$attrs["test_case"] = $testcase;
		$attrs["question_id"] = $test->getQuestionId();
		$a_xml_writer->xmlStartTag("test", $attrs);

		//test elements
		$a_xml_writer->xmlElement("test_id", $attrs, $a_xml_writer->xmlEncodeData($test->getTestId()));
		$a_xml_writer->xmlElement("question_id", $attrs, $a_xml_writer->xmlEncodeData($test->getQuestionId()));
		$a_xml_writer->xmlElement("test_case", $attrs, $a_xml_writer->xmlEncodeData($test->getTestCase()));
		$a_xml_writer->xmlElement("number_of_tests", $attrs, $a_xml_writer->xmlEncodeData($test->getTestInputs()));

		//TEST INPUTS
		$attrs = array();
		//Test inputs attributes
		$attrs["test_case"] = $testcase;
		$attrs["question_id"] = $this->object->getId();
		$a_xml_writer->xmlStartTag("test_inputs", $attrs);

		foreach ($test->getTestInputs() as $test_input) {
			$attrs = array();
			//Test inputs attributes
			$attrs["test_case"] = $test_input->getTestCase();
			$attrs["input"] = $test_input->getTestInputName();
			$attrs["question_id"] = $test_input->getQuestionId();
			$a_xml_writer->xmlStartTag("input", $attrs);

			//Test inputs elements
			$a_xml_writer->xmlElement("test_input_id", $attrs, $a_xml_writer->xmlEncodeData($test_input->getTestInputId()));
			$a_xml_writer->xmlElement("question_id", $attrs, $a_xml_writer->xmlEncodeData($test_input->getQuestionId()));
			$a_xml_writer->xmlElement("test_case", $attrs, $a_xml_writer->xmlEncodeData($test_input->getTestCase()));
			$a_xml_writer->xmlElement("test_input_name", $attrs, $a_xml_writer->xmlEncodeData($test_input->getTestInputName()));
			$a_xml_writer->xmlElement("test_input_value", $attrs, $a_xml_writer->xmlEncodeData($test_input->getTestInputValue()));

			$a_xml_writer->xmlEndTag("input");

		}
		$a_xml_writer->xmlEndTag("test_inputs");

		//TEST EXPECTED
		$attrs = array();
		//Test inputs attributes
		$attrs["test_case"] = $testcase;
		$attrs["question_id"] = $this->object->getId();
		$a_xml_writer->xmlStartTag("test_expected", $attrs);

		foreach ($test->getTestExpected() as $test_expected) {
			$attrs = array();
			//Test inputs attributes
			$attrs["test_case"] = $test_expected->getTestCase();
			$attrs["prt"] = $test_expected->getTestPRTName();
			$attrs["question_id"] = $test_expected->getQuestionId();
			$a_xml_writer->xmlStartTag("expected", $attrs);

			//Test inputs elements
			$a_xml_writer->xmlElement("test_expected_id", $attrs, $a_xml_writer->xmlEncodeData($test_expected->getTestExpectedId()));
			$a_xml_writer->xmlElement("question_id", $attrs, $a_xml_writer->xmlEncodeData($test_expected->getQuestionId()));
			$a_xml_writer->xmlElement("test_case", $attrs, $a_xml_writer->xmlEncodeData($test_expected->getTestCase()));
			$a_xml_writer->xmlElement("test_prt_name", $attrs, $a_xml_writer->xmlEncodeData($test_expected->getTestPRTName()));
			$a_xml_writer->xmlElement("expected_score", $attrs, $a_xml_writer->xmlEncodeData($test_expected->getExpectedScore()));
			$a_xml_writer->xmlElement("expected_penalty", $attrs, $a_xml_writer->xmlEncodeData($test_expected->getExpectedPenalty()));
			$a_xml_writer->xmlElement("expected_answer_note", $attrs, $a_xml_writer->xmlEncodeData($test_expected->getExpectedAnswerNote()));

			$a_xml_writer->xmlEndTag("expected");

		}
		$a_xml_writer->xmlEndTag("test_expected");

		$a_xml_writer->xmlEndTag("test");
	}
	$a_xml_writer->xmlEndTag("tests");

	//EXTRA INFO
	$extra_info = $this->object->getExtraInfo();
	$attrs = array();

	//extra info attributes
	$attrs["question_id"] = $options->getQuestionId();
	$a_xml_writer->xmlStartTag("extra_info", $attrs);

	//extra info elements
	$a_xml_writer->xmlElement("points", $attrs, $a_xml_writer->xmlEncodeData($this->object->getPoints()));
	$a_xml_writer->xmlElement("how_to_solve", $attrs, $a_xml_writer->xmlEncodeData($extra_info->getHowToSolve()));
	$a_xml_writer->xmlElement("penalty", $attrs, $a_xml_writer->xmlEncodeData($extra_info->getPenalty()));
	$a_xml_writer->xmlElement("hidden", $attrs, $a_xml_writer->xmlEncodeData($extra_info->getHidden()));

	$a_xml_writer->xmlEndTag("extra_info");
	*/


	/**
	 * Exports the evaluation data to the Microsoft Excel file format
	 *
	 * @param bool $deliver
	 * @param string $filterby
	 * @param string $filtertext Filter text for the user data
	 * @param boolean $passedonly TRUE if only passed user datasets should be exported, FALSE otherwise
	 *
	 * @return string
	 */
	public function exportToExcel($deliver = TRUE, $filterby = "", $filtertext = "", $passedonly = FALSE)
	{
		if (strcmp($this->mode, "aggregated") == 0) return $this->aggregatedResultsToExcel($deliver);

		require_once './Services/Excel/classes/class.ilExcelWriterAdapter.php';
		$excelfile = ilUtil::ilTempnam();
		$adapter = new ilExcelWriterAdapter($excelfile, FALSE);
		$testname = ilUtil::getASCIIFilename(preg_replace("/\s/", "_", $this->test_obj->getTitle())) . ".xls";
		$workbook = $adapter->getWorkbook();
		$workbook->setVersion(8); // Use Excel97/2000 Format
		// Creating a worksheet
		$format_bold =& $workbook->addFormat();
		$format_bold->setBold();
		$format_percent =& $workbook->addFormat();
		$format_percent->setNumFormat("0.00%");
		$format_datetime =& $workbook->addFormat();
		$format_datetime->setNumFormat("DD/MM/YYYY hh:mm:ss");
		$format_title =& $workbook->addFormat();
		$format_title->setBold();
		$format_title->setColor('black');
		$format_title->setPattern(1);
		$format_title->setFgColor('silver');
		require_once './Services/Excel/classes/class.ilExcelUtils.php';
		$worksheet =& $workbook->addWorksheet(ilExcelUtils::_convert_text($this->lng->txt("tst_results")));
		$additionalFields = $this->test_obj->getEvaluationAdditionalFields();
		$row = 0;
		$col = 0;

		if ($this->test_obj->getAnonymity()) {
			$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("counter")), $format_title);
		} else {
			$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("name")), $format_title);
			$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("login")), $format_title);
		}
		if (count($additionalFields)) {
			foreach ($additionalFields as $fieldname) {
				$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt($fieldname)), $format_title);
			}
		}
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("tst_stat_result_resultspoints")), $format_title);
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("maximum_points")), $format_title);
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("tst_stat_result_resultsmarks")), $format_title);
		if ($this->test_obj->ects_output) {
			$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("ects_grade")), $format_title);
		}
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("tst_stat_result_qworkedthrough")), $format_title);
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("tst_stat_result_qmax")), $format_title);
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("tst_stat_result_pworkedthrough")), $format_title);
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("tst_stat_result_timeofwork")), $format_title);
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("tst_stat_result_atimeofwork")), $format_title);
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("tst_stat_result_firstvisit")), $format_title);
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("tst_stat_result_lastvisit")), $format_title);

		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("tst_stat_result_mark_median")), $format_title);
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("tst_stat_result_rank_participant")), $format_title);
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("tst_stat_result_rank_median")), $format_title);
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("tst_stat_result_total_participants")), $format_title);
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("tst_stat_result_median")), $format_title);
		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("scored_pass")), $format_title);

		$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("pass")), $format_title);

		$counter = 1;
		$data =& $this->test_obj->getCompleteEvaluationData(TRUE, $filterby, $filtertext);
		$firstrowwritten = false;
		foreach ($data->getParticipants() as $active_id => $userdata) {
			$remove = FALSE;
			if ($passedonly) {
				if ($data->getParticipant($active_id)->getPassed() == FALSE) {
					$remove = TRUE;
				}
			}
			if (!$remove) {
				$row++;
				if ($this->test_obj->isRandomTest() || $this->test_obj->getShuffleQuestions()) {
					$row++;
				}
				$col = 0;
				if ($this->test_obj->getAnonymity()) {
					$worksheet->write($row, $col++, ilExcelUtils::_convert_text($counter));
				} else {
					$worksheet->write($row, $col++, ilExcelUtils::_convert_text($data->getParticipant($active_id)->getName()));
					$worksheet->write($row, $col++, ilExcelUtils::_convert_text($data->getParticipant($active_id)->getLogin()));
				}
				if (count($additionalFields)) {
					$userfields = ilObjUser::_lookupFields($userdata->getUserID());
					foreach ($additionalFields as $fieldname) {
						if (strcmp($fieldname, "gender") == 0) {
							$worksheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt("gender_" . $userfields[$fieldname])));
						} else {
							$worksheet->write($row, $col++, ilExcelUtils::_convert_text($userfields[$fieldname]));
						}
					}
				}
				$worksheet->write($row, $col++, ilExcelUtils::_convert_text($data->getParticipant($active_id)->getReached()));
				$worksheet->write($row, $col++, ilExcelUtils::_convert_text($data->getParticipant($active_id)->getMaxpoints()));
				$worksheet->write($row, $col++, ilExcelUtils::_convert_text($data->getParticipant($active_id)->getMark()));
				if ($this->test_obj->ects_output) {
					$worksheet->write($row, $col++, ilExcelUtils::_convert_text($data->getParticipant($active_id)->getECTSMark()));
				}
				$worksheet->write($row, $col++, ilExcelUtils::_convert_text($data->getParticipant($active_id)->getQuestionsWorkedThrough()));
				$worksheet->write($row, $col++, ilExcelUtils::_convert_text($data->getParticipant($active_id)->getNumberOfQuestions()));
				$worksheet->write($row, $col++, $data->getParticipant($active_id)->getQuestionsWorkedThroughInPercent() / 100.0, $format_percent);
				$time = $data->getParticipant($active_id)->getTimeOfWork();
				$time_seconds = $time;
				$time_hours = floor($time_seconds / 3600);
				$time_seconds -= $time_hours * 3600;
				$time_minutes = floor($time_seconds / 60);
				$time_seconds -= $time_minutes * 60;
				$worksheet->write($row, $col++, ilExcelUtils::_convert_text(sprintf("%02d:%02d:%02d", $time_hours, $time_minutes, $time_seconds)));
				$time = $data->getParticipant($active_id)->getQuestionsWorkedThrough() ? $data->getParticipant($active_id)->getTimeOfWork() / $data->getParticipant($active_id)->getQuestionsWorkedThrough() : 0;
				$time_seconds = $time;
				$time_hours = floor($time_seconds / 3600);
				$time_seconds -= $time_hours * 3600;
				$time_minutes = floor($time_seconds / 60);
				$time_seconds -= $time_minutes * 60;
				$worksheet->write($row, $col++, ilExcelUtils::_convert_text(sprintf("%02d:%02d:%02d", $time_hours, $time_minutes, $time_seconds)));
				$fv = getdate($data->getParticipant($active_id)->getFirstVisit());
				$firstvisit = ilUtil::excelTime(
					$fv["year"],
					$fv["mon"],
					$fv["mday"],
					$fv["hours"],
					$fv["minutes"],
					$fv["seconds"]
				);
				$worksheet->write($row, $col++, $firstvisit, $format_datetime);
				$lv = getdate($data->getParticipant($active_id)->getLastVisit());
				$lastvisit = ilUtil::excelTime(
					$lv["year"],
					$lv["mon"],
					$lv["mday"],
					$lv["hours"],
					$lv["minutes"],
					$lv["seconds"]
				);
				$worksheet->write($row, $col++, $lastvisit, $format_datetime);

				$median = $data->getStatistics()->getStatistics()->median();
				$pct = $data->getParticipant($active_id)->getMaxpoints() ? $median / $data->getParticipant($active_id)->getMaxpoints() * 100.0 : 0;
				$mark = $this->test_obj->mark_schema->getMatchingMark($pct);
				$mark_short_name = "";
				if (is_object($mark)) {
					$mark_short_name = $mark->getShortName();
				}
				$worksheet->write($row, $col++, ilExcelUtils::_convert_text($mark_short_name));
				$worksheet->write($row, $col++, ilExcelUtils::_convert_text($data->getStatistics()->getStatistics()->rank($data->getParticipant($active_id)->getReached())));
				$worksheet->write($row, $col++, ilExcelUtils::_convert_text($data->getStatistics()->getStatistics()->rank_median()));
				$worksheet->write($row, $col++, ilExcelUtils::_convert_text($data->getStatistics()->getStatistics()->count()));
				$worksheet->write($row, $col++, ilExcelUtils::_convert_text($median));
				if ($this->test_obj->getPassScoring() == SCORE_BEST_PASS) {
					$worksheet->write($row, $col++, $data->getParticipant($active_id)->getBestPass() + 1);
				} else {
					$worksheet->write($row, $col++, $data->getParticipant($active_id)->getLastPass() + 1);
				}
				$startcol = $col;
				for ($pass = 0; $pass <= $data->getParticipant($active_id)->getLastPass(); $pass++) {
					$col = $startcol;
					$finishdate = $this->test_obj->getPassFinishDate($active_id, $pass);
					if ($finishdate > 0) {
						if ($pass > 0) {
							$row++;
							if ($this->test_obj->isRandomTest() || $this->test_obj->getShuffleQuestions()) {
								$row++;
							}
						}
						$worksheet->write($row, $col++, ilExcelUtils::_convert_text($pass + 1));
						if (is_object($data->getParticipant($active_id)) && is_array($data->getParticipant($active_id)->getQuestions($pass))) {
							foreach ($data->getParticipant($active_id)->getQuestions($pass) as $question) {
								$question_data = $data->getParticipant($active_id)->getPass($pass)->getAnsweredQuestionByQuestionId($question["id"]);
								$worksheet->write($row, $col, ilExcelUtils::_convert_text($question_data["reached"]));
								if ($this->test_obj->isRandomTest() || $this->test_obj->getShuffleQuestions()) {
									$worksheet->write($row - 1, $col, ilExcelUtils::_convert_text(preg_replace("/<.*?>/", "", $data->getQuestionTitle($question["id"]))), $format_title);
								} else {
									if ($pass == 0 && !$firstrowwritten) {
										$worksheet->write(0, $col, ilExcelUtils::_convert_text(preg_replace("/<.*?>/", "", $data->getQuestionTitle($question["id"]))), $format_title);
									}
								}
								$col++;
							}
							$firstrowwritten = true;
						}
					}
				}
				$counter++;
			}
		}
		if ($this->test_obj->getExportSettingsSingleChoiceShort() && !$this->test_obj->isRandomTest() && $this->test_obj->hasSingleChoiceQuestions()) {
			// special tab for single choice tests
			$titles =& $this->test_obj->getQuestionTitlesAndIndexes();
			$positions = array();
			$pos = 0;
			$row = 0;
			foreach ($titles as $id => $title) {
				$positions[$id] = $pos;
				$pos++;
			}
			$usernames = array();
			$participantcount = count($data->getParticipants());
			$allusersheet = false;
			$pages = 0;
			$resultsheet =& $workbook->addWorksheet($this->lng->txt("eval_all_users"));

			$col = 0;
			$resultsheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt('name')), $format_title);
			$resultsheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt('login')), $format_title);
			if (count($additionalFields)) {
				foreach ($additionalFields as $fieldname) {
					if (strcmp($fieldname, "matriculation") == 0) {
						$resultsheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt('matriculation')), $format_title);
					}
				}
			}
			$resultsheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt('test')), $format_title);
			foreach ($titles as $title) {
				$resultsheet->write($row, $col++, ilExcelUtils::_convert_text($title), $format_title);
			}
			$row++;

			foreach ($data->getParticipants() as $active_id => $userdata) {
				$username = (!is_null($userdata) && ilExcelUtils::_convert_text($userdata->getName())) ? ilExcelUtils::_convert_text($userdata->getName()) : "ID $active_id";
				if (array_key_exists($username, $usernames)) {
					$usernames[$username]++;
					$username .= " ($i)";
				} else {
					$usernames[$username] = 1;
				}
				$col = 0;
				$resultsheet->write($row, $col++, $username);
				$resultsheet->write($row, $col++, $userdata->getLogin());
				if (count($additionalFields)) {
					$userfields = ilObjUser::_lookupFields($userdata->getUserID());
					foreach ($additionalFields as $fieldname) {
						if (strcmp($fieldname, "matriculation") == 0) {
							if (strlen($userfields[$fieldname])) {
								$resultsheet->write($row, $col++, ilExcelUtils::_convert_text($userfields[$fieldname]));
							} else {
								$col++;
							}
						}
					}
				}
				$resultsheet->write($row, $col++, ilExcelUtils::_convert_text($this->test_obj->getTitle()));
				$pass = $userdata->getScoredPass();
				if (is_object($userdata) && is_array($userdata->getQuestions($pass))) {
					foreach ($userdata->getQuestions($pass) as $question) {
						$objQuestion =& $this->test_obj->_instanciateQuestion($question["aid"]);
						if (is_object($objQuestion) && strcmp($objQuestion->getQuestionType(), 'assSingleChoice') == 0) {
							$solution = $objQuestion->getSolutionValues($active_id, $pass);
							$pos = $positions[$question["aid"]];
							$selectedanswer = "x";
							foreach ($objQuestion->getAnswers() as $id => $answer) {
								if (strlen($solution[0]["value1"]) && $id == $solution[0]["value1"]) {
									$selectedanswer = $answer->getAnswertext();
								}
							}
							$resultsheet->write($row, $col + $pos, ilExcelUtils::_convert_text($selectedanswer));
						}
					}
				}
				$row++;
			}
			if ($this->test_obj->isSingleChoiceTestWithoutShuffle()) {
				// special tab for single choice tests without shuffle option
				$pos = 0;
				$row = 0;
				$usernames = array();
				$allusersheet = false;
				$pages = 0;
				$resultsheet =& $workbook->addWorksheet($this->lng->txt("eval_all_users") . " (2)");

				$col = 0;
				$resultsheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt('name')), $format_title);
				$resultsheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt('login')), $format_title);
				if (count($additionalFields)) {
					foreach ($additionalFields as $fieldname) {
						if (strcmp($fieldname, "matriculation") == 0) {
							$resultsheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt('matriculation')), $format_title);
						}
					}
				}
				$resultsheet->write($row, $col++, ilExcelUtils::_convert_text($this->lng->txt('test')), $format_title);
				foreach ($titles as $title) {
					$resultsheet->write($row, $col++, ilExcelUtils::_convert_text($title), $format_title);
				}
				$row++;

				foreach ($data->getParticipants() as $active_id => $userdata) {
					$username = (!is_null($userdata) && ilExcelUtils::_convert_text($userdata->getName())) ? ilExcelUtils::_convert_text($userdata->getName()) : "ID $active_id";
					if (array_key_exists($username, $usernames)) {
						$usernames[$username]++;
						$username .= " ($i)";
					} else {
						$usernames[$username] = 1;
					}
					$col = 0;
					$resultsheet->write($row, $col++, $username);
					$resultsheet->write($row, $col++, $userdata->getLogin());
					if (count($additionalFields)) {
						$userfields = ilObjUser::_lookupFields($userdata->getUserID());
						foreach ($additionalFields as $fieldname) {
							if (strcmp($fieldname, "matriculation") == 0) {
								if (strlen($userfields[$fieldname])) {
									$resultsheet->write($row, $col++, ilExcelUtils::_convert_text($userfields[$fieldname]));
								} else {
									$col++;
								}
							}
						}
					}
					$resultsheet->write($row, $col++, ilExcelUtils::_convert_text($this->test_obj->getTitle()));
					$pass = $userdata->getScoredPass();
					if (is_object($userdata) && is_array($userdata->getQuestions($pass))) {
						foreach ($userdata->getQuestions($pass) as $question) {
							$objQuestion =& $this->test_obj->_instanciateQuestion($question["aid"]);
							if (is_object($objQuestion) && strcmp($objQuestion->getQuestionType(), 'assSingleChoice') == 0) {
								$solution = $objQuestion->getSolutionValues($active_id, $pass);
								$pos = $positions[$question["aid"]];
								$selectedanswer = chr(65 + $solution[0]["value1"]);
								$resultsheet->write($row, $col + $pos, ilExcelUtils::_convert_text($selectedanswer));
							}
						}
					}
					$row++;
				}
			}
		} else {
			// test participant result export
			$usernames = array();
			$participantcount = count($data->getParticipants());
			$allusersheet = false;
			$pages = 0;
			$i = 0;
			foreach ($data->getParticipants() as $active_id => $userdata) {
				$i++;

				$username = (!is_null($userdata) && ilExcelUtils::_convert_text($userdata->getName())) ? ilExcelUtils::_convert_text($userdata->getName()) : "ID $active_id";
				if (array_key_exists($username, $usernames)) {
					$usernames[$username]++;
					$username .= " ($i)";
				} else {
					$usernames[$username] = 1;
				}
				if ($participantcount > 250) {
					if (!$allusersheet || ($pages - 1) < floor($row / 64000)) {
						$resultsheet =& $workbook->addWorksheet($this->lng->txt("eval_all_users") . (($pages > 0) ? " (" . ($pages + 1) . ")" : ""));
						$allusersheet = true;
						$row = 0;
						$pages++;
					}
				} else {
					$resultsheet =& $workbook->addWorksheet($username);
				}
				if (method_exists($resultsheet, "writeString")) {
					$pass = $userdata->getScoredPass();
					$row = ($allusersheet) ? $row : 0;
					$resultsheet->writeString($row, 0, ilExcelUtils::_convert_text(sprintf($this->lng->txt("tst_result_user_name_pass"), $pass + 1, $userdata->getName())), $format_bold);
					$row += 2;
					if (is_object($userdata) && is_array($userdata->getQuestions($pass))) {
						foreach ($userdata->getQuestions($pass) as $question) {
							require_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
							$question = assQuestion::_instanciateQuestion($question["id"]);
							if (is_object($question)) {
								$row = $question->setExportDetailsXLS($resultsheet, $row, $active_id, $pass, $format_title, $format_bold);
							}
						}
					}
				}
			}
		}
		$workbook->close();
		if ($deliver) {
			ilUtil::deliverFile($excelfile, $testname, "application/vnd.ms-excel", false, true);
			exit;
		} else {
			return $excelfile;
		}
	}

} 