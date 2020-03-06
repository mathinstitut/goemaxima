<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';
require_once './Services/MediaObjects/classes/class.ilObjMediaObject.php';


/**
 * STACK Question IMPORT OF QUESTIONS from a MOODLEXML file
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: 2.3$$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionMoodleImport
{
	/**
	 * Plugin instance for language management
	 * @var ilassStackQuestionPlugin
	 */
	private $plugin;

	/**
	 * ILIAS version of the question
	 * The current question
	 * @var assStackQuestion
	 */
	private $question;

	/**
	 * Question_id for the first question to import
	 * (When only one question, use this as Question_Id)
	 * @var int When first question this var is higher than 0.
	 */
	private $first_question;

	private $error_log;

	/**
	 * @var string    allowed html tags, e.g. "<em><strong>..."
	 */
	private $rte_tags = "";


	/**
	 * media objects created for an imported question
	 * This list will be cleared for every new question
	 * @var array    id => object
	 */
	private $media_objects = array();


	/**
	 * Set all the parameters for this question, including the creation of
	 * the first assStackQuestion object.
	 * @param ilassStackQuestionPlugin $plugin
	 * @param $first_question_id int the question_id for the first question to import.
	 */
	function __construct($plugin, $first_question_id, $parent_obj)
	{
		//Set Plugin and first question id.
		$this->setPlugin($plugin);
		$this->setFirstQuestion($first_question_id);

		//Creation of the first question.
		$this->getPlugin()->includeClass('class.assStackQuestion.php');
		$this->setQuestion($parent_obj);

		//Initialization and load of stack wrapper classes
		$this->getPlugin()->includeClass('utils/class.assStackQuestionInitialization.php');
	}

	/**
	 * ### MAIN METHOD OF THIS CLASS ###
	 * This method is called from assStackQuestion to import the questions from an MoodleXML file.
	 * @param $xml_file string the MoodleXML file
	 * @return mixed Status of the import
	 */
	public function import($xml_file)
	{
		//Step 1: Get data from XML.
		$raw_data = $this->getDataFromXML($xml_file);
		//Step 2: Import questions.
		$import_status = $this->importQuestions($raw_data);

		//Step 3: Return status.
		return $import_status;
	}

	public function importQuestions($raw_data)
	{
		$number_of_questions_created = 0;
		//For each question in the array
		foreach ($raw_data['question'] as $data)
		{
			// start with a new list of media objects for each question
			$this->clearMediaObjects();

			//Check for not category
			if (is_array($data['category']))
			{
				continue;
			}

			//Step 2.1: Create standard question
			$ok = $this->createStandardQuestion($data);
			if ($ok === FALSE)
			{
				//Do not continue creating question
				$this->purgeMediaObjects();
				continue;
			}

			//Step 2.2: Get Options from data and set as Options OBJ in cas_question
			$question_options = $this->getOptionsFromXML($data);
			$this->getQuestion()->setOptions($question_options);

			//Step 2.3: Get Inputs from data and set as Input OBJ in cas_question
			$ok = $question_inputs = $this->getInputsFromXML($data['input']);
			$this->getQuestion()->setInputs($question_inputs);
			if ($ok === FALSE)
			{
				//Delete current question data
				$this->getQuestion()->delete($this->getQuestion()->getId());
				$this->purgeMediaObjects();
				//Do not continue creating question
				continue;
			}

			//Step 2.4.A: Get PRT from data and set as PRT OBJ in cas_question
			//Step 2.4.B: Get Nodes from each PRT and set as Node OBJ in each PRT.
			$ok = $question_PRTs = $this->getPRTsFromXML($data['prt']);
			$this->getQuestion()->setPotentialResponsesTrees($question_PRTs);
			if ($ok === FALSE)
			{
				//Delete current question data
				$this->getQuestion()->delete($this->getQuestion()->getId());
				$this->purgeMediaObjects();
				//Do not continue creating question
				continue;
			}

			//Step 2.5.A: Get Test from from data and set as Test OBJ in cas_question
			//Step 2.5.B: Get Test Inputs and Expected from data and set as TestInput/Expected OBJ in cas_question
			if (isset($data['qtest']))
			{
				$question_tests = $this->getTestsFromXML($data['qtest']);
				$this->getQuestion()->setTests($question_tests);
			} else
			{
				$this->getQuestion()->setTests(array());
			}

			//Step 2.6: Get deployed seeds
			if (isset($data['deployedseed']))
			{
				$question_seeds = $this->getDeployedSeedsFromXML($data['deployedseed']);
				$this->getQuestion()->setDeployedSeeds($question_seeds);
			} else
			{
				$this->getQuestion()->setDeployedSeeds(array());
			}

			//Step 2.7: Get extra fields
			$extra_info = $this->getExtraInfoFromXML($data);
			$this->getQuestion()->setExtraInfo($extra_info);

			//Step 2.8: Fix possible errors
			$question_is_ok = $this->checkQuestion($this->getQuestion());

			//Step 2.9: Insert into DB or delete if question is not OK
			if ($question_is_ok)
			{
				//Delete options from created question.
				$this->deletePredefinedQuestionData($this->getQuestion()->getId());

				//Save STACK Question data.
				$this->getQuestion()->saveToDB($this->getQuestion()->getId(), TRUE);
				$this->saveMediaObjectUsages($this->getQuestion()->getId());
				$number_of_questions_created++;

				//Set $question to a new question
				$this->getQuestion()->setId(-1);
			} else
			{
				//Delete current question data
				$this->getQuestion()->delete($this->getQuestion()->getId());
				$this->purgeMediaObjects();
			}
		}
		if (sizeof($this->error_log))
		{
			ilUtil::sendFailure(implode('</br>', $this->error_log));
		}

		//Number of questions created info
		if ($number_of_questions_created > 1)
		{
			ilUtil::sendSuccess($number_of_questions_created . ' ' . $this->getPlugin()->txt('import_number_of_questions_created'));
		} elseif ($number_of_questions_created)
		{
			ilUtil::sendSuccess($number_of_questions_created . ' ' . $this->getPlugin()->txt('import_number_of_questions_created_1'));
		}
	}

	public function deletePredefinedQuestionData($question_id)
	{
		global $DIC;
		$db = $DIC->database();

		$query = 'DELETE FROM xqcas_options WHERE question_id = ' . $question_id;
		$db->manipulate($query);

		$query = 'DELETE FROM xqcas_inputs WHERE question_id = ' . $question_id;
		$db->manipulate($query);
	}
	/*
	 * PARSER
	 */

	/**
	 *
	 * @param string $xml_file
	 * @return array
	 */
	private function getDataFromXML($xml_file)
	{
		$xml = simplexml_load_file($xml_file);
		$raw_array = $this->xml2array($xml);

		return $raw_array;
	}

	/**
	 * XML Parser
	 * @param SimpleXMLElement $xml
	 * @return array
	 */
	private function xml2array($xml)
	{
		$arr = array();
		foreach ($xml as $element)
		{
			$tag = $element->getName();
			$e = get_object_vars($element);
			//Deployed seed bug fixing
			if ($element->getName() == "deployedseed")
			{
				$arr[$tag][] = strip_tags((string)$element);
			} else
			{
				if (!empty($e))
				{
					if ($element instanceof SimpleXMLElement)
					{
						$elem_arr = $this->xml2array($element);
						if (empty($elem_arr))
						{
							$elem_arr['_content'] = (string)$element;
						}
						foreach ($element->attributes() as $name => $value)
						{
							$elem_arr['_attributes'][$name] = (string)$value;
						}
						$arr[$tag][] = $elem_arr;
					} else
					{
						$arr[$tag][] = (string)$e;
					}
				} else
				{
					$arr[$tag] = (string)$element;
				}
			}
		}

		return $arr;
	}

	private function cleanXML($xml_data)
	{
		foreach ($xml_data as $array)
		{
			if (sizeof($array) > 1)
			{
				return $array;
			}
		}
	}

	/*
	 * BUSTACK QuestionTION FUNCTIONS
	 */

	/**
	 * Stablish the data in order to create an Standard Question
	 * @param array $data
	 */
	private function createStandardQuestion($data)
	{
		//Question id management.
		if ($this->getQuestion()->getId() == $this->getFirstQuestion())
		{
			//Nothing to do, first question
		} else
		{
			$this->getQuestion()->setId(-1);
		}

		if (!isset($data['name'][0]['text']) OR $data['name'][0]['text'] == '')
		{
			$this->error_log[] = $this->getPlugin()->txt('error_import_no_title');

			return FALSE;
		}

		if (!isset($data['questiontext'][0]['text']) OR $data['questiontext'][0]['text'] == '')
		{
			$this->error_log[] = $this->getPlugin()->txt('error_import_no_question_text') . ' ' . $data['name'][0]['text'];

			return FALSE;
		}

		if (!isset($data['defaultgrade']) OR $data['defaultgrade'] == '')
		{
			$this->error_log[] = $this->getPlugin()->txt('error_import_no_points') . ' ' . $data['name'][0]['text'];

			return FALSE;
		}

		$mapping = $this->getMediaObjectsFromXML($data['questiontext'][0]['file']);
		$questiontext = $this->replaceMediaObjectReferences($data['questiontext'][0]['text'], $mapping);

		//Other parameters settings.
		$this->getQuestion()->setTitle(strip_tags($data['name'][0]['text']));
		$this->getQuestion()->setQuestion(assStackQuestionUtils::_casTextConverter($questiontext, $this->getQuestion()->getTitle(), TRUE), $this->getQuestion()->getTitle(), true, $this->getRTETags());
		$this->getQuestion()->setPoints($data['defaultgrade']);

		//Save standard data.
		$this->getQuestion()->saveQuestionDataToDb();
	}

	private function checkQuestionType($data)
	{
		$has_name = array_key_exists('name', $data);
		$has_question_variables = array_key_exists('questionvariables', $data);
		$has_inputs = array_key_exists('input', $data);
		$has_prts = array_key_exists('prt', $data);

		if ($has_name AND $has_question_variables AND $has_inputs AND $has_prts)
		{
			return TRUE;
		} else
		{
			ilUtil::sendInfo($this->cas_question->getPlugin()->txt('error_importing_question_malformed'));

			return FALSE;
		}
	}

	/**
	 * Get Options from XML
	 * NOTICE:
	 * * Formats are set always as 1 (1 means HTML)
	 * * Due to lack of inverse_trig parameteter in MoodleXML files inverse_trig is always set as cos-1.
	 * * SOLVED if field doesn't exist, set to cos-1
	 * @param array $data
	 */
	private function getOptionsFromXML($data)
	{
		$this->getPlugin()->includeClass('model/ilias_object/class.assStackQuestionOptions.php');
		$question_options = new assStackQuestionOptions(-1, $this->getQuestion()->getId());

		//Question Variables
		if (isset($data['questionvariables'][0]['text']))
		{
			$question_options->setQuestionVariables($data['questionvariables'][0]['text']);
		}

		//Specific feedback
		$mapping = $this->getMediaObjectsFromXML($data['specificfeedback'][0]['file']);
		$specificfeedback = assStackQuestionUtils::_casTextConverter($this->replaceMediaObjectReferences($data['specificfeedback'][0]['text'], $mapping), $this->getQuestion()->getTitle(), TRUE);
		$question_options->setSpecificFeedback(ilUtil::secureString($specificfeedback, true, $this->getRTETags()));
		$question_options->setSpecificFeedbackFormat(1);

		//Question note:
		$question_note = assStackQuestionUtils::_casTextConverter($data['questionnote'][0]['text'], $this->getQuestion()->getTitle(), TRUE);
		$question_options->setQuestionNote($question_note);

		//Question simplify? Assume possitive?
		$question_options->setQuestionSimplify((int)$data['questionsimplify']);
		$question_options->setAssumePositive((int)$data['assumepositive']);

		//PRT Messages
		$question_options->setPRTCorrectFormat(1);
		$question_options->setPRTPartiallyCorrectFormat(1);
		$question_options->setPRTIncorrectFormat(1);

		$mapping = $this->getMediaObjectsFromXML($data['prtcorrect'][0]['file']);
		$prtcorrect = $this->replaceMediaObjectReferences($data['prtcorrect'][0]['text'], $mapping);
		$question_options->setPRTCorrect(ilUtil::secureString($prtcorrect, true, $this->getRTETags()));

		$mapping = $this->getMediaObjectsFromXML($data['prtpartiallycorrect'][0]['file']);
		$prtpartiallycorrect = $this->replaceMediaObjectReferences($data['prtpartiallycorrect'][0]['text'], $mapping);
		$question_options->setPRTPartiallyCorrect(ilUtil::secureString($prtpartiallycorrect, true, $this->getRTETags()));

		$mapping = $this->getMediaObjectsFromXML($data['prtincorrect'][0]['file']);
		$prtincorrect = $this->replaceMediaObjectReferences($data['prtincorrect'][0]['text'], $mapping);
		$question_options->setPRTIncorrect(ilUtil::secureString($prtincorrect, true, $this->getRTETags()));

		//Multiplication, SQRT, Complex No, Variants seeds
		$question_options->setMultiplicationSign(strip_tags($data['multiplicationsign']));
		$question_options->setSqrtSign(strip_tags($data['sqrtsign']));
		$question_options->setComplexNumbers(strip_tags($data['complexno']));
		$question_options->setInverseTrig(isset($data['inversetrig']) ? strip_tags($data['inversetrig']) : 'cos-1');
		$question_options->setMatrixParens(isset($data['matrixparens']) ? strip_tags($data['matrixparens']) : '[');
		$question_options->setVariantsSelectionSeeds(strip_tags($data['variantsselectionseed']));

		return $question_options;
	}

	/**
	 * Get Inputs from XML and returns an array with them.
	 * NOTICE:
	 * * Options are setted as ""
	 * @param array $data
	 * @return \assStackQuestionInput
	 */
	private function getInputsFromXML($data)
	{
		$this->getPlugin()->includeClass('model/ilias_object/class.assStackQuestionInput.php');

		$inputs = array();
		if (is_array($data))
		{
			foreach ($data as $input)
			{

				//Main attributes needed to create an InputOBJ
				$input_name = strip_tags($input['name']);
				$input_type = strip_tags($input['type']);
				$input_teacher_answer = strip_tags($input['tans']);
				$new_input = new assStackQuestionInput(-1, $this->getQuestion()->getId(), $input_name, $input_type, $input_teacher_answer);

				//Setting the rest of the attributes
				$new_input->setBoxSize((int)$input['boxsize']);
				$new_input->setStrictSyntax((int)$input['strictsyntax']);
				$new_input->setInsertStars((int)$input['insertstars']);
				$new_input->setSyntaxHint(strip_tags($input['syntaxhint']));
				$new_input->setForbidWords(strip_tags($input['forbidwords']));
				$new_input->setAllowWords(strip_tags($input['allowwords']));
				$new_input->setForbidFloat((int)$input['forbidfloat']);
				$new_input->setRequireLowestTerms((int)$input['requirelowestterms']);
				$new_input->setCheckAnswerType((int)$input['checkanswertype']);
				$new_input->setMustVerify((int)$input['mustverify']);
				$new_input->setShowValidation((int)$input['showvalidation']);
				$new_input->setOptions(strip_tags($input['options']));

				$inputs[$input_name] = $new_input;
			}
		} else
		{
			$this->error_log[] = $this->getPlugin()->txt('error_import_no_inputs') . ' ' . $this->getQuestion()->getTitle();

			return FALSE;
		}

		if (!is_array($inputs))
		{
			$this->error_log[] = $this->getPlugin()->txt('error_import_no_inputs') . ' ' . $this->getQuestion()->getTitle();

			return FALSE;
		}

		//array of assStackQuestionInputs
		return $inputs;
	}

	/**
	 * Get PRTs from XML and returns an array with them
	 * NOTICE:
	 * * This method calls getPRTNodesFromXML
	 * * prt_nodes[first] should be unset after prt->first_node is set.
	 * @param array $data
	 * @return \assStackQuestionPRT
	 */
	private function getPRTsFromXML($data)
	{
		$this->getPlugin()->includeClass('model/ilias_object/class.assStackQuestionPRT.php');
		$prts = array();

		if (is_array($data))
		{
			foreach ($data as $prt)
			{
				//Creation of the PRT
				$new_prt = new assStackQuestionPRT(-1, $this->getQuestion()->getId());
				$prt_name = strip_tags($prt['name']);
				$new_prt->setPRTName($prt_name);
				$new_prt->setPRTValue(strip_tags($prt['value']));
				$new_prt->setAutoSimplify(strip_tags($prt['autosimplify']));
				$new_prt->setPRTFeedbackVariables($prt['feedbackvariables'][0]['text']);

				//Creation of Nodes
				$prt_nodes = $this->getPRTNodesFromXML($prt['node'], $new_prt->getPRTName());

				//Set the first node and later unset from array
				$new_prt->setFirstNodeName($prt_nodes['first']);

				unset($prt_nodes['first']);
				$new_prt->setPRTNodes($prt_nodes);

				$prts[] = $new_prt;
			}

		} else
		{
			$this->error_log[] = $this->getPlugin()->txt('error_import_no_prt') . ' ' . $this->getQuestion()->getTitle();

			return FALSE;
		}

		if (!is_array($prts))
		{
			$this->error_log[] = $this->getPlugin()->txt('error_import_no_prt') . ' ' . $this->getQuestion()->getTitle();

			return FALSE;
		}

		//array of assStackQuestionPRT
		return $prts;
	}

	/**
	 * Get PRTNodes from XML and returns an array with them and also with the first node of the PRT
	 * NOTICE:
	 * * Feedback format is set as 1 (Possible error in STACK because in description of DB
	 * * this field will be used for store the format of the feedback but is an int)
	 * @param array $data
	 * @param string $prt_name
	 * @return \assStackQuestionPRTNode
	 */
	private function getPRTNodesFromXML($data, $prt_name)
	{
		$this->getPlugin()->includeClass('model/ilias_object/class.assStackQuestionPRTNode.php');
		$prt_nodes = array();

		//First node var
		$is_first_node = true;
		foreach ($data as $prt_node)
		{
			//Main attributes for creating the PRTNode OBJ
			$node_name = strip_tags($prt_node['name']);

			$true_next_node = strip_tags($prt_node['truenextnode']);
			$false_next_node = strip_tags($prt_node['falsenextnode']);
			$new_node = new assStackQuestionPRTNode(-1, $this->getQuestion()->getId(), $prt_name, $node_name, $true_next_node, $false_next_node);

			//Setting Answers
			$new_node->setAnswerTest(strip_tags($prt_node['answertest']));
			$new_node->setStudentAnswer($prt_node['sans']);
			$new_node->setTeacherAnswer($prt_node['tans']);

			//Other options
			$new_node->setTestOptions($prt_node['testoptions']);
			$new_node->setQuiet((int)$prt_node['quiet']);

			//True child
			$new_node->setTrueScoreMode(strip_tags($prt_node['truescoremode']));
			$new_node->setTrueScore(strip_tags($prt_node['truescore']));
			$new_node->setTruePenalty(strip_tags($prt_node['truepenalty']));
			$new_node->setTrueAnswerNote($prt_node['trueanswernote']);
			$new_node->setTrueFeedbackFormat(1);

			$mapping = $this->getMediaObjectsFromXML($prt_node['truefeedback'][0]['file']);
			$truefeedback = assStackQuestionUtils::_casTextConverter($this->replaceMediaObjectReferences($prt_node['truefeedback'][0]['text'], $mapping), $this->getQuestion()->getTitle(), TRUE);
			$new_node->setTrueFeedback(ilUtil::secureString($truefeedback, true, $this->getRTETags()));

			//False child
			$new_node->setFalseScoreMode(strip_tags($prt_node['falsescoremode']));
			$new_node->setFalseScore(strip_tags($prt_node['falsescore']));
			$new_node->setFalsePenalty(strip_tags($prt_node['falsepenalty']));
			$new_node->setFalseAnswerNote($prt_node['falseanswernote']);
			$new_node->setFalseFeedbackFormat(1);

			$mapping = $this->getMediaObjectsFromXML($prt_node['falsefeedback'][0]['file']);
			$falsefeedback = assStackQuestionUtils::_casTextConverter($this->replaceMediaObjectReferences($prt_node['falsefeedback'][0]['text'], $mapping), $this->getQuestion()->getTitle(), TRUE);
			$new_node->setFalseFeedback(ilUtil::secureString($falsefeedback, true, $this->getRTETags()));

			if ($is_first_node)
			{
				$prt_nodes['first'] = $new_node->getNodeName();
				$is_first_node = false;
			}

			$prt_nodes[] = $new_node;
		}

		//array of assStackQuestionPRTNode
		return $prt_nodes;
	}

	private function getTestsFromXML($data)
	{
		$this->getPlugin()->includeClass('model/ilias_object/test/class.assStackQuestionTest.php');
		$tests = array();

		foreach ($data as $test)
		{
			//Main attributes needed to create an TestOBJ
			$test_case = (int)$test['testcase'];
			$new_test = new assStackQuestionTest(-1, $this->getQuestion()->getId(), $test_case);

			//Creation of inputs
			$test_inputs = $this->getTestInputsFromXML($test['testinput'], $this->getQuestion()->getId(), $test_case);
			$new_test->setTestInputs($test_inputs);

			//Creation of expected results
			$test_expected = $this->getTestExpectedFromXML($test['expected'], $this->getQuestion()->getId(), $test_case);
			$new_test->setTestExpected($test_expected);

			$tests[] = $new_test;
		}

		//array of assStackQuestionTest
		return $tests;
	}

	private function getTestInputsFromXML($data, $question_id, $test_case)
	{
		$this->getPlugin()->includeClass('model/ilias_object/test/class.assStackQuestionTestInput.php');
		$test_inputs = array();

		foreach ($data as $input)
		{
			$new_test_input = new assStackQuestionTestInput(-1, $this->getQuestion()->getId(), $test_case);

			$new_test_input->setTestInputName($input['name']);
			$new_test_input->setTestInputValue($input['value']);

			$test_inputs[] = $new_test_input;
		}

		//array of assStackQuestionTestInput
		return $test_inputs;
	}

	private function getTestExpectedFromXML($data, $question_id, $test_case)
	{
		$this->getPlugin()->includeClass('model/ilias_object/test/class.assStackQuestionTestExpected.php');
		$test_expected = array();

		foreach ($data as $expected)
		{
			//Getting the PRT name
			$prt_name = strip_tags($expected['name']);
			$new_test_expected = new assStackQuestionTestExpected(-1, $this->getQuestion()->getId(), $test_case, $prt_name);

			$new_test_expected->setExpectedScore(strip_tags($expected['expectedscore']));
			$new_test_expected->setExpectedPenalty(strip_tags($expected['expectedpenalty']));
			$new_test_expected->setExpectedAnswerNote($expected['expectedanswernote']);

			$test_expected[] = $new_test_expected;
		}

		//array of assStackQuestionTestExpected
		return $test_expected;
	}

	private function getDeployedSeedsFromXML($data)
	{
		$this->getPlugin()->includeClass('model/ilias_object/class.assStackQuestionDeployedSeed.php');
		$deployed_seeds = array();

		foreach ($data as $deployed_seed_string)
		{
			$deployed_seed = new assStackQuestionDeployedSeed(-1, $this->getQuestion()->getId(), (int)$deployed_seed_string);
			$deployed_seeds[] = $deployed_seed;
		}

		//array of assStackQuestionDeployedSeed
		return $deployed_seeds;
	}

	private function getExtraInfoFromXML($data)
	{
		$this->getPlugin()->includeClass('model/ilias_object/class.assStackQuestionExtraInfo.php');
		$extra_info = new assStackQuestionExtraInfo(-1, $this->getQuestion()->getId());

		//General feedback property
		$mapping = $this->getMediaObjectsFromXML($data['generalfeedback'][0]['file']);
		$how_to_solve = assStackQuestionUtils::_casTextConverter($this->replaceMediaObjectReferences($data['generalfeedback'][0]['text'], $mapping), $this->getQuestion()->getTitle(), TRUE);
		$extra_info->setHowToSolve(ilUtil::secureString($how_to_solve, true, $this->getRTETags()));
		//Penalty property
		$penalty = $data['penalty'];
		$extra_info->setPenalty($penalty);
		//Hidden property
		$hidden = $data['hidden'];
		$extra_info->setHidden($hidden);

		//assStackQuestionExtraInfo
		return $extra_info;
	}

	/**
	 * Create media objects from array converted file elements
	 * @param    array $data [['_attributes' => ['name' => string, 'path' => string], '_content' => string], ...]
	 * @return    array             filename => object_id
	 */
	private function getMediaObjectsFromXML($data = array())
	{
		$mapping = array();
		foreach ((array)$data as $file)
		{
			$name = $file['_attributes']['name'];
			$path = $file['_attributes']['path'];
			$src = $file['_content'];

			$temp = ilUtil::ilTempnam();
			file_put_contents($temp, base64_decode($src));
			$media_object = ilObjMediaObject::_saveTempFileAsMediaObject($name, $temp, false);
			@unlink($temp);

			$this->media_objects[$media_object->getId()] = $media_object;
			$mapping[$name] = $media_object->getId();
		}

		return $mapping;
	}

	/**
	 * Replace references to media objects in a text
	 * @param    string    text from moodleXML with local references
	 * @param    array    mapping of filenames to media object IDs
	 * @return    string    text with paths to media objects
	 */
	private function replaceMediaObjectReferences($text = "", $mapping = array())
	{
		foreach ($mapping as $name => $id)
		{
			$text = str_replace('src="@@PLUGINFILE@@/' . $name, 'src="' . ILIAS_HTTP_PATH . '/data/' . CLIENT_ID . '/mobs/mm_' . $id . "/" . $name . '"', $text);
		}

		return $text;
	}

	/**
	 * Clear the list of media objects
	 * This should be called for every new question import
	 */
	private function clearMediaObjects()
	{
		$this->media_objects = array();
	}

	/**
	 * Save the usages of media objects in a question
	 * @param integer $question_id
	 */
	private function saveMediaObjectUsages($question_id)
	{
		foreach ($this->media_objects as $id => $media_object)
		{
			ilObjMediaObject::_saveUsage($media_object->getId(), "qpl:html", $question_id);
		}
		$this->media_objects = array();
	}

	/**
	 * Purge the media objects colleted for a not imported question
	 */
	private function purgeMediaObjects()
	{
		foreach ($this->media_objects as $id => $media_object)
		{
			$media_object->delete();
		}
		$this->media_objects = array();
	}


	/**
	 * Check if the question has all data needed to work properly
	 * In this method is done the check for new syntax in CASText from STACK 4.0
	 * @return boolean if question has been properly created
	 */
	public function checkQuestion(assStackQuestion $question)
	{
		//Step 1: Check if there is one option object and at least one input, one prt with at least one node;
		if (!is_a($question->getOptions(), 'assStackQuestionOptions'))
		{
			return false;
		}
		if (is_array($question->getInputs()))
		{
			foreach ($question->getInputs() as $input)
			{
				if (!is_a($input, 'assStackQuestionInput'))
				{
					return false;
				}
			}
		} else
		{
			return false;
		}
		if (is_array($question->getPotentialResponsesTrees()))
		{
			foreach ($question->getPotentialResponsesTrees() as $prt)
			{
				if (!is_a($prt, 'assStackQuestionPRT'))
				{
					return false;
				} else
				{
					foreach ($prt->getPRTNodes() as $node)
					{
						if (!is_a($node, 'assStackQuestionPRTNode'))
						{
							return false;
						}
					}
				}
			}
		} else
		{
			return false;
		}

		//Step 2: Check options
		$options_are_ok = $question->getOptions()->checkOptions(TRUE);

		//Step 3: Check inputs
		foreach ($question->getInputs() as $input)
		{
			$inputs_are_ok = $input->checkInput(TRUE);
			if ($inputs_are_ok == FALSE)
			{
				break;
			}
		}

		//Step 4A: Check PRT
		if (is_array($question->getPotentialResponsesTrees()))
		{
			foreach ($question->getPotentialResponsesTrees() as $PRT)
			{
				$PRTs_are_ok = $PRT->checkPRT(TRUE);
				if ($PRTs_are_ok == FALSE)
				{
					break;
				} else
				{
					//Step 4B: Check Nodes
					if (is_array($PRT->getPRTNodes()))
					{
						foreach ($PRT->getPRTNodes() as $node)
						{
							$Nodes_are_ok = $node->checkPRTNode(TRUE);
							if ($Nodes_are_ok == FALSE)
							{
								break;
							}
						}
					}
					//Step 4C: Check if nodes make a PRT
				}
			}
		}

		//Step 5: Check tests
		if (sizeof($question->getTests()))
		{
			foreach ($question->getTests() as $test)
			{
				if (!is_a($test, 'assStackQuestionTest'))
				{
					return false;
				} else
				{
					$tests_creation_is_ok = $test->checkTest(TRUE);
					//Step 5B: Check inputs
					foreach ($test->getTestInputs() as $input)
					{
						$test_inputs_are_ok = $input->checkTestInput(TRUE);
						if ($test_inputs_are_ok == FALSE)
						{
							break;
						}
					}
					//Step 5C: Check expected
					foreach ($test->getTestExpected() as $expected)
					{
						$test_expected_are_ok = $expected->checkTestExpected(TRUE);
						if ($test_expected_are_ok == FALSE)
						{
							break;
						}
					}
					if ($tests_creation_is_ok AND $test_inputs_are_ok AND $test_expected_are_ok)
					{
						$test_are_ok = TRUE;
					} else
					{
						$test_are_ok = FALSE;
					}
				}
			}
		} else
		{
			$test_are_ok = TRUE;
		}

		if ($options_are_ok AND $inputs_are_ok AND $PRTs_are_ok AND $Nodes_are_ok AND $test_are_ok)
		{
			return true;
		} else
		{
			return false;
		}
	}

	/*
	 * GETTERS AND SETTERS
	 */

	/**
	 * @param \ilassStackQuestionPlugin $plugin
	 */
	public function setPlugin($plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * @return \ilassStackQuestionPlugin
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * @param \assStackQuestion $question
	 */
	public function setQuestion($question)
	{
		$this->question = $question;
	}

	/**
	 * @return \assStackQuestion
	 */
	public function getQuestion()
	{
		return $this->question;
	}

	/**
	 * @param int $first_question
	 */
	public function setFirstQuestion($first_question)
	{
		$this->first_question = $first_question;
	}

	/**
	 * @return int
	 */
	public function getFirstQuestion()
	{
		return $this->first_question;
	}

	/**
	 * @return string    allowed html tags, e.g. "<em><strong>..."
	 */
	public function setRTETags($tags)
	{
		$this->rte_tags = $tags;
	}

	/**
	 * @return string    allowed html tags, e.g. "<em><strong>..."
	 */
	public function getRTETags()
	{
		return $this->rte_tags;
	}
}
