<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question MoodleXML Export
 * This class provides an XML compatible with Moodle for STACK questions created in ILIAS.
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: 2.3$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionMoodleXMLExport
{

	private $stack_questions;

	function __construct($stack_questions)
	{
		global $DIC;

		$lng = $DIC->language();
		if (is_array($stack_questions) AND sizeof($stack_questions)) {
			$this->setStackQuestions($stack_questions);
		} else {
			throw new stack_exception($lng->txt('qpl_qst_xqcas_moodlexml_no_questions_selected'));
		}
	}


	/**
	 * @param mixed $stack_questions
	 */
	public function setStackQuestions($stack_questions)
	{
		$this->stack_questions = $stack_questions;
	}

	/**
	 * @return mixed
	 */
	public function getStackQuestions()
	{
		return $this->stack_questions;
	}


	function toMoodleXML()
	{
		global $ilias;

		include_once("./Services/Xml/classes/class.ilXmlWriter.php");
		$a_xml_writer = new ilXmlWriter;


		// set xml header
		$a_xml_writer->xmlHeader();
		$a_xml_writer->xmlStartTag("quiz");
		foreach ($this->getStackQuestions() as $question_id => $question) {
			$a_xml_writer->xmlComment(" question: " . $question_id . " ");

			$a_xml_writer->xmlStartTag("question", array("type" => "stack"));
			//QUESTION

			//Question Title
			$a_xml_writer->xmlStartTag("name");
			$a_xml_writer->xmlElement("text", NULL, $question->getTitle());
			$a_xml_writer->xmlEndTag("name");

			//Question Text
			$a_xml_writer->xmlStartTag("questiontext", array("format" => "html"));
			$media = $this->getRTEMedia($question->getQuestion(), $question);
			$this->addRTEText($a_xml_writer, $question->getQuestion());
			$this->addRTEMedia($a_xml_writer, $media);
			$a_xml_writer->xmlEndTag("questiontext");

			//General feedback
			$a_xml_writer->xmlStartTag("generalfeedback", array("format" => "html"));
			$media = $this->getRTEMedia($question->getExtraInfo()->getHowToSolve());
			$this->addRTEText($a_xml_writer, $question->getExtraInfo()->getHowToSolve());
			$this->addRTEMedia($a_xml_writer, $media);
			$a_xml_writer->xmlEndTag("generalfeedback");

			//Grade and penalty
			$a_xml_writer->xmlElement("defaultgrade", NULL, $question->getPoints());
			if ($question->getExtraInfo()->getPenalty()) {
				$a_xml_writer->xmlElement("penalty", NULL, $question->getExtraInfo()->getPenalty());
			} else {
				$a_xml_writer->xmlElement("penalty", NULL, "0");
			}

			if ($question->getExtraInfo()->getPenalty()) {
				$a_xml_writer->xmlElement("hidden", NULL, $question->getExtraInfo()->getHidden());
			} else {
				$a_xml_writer->xmlElement("hidden", NULL, "0");
			}

			//Options
			$a_xml_writer->xmlStartTag("questionvariables");
			$a_xml_writer->xmlElement("text", NULL, $question->getOptions()->getQuestionVariables());
			$a_xml_writer->xmlEndTag("questionvariables");

			$a_xml_writer->xmlStartTag("specificfeedback", array("format" => "html"));
			$media = $this->getRTEMedia($question->getOptions()->getSpecificFeedback());
			$this->addRTEText($a_xml_writer, $question->getOptions()->getSpecificFeedback());
			$this->addRTEMedia($a_xml_writer, $media);
			$a_xml_writer->xmlEndTag("specificfeedback");

			$a_xml_writer->xmlStartTag("questionnote", array("format" => "html"));
			$a_xml_writer->xmlElement("text", NULL, $question->getOptions()->getQuestionNote());
			$a_xml_writer->xmlEndTag("questionnote");

			$a_xml_writer->xmlElement("questionsimplify", NULL, (int)$question->getOptions()->getQuestionSimplify());

			$a_xml_writer->xmlElement("assumepositive", NULL, (int)$question->getOptions()->getAssumePositive());

			$a_xml_writer->xmlStartTag("prtcorrect", array("format" => "html"));
			$media = $this->getRTEMedia($question->getOptions()->getPRTCorrect());
			$this->addRTEText($a_xml_writer, $question->getOptions()->getPRTCorrect());
			$this->addRTEMedia($a_xml_writer, $media);
			$a_xml_writer->xmlEndTag("prtcorrect");

			$a_xml_writer->xmlStartTag("prtpartiallycorrect", array("format" => "html"));
			$media = $this->getRTEMedia($question->getOptions()->getPRTPartiallyCorrect());
			$this->addRTEText($a_xml_writer, $question->getOptions()->getPRTPartiallyCorrect());
			$this->addRTEMedia($a_xml_writer, $media);
			$a_xml_writer->xmlEndTag("prtpartiallycorrect");

			$a_xml_writer->xmlStartTag("prtincorrect", array("format" => "html"));
			$media = $this->getRTEMedia($question->getOptions()->getPRTIncorrect());
			$this->addRTEText($a_xml_writer, $question->getOptions()->getPRTIncorrect());
			$this->addRTEMedia($a_xml_writer, $media);
			$a_xml_writer->xmlEndTag("prtincorrect");

			$a_xml_writer->xmlElement("multiplicationsign", NULL, $question->getOptions()->getMultiplicationSign());

			$a_xml_writer->xmlElement("sqrtsign", NULL, $question->getOptions()->getSqrtSign());

			$a_xml_writer->xmlElement("complexno", NULL, $question->getOptions()->getComplexNumbers());

			$a_xml_writer->xmlElement("inversetrig", NULL, $question->getOptions()->getInverseTrig());

			$a_xml_writer->xmlElement("variantsselectionseed", NULL, $question->getOptions()->getVariantsSelectionSeeds());

			//Inputs
			if (sizeof($question->getInputs())) {
				foreach ($question->getInputs() as $input) {
					$a_xml_writer->xmlStartTag("input");

					$a_xml_writer->xmlElement("name", NULL, $input->getInputName());
					$a_xml_writer->xmlElement("type", NULL, $input->getInputType());
					$a_xml_writer->xmlElement("tans", NULL, $input->getTeacherAnswer());
					$a_xml_writer->xmlElement("boxsize", NULL, $input->getBoxSize());
					$a_xml_writer->xmlElement("strictsyntax", NULL, (int)$input->getStrictSyntax());
					$a_xml_writer->xmlElement("insertstars", NULL, (int)$input->getInsertStars());
					$a_xml_writer->xmlElement("syntaxhint", NULL, $input->getSyntaxHint());
					$a_xml_writer->xmlElement("forbidwords", NULL, $input->getForbidWords());
					$a_xml_writer->xmlElement("allowwords", NULL, $input->getAllowWords());
					$a_xml_writer->xmlElement("forbidfloat", NULL, (int)$input->getForbidFloat());
					$a_xml_writer->xmlElement("requirelowestterms", NULL, (int)$input->getRequireLowestTerms());
					$a_xml_writer->xmlElement("checkanswertype", NULL, (int)$input->getCheckAnswerType());
					$a_xml_writer->xmlElement("mustverify", NULL, (int)$input->getMustVerify());
					$a_xml_writer->xmlElement("showvalidation", NULL, (int)$input->getShowValidation());
					$a_xml_writer->xmlElement("options", NULL, $input->getOptions());

					$a_xml_writer->xmlEndTag("input");
				}
			}

			//PRT
			if (sizeof($question->getPotentialResponsesTrees())) {
				foreach ($question->getPotentialResponsesTrees() as $prt) {
					$a_xml_writer->xmlStartTag("prt");

					$a_xml_writer->xmlElement("name", NULL, $prt->getPRTName());
					$a_xml_writer->xmlElement("value", NULL, $prt->getPRTValue());
					$a_xml_writer->xmlElement("autosimplify", NULL, $prt->getAutoSimplify());

					$a_xml_writer->xmlStartTag("feedbackvariables", array("format" => "html"));
					$a_xml_writer->xmlElement("text", NULL, $prt->getPRTFeedbackVariables());
					$a_xml_writer->xmlEndTag("feedbackvariables");

					//Nodes
					if (sizeof($prt->getPRTNodes())) {
						foreach ($prt->getPRTNodes() as $node) {
							$a_xml_writer->xmlStartTag("node");

							$a_xml_writer->xmlElement("name", NULL, $node->getNodeName());
							$a_xml_writer->xmlElement("answertest", NULL, $node->getAnswerTest());
							$a_xml_writer->xmlElement("sans", NULL, $node->getStudentAnswer());
							$a_xml_writer->xmlElement("tans", NULL, $node->getTeacherAnswer());
							$a_xml_writer->xmlElement("testoptions", NULL, $node->getTestOptions());
							$a_xml_writer->xmlElement("quiet", NULL, $node->getQuiet());

							$a_xml_writer->xmlElement("truescoremode", NULL, $node->getTrueScoreMode());
							$a_xml_writer->xmlElement("truescore", NULL, $node->getTrueScore());
							$a_xml_writer->xmlElement("truepenalty", NULL, $node->getTruePenalty());
							$a_xml_writer->xmlElement("truenextnode", NULL, $node->getTrueNextNode());
							$a_xml_writer->xmlElement("trueanswernote", NULL, $node->getTrueAnswerNote());

							$a_xml_writer->xmlStartTag("truefeedback", array("format" => "html"));
							$media = $this->getRTEMedia($node->getTrueFeedback());
							$this->addRTEText($a_xml_writer, $node->getTrueFeedback());
							$this->addRTEMedia($a_xml_writer, $media);
							$a_xml_writer->xmlEndTag("truefeedback");

							$a_xml_writer->xmlElement("falsescoremode", NULL, $node->getFalseScoreMode());
							$a_xml_writer->xmlElement("falsescore", NULL, $node->getFalseScore());
							$a_xml_writer->xmlElement("falsepenalty", NULL, $node->getFalsePenalty());
							$a_xml_writer->xmlElement("falsenextnode", NULL, $node->getFalseNextNode());
							$a_xml_writer->xmlElement("falseanswernote", NULL, $node->getFalseAnswerNote());

							$a_xml_writer->xmlStartTag("falsefeedback", array("format" => "html"));
							$media = $this->getRTEMedia($node->getFalseFeedback());
							$this->addRTEText($a_xml_writer, $node->getFalseFeedback());
							$this->addRTEMedia($a_xml_writer, $media);
							$a_xml_writer->xmlEndTag("falsefeedback");

							$a_xml_writer->xmlEndTag("node");
						}
					}
					$a_xml_writer->xmlEndTag("prt");
				}
			}

			//deployed seeds
			if (sizeof($question->getDeployedSeeds())) {
				foreach ($question->getDeployedSeeds() as $seed) {
					$a_xml_writer->xmlElement("deployedseed", NULL, $seed->getSeed());
				}
			}

			//tests
			if (sizeof($question->getTests())) {
				foreach ($question->getTests() as $test) {
					$a_xml_writer->xmlStartTag("qtest");
					$a_xml_writer->xmlElement("testcase", NULL, $test->getTestCase());
					//test input
					foreach ($test->getTestInputs() as $test_input) {
						$a_xml_writer->xmlStartTag("testinput");
						$a_xml_writer->xmlElement("name", NULL, $test_input->getTestInputName());
						$a_xml_writer->xmlElement("value", NULL, $test_input->getTestInputValue());
						$a_xml_writer->xmlEndTag("testinput");
					}
					//test expected
					foreach ($test->getTestExpected() as $test_input) {
						$a_xml_writer->xmlStartTag("expected");
						$a_xml_writer->xmlElement("name", NULL, $test_input->getTestPRTName());
						$a_xml_writer->xmlElement("expectedscore", NULL, $test_input->getExpectedScore());
						$a_xml_writer->xmlElement("expectedpenalty", NULL, $test_input->getExpectedPenalty());
						$a_xml_writer->xmlElement("expectedanswernote", NULL, $test_input->getExpectedAnswerNote());
						$a_xml_writer->xmlEndTag("expected");
					}
					$a_xml_writer->xmlEndTag("qtest");
				}
			}
			$a_xml_writer->xmlEndTag("question");
		}
		$a_xml_writer->xmlEndTag("quiz");
		$xml = $a_xml_writer->xmlDumpMem(FALSE);

		if (sizeof($this->getStackQuestions()) > 1) {
			ilUtil::deliverData($xml, "stack_question_" . $question_id . "_and_others.xml", "xml");
		} elseif (sizeof($this->getStackQuestions()) == 1) {
			ilUtil::deliverData($xml, "stack_question_" . $question_id . ".xml", "xml");
		}

		return $xml;
	}

	/**
	 * Get the media files used in an RTE text
	 * @param 	string		text to analyze
	 * @param 	assStackQuestion question
	 * @return	array		name => file content
	 */
	private function getRTEMedia($a_text, $stack_question = "")
	{
		$media = array();
		$matches = array();
		preg_match_all('/src=".*\/mobs\/mm_([0-9]+)\/([^"]+)"/', $a_text, $matches);

		for ($i = 0; $i < count($matches[0]); $i++)
		{
			$id = $matches[1][$i];
			$name = $matches[2][$i];

			$new_match =explode('?',$name);

			if (is_file(ilUtil::getWebspaceDir()."/mobs/mm_".$id.'/'.$new_match[0]))
			{
				$media[$new_match[0]] = file_get_contents(ilUtil::getWebspaceDir()."/mobs/mm_".$id.'/'.$new_match[0]);
			}
		}
		return $media;
	}

	/**
	 * Add an RTE text
	 * This will change the media references and wrap the text in CDATA
	 * @param 	ilXmlWriter	XML writer
	 * @param 	string		text to add
	 * @param	string		tag for the element
	 * @param	array		attributes
	 */
	private function addRTEText($a_xml_writer, $a_text, $a_tag = 'text', $a_attr = null)
	{
		$text = preg_replace(
			'/src=".*\/mobs\/mm_([0-9]+)\/([^"]+)"/', 'src="@@PLUGINFILE@@/$2"', $a_text);

		$text =  '<![CDATA[' . $text . ']]>';

		$a_xml_writer->xmlElement($a_tag, NULL, $text, false, false);
	}


	/**
	 * Add media files as <file> elements
	 * @param 	ilXmlWriter		XML writer
	 * @param 	array			name => content
	 * @param	string			tag for the element
	 */
	private function addRTEMedia($a_xml_writer, $a_media,  $a_tag = 'file')
	{
		foreach ($a_media as $name => $content)
		{
			$attr = array (
				'name' => $name,
				'path' => '/',
				'encoding' => 'base64'
			);
			$a_xml_writer->xmlElement('file', $attr, base64_encode($content), false, false);
		}
	}
} 