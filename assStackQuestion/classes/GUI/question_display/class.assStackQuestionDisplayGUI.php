<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question PREVIEW of question GUI class
 * This class provides a view for the preview of a specific STACK Question when not in a test
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: 2.3$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionDisplayGUI
{
	/**
	 * Plugin instance for templates and language management
	 * @var ilassStackQuestionPlugin
	 */
	private $plugin;

	/**
	 * @var ilTemplate for showing the preview
	 */
	private $template;

	/**
	 * @var array with the data from assStackQuestionDisplay
	 */
	private $display;

	/**
	 * Set all the data needed for call the getQuestionDisplayGUI() method.
	 * @param ilassStackQuestionPlugin $plugin
	 * @param array $display_data
	 */
	function __construct(ilassStackQuestionPlugin $plugin, $display_data)
	{
		//Set plugin object
		$this->setPlugin($plugin);

		//Set template for preview
		$this->setTemplate($this->getPlugin()->getTemplate('tpl.il_as_qpl_xqcas_question_display.html'));
		//Add CSS to the template
		$this->getTemplate()->addCss($this->getPlugin()->getStyleSheetLocation('css/qpl_xqcas_question_display.css'));

		//Add MathJax (Ensure MathJax is loaded)
		include_once "./Services/Administration/classes/class.ilSetting.php";
		$mathJaxSetting = new ilSetting("MathJax");
		$this->getTemplate()->addJavaScript($mathJaxSetting->get("path_to_mathjax"));

		//Set preview data
		$this->setDisplay($display_data);
	}

	/**
	 * ### MAIN METHOD OF THIS CLASS ###
	 * This method is called from assStackQuestionGUI and assStackQuestionPreviewGUI to get the question display HTML.
	 * @return ilTemplate the STACK Question display HTML
	 */
	public function getQuestionDisplayGUI($show_specific_feedback_for_each_answer = FALSE)
	{
		//Step 1: Enable ajax;
		$this->enableAjax();

		//Step 2: Prepare extra info for replacement in question text.
		$this->prepareExtraInfo();

		//Step 3: Replace placeholders
		$this->replacePlaceholders($show_specific_feedback_for_each_answer);

		//Step 5: Fill template
		$this->fillTemplate();

		//Step 6: Returns HTML
		return $this->getTemplate();
	}

	/**
	 * Create all variables needed for the validation through Ajax, JQuery and JavaScript.
	 * @global ilTemplate $tpl
	 */
	public function enableAjax()
	{
		global $tpl;

		if (is_array($this->getDisplay('inputs')))
		{
			foreach ($this->getDisplay('inputs') as $input_name => $input)
			{
				if ($this->getDisplay('validation', $input_name) == 'instant')
				{
					//Instant validation
					$this->jsconfig = new stdClass();
					$this->jsconfig->validate_url = ILIAS_HTTP_PATH . "/Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/instant_validation.php";

					$this->jstexts = new stdClass();
					$this->jstexts->page = $this->getPlugin()->txt('page');

					$tpl->addJavascript('Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/templates/js/instant_validation.js');
					$tpl->addOnLoadCode('il.instant_validation.init(' . json_encode($this->jsconfig) . ',' . json_encode($this->jstexts) . ')');
					continue;
				} elseif ($this->getDisplay('validation', $input_name) == 'button')
				{
					//Button Validation
					$this->jsconfig = new stdClass();
					$this->jsconfig->validate_url = ILIAS_HTTP_PATH . "/Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/validation.php";

					$this->jstexts = new stdClass();
					$this->jstexts->page = $this->getPlugin()->txt('page');

					$tpl->addJavascript('Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/templates/js/assStackQuestion.js');
					$tpl->addOnLoadCode('il.assStackQuestion.init(' . json_encode($this->jsconfig) . ',' . json_encode($this->jstexts) . ')');

					continue;
				}
			}
		}

	}

	/**
	 *Prepare the properly replacement for validation inputs
	 */
	private function prepareExtraInfo()
	{
		if (is_array($this->getDisplay('inputs')))
		{
			foreach ($this->getDisplay('inputs') as $input_name => $input)
			{
				//Prepare validation button and division for giving feedback
				if ($this->getDisplay('validation', $input_name) == 'instant')
				{
					//Instant validation
					$validation = $this-> validationDisplayDivision($input_name, $input);
					$this->setDisplay($validation, 'validation', $input_name);
				} elseif ($this->getDisplay('validation', $input_name) == 'button')
				{
					//Button Validation
					$validation = $this->validationDisplayDivision($input_name, $input);
					$this->setDisplay($validation, 'validation', $input_name);
				} elseif ($this->getDisplay('validation', $input_name) == 'hidden')
				{
					//Button Validation
					$validation = "";
					$this->setDisplay($validation, 'validation', $input_name);
				} else
				{
					$this->setDisplay(' ', 'validation', $input_name);
				}
			}
		}
	}

	/**
	 * Replace validation feedback placeholders by HTML division.
	 * @param string $input_name
	 */
	private function validationDisplayDivision($input_name, $input)
	{
		if (isset($input['matrix_h']))
		{
			return '<div id="validation_xqcas_roll_' . $this->getDisplay('question_id') . '_' . $input_name . '"></div><div id="validation_xqcas_' . $this->getDisplay('question_id') . '_' . $input_name . '"></div><div id="xqcas_input_matrix_width_' . $input_name . '" style="visibility: hidden">' . $input['matrix_w'] . '</div><div id="xqcas_input_matrix_height_' . $input_name . '" style="visibility: hidden";>' . $input['matrix_h'] . '</div>';
		} else
		{
			return '<div id="validation_xqcas_roll_' . $this->getDisplay('question_id') . '_' . $input_name . '"></div><div class="xqcas_input_validation"><div id="validation_xqcas_' . $this->getDisplay('question_id') . '_' . $input_name . '"></div></div>';
		}
	}

	/**
	 * Returns the button for current input field.
	 * @param string $input_name
	 * @return HTML the HTML code of the button of validation for this input.
	 */
	private function validationButton($input_name)
	{
		return "<button style=\"height:2.2em;\" class=\"\" name=\"cmd[xqcas_" . $this->getDisplay('question_id') . '_' . $input_name . "]\"><span class=\"glyphicon glyphicon-ok\" aria-hidden=\"true\"></span></button>";
	}

	/**
	 * Replaces the placeholders with the info given by question display
	 */
	private function replacePlaceholders($show_feedback = FALSE)
	{
		//Step 1: Replace placeholders per each input
		if (is_array($this->getDisplay('inputs')))
		{
			foreach ($this->getDisplay('inputs') as $input_name => $input)
			{
				//Step 1.1 Replace input fields
				$display = $this->getDisplay('inputs', $input_name);
				//Check is is not string, then it's algebraic input.
				if (!is_string($display["display"]))
				{
					//Manage algebraic input rendering in this page
					$text = $this->renderAlgebraicInput($display["display"]);
				} else
				{
					//#22780 no <br> before input redering
					//Bootstrap div for align input and validation button
					if ($input["text_area"] == TRUE)
					{
						if ($input["show_validation"] > 0 AND !assStackQuestionUtils::_useInstantValidation())
						{
							$text = "<table><tr><td>" . $display['display'] . "</td><td class='stack_textarea'>" . $this->validationButton($input_name) . "</td></tr></table>";
						} else
						{
							$text = "<span>" . $display['display'] . "</span>";
						}
					} else
					{
						if ($input["show_validation"] > 0 AND !assStackQuestionUtils::_useInstantValidation())
						{
							$text = "<span>" . $display['display'] . " " . $this->validationButton($input_name) . "</span>";
						} else
						{
							$text = "<span>" . $display['display'] . "</span>";
						}
					}
				}

				$input_text = str_replace("[[input:{$input_name}]]", $text, $this->getDisplay('question_text'));
				$this->setDisplay($input_text, 'question_text');
				//Step 1.2 Replace validation fields
				if (strlen(trim($this->getDisplay('validation', $input_name))))
				{
					if ($show_feedback AND strlen($display["display_rendered"]) > 1)
					{
						$validation_text = str_replace("[[validation:{$input_name}]]", html_writer::tag('p', $display["validation"]), $this->getDisplay('question_text'));
					} else
					{
						$validation_text = str_replace("[[validation:{$input_name}]]", $this->getDisplay('validation', $input_name), $this->getDisplay('question_text'));
					}
				} else
				{
					$validation_text = str_replace("[[validation:{$input_name}]]", "", $this->getDisplay('question_text'));
				}

				$this->setDisplay($validation_text, 'question_text');
			}
		}

		//Step 2: Replace feedback placeholders
		if (is_array($this->getDisplay('prts')) AND $show_feedback)
		{
			foreach ($this->getDisplay('prts') as $prt_name => $prt)
			{
				//Step 2.1 Replace prt fields
				if ($this->getDisplay('prts', $prt_name))
				{
					$display = $this->getDisplay('prts', $prt_name);
				}
				$question_text = str_replace("[[feedback:{$prt_name}]]", $display['display'], $this->getDisplay('question_text'));
				$this->setDisplay($question_text, 'question_text');
				$question_specific_feedback = str_replace("[[feedback:{$prt_name}]]", $display['display'], $this->getDisplay('question_specific_feedback'));
				$this->setDisplay($question_specific_feedback, 'question_specific_feedback');
			}
		} else
		{
			$question_text = preg_replace('/\[\[feedback:(.*?)\]\]/', "", $this->getDisplay('question_text'));
			$this->setDisplay($question_text, 'question_text');
			$question_specific_feedback = preg_replace('/\[\[feedback:(.*?)\]\]/', "", $this->getDisplay('question_specific_feedback'));
			$this->setDisplay($question_specific_feedback, 'question_specific_feedback');
		}

	}

	/**
	 * Fills the template for question display
	 */
	private function fillTemplate()
	{
		$this->getTemplate()->setVariable('QUESTION_ID', $this->getDisplay('question_id'));
		$this->getTemplate()->setVariable('VALIDATION_URL', ILIAS_HTTP_PATH . "/Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/validation.php");
		$this->getTemplate()->setVariable('QUESTION_TEXT', assStackQuestionUtils::_getLatex($this->getDisplay('question_text')));
		//$this->getTemplate()->setVariable('SPECIFIC_FEEDBACK', assStackQuestionUtils::_getLatex($this->getDisplay('question_specific_feedback')));
	}

	/*
	 * GETTERS AND SETTERS
	 */

	/**
	 * @param \array OR string $display
	 */
	public function setDisplay($display, $selector = '', $selector2 = '')
	{
		if ($selector AND $selector2)
		{
			$this->display[$selector][$selector2] = $display;
		} elseif ($selector)
		{
			$this->display[$selector] = $display;
		} else
		{
			$this->display = $display;
		}
	}

	/**
	 * @return \array
	 */
	public function getDisplay($selector = '', $selector2 = '')
	{
		if ($selector AND $selector2)
		{
			return $this->display[$selector][$selector2];
		} elseif ($selector)
		{
			return $this->display[$selector];
		} else
		{
			return $this->display;
		}
	}

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
	 * @param \ilTemplate $template
	 */
	public function setTemplate($template)
	{
		$this->template = $template;
	}

	/**
	 * @return \ilTemplate
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * @param stdClass $input_info
	 * @return string The algebraic input rendered
	 */
	public function renderAlgebraicInput(stdClass $input_info)
	{
		//Input template
		$input_tpl = $this->getPlugin()->getTemplate("tpl.il_as_qpl_xqcas_algebraic_input.html");
		//Set input name
		$input_tpl->setVariable('INPUT_NAME', 'xqcas_' . $this->getDisplay('question_id') . '_' . $input_info->name);
		//Set size
		$input_tpl->setVariable('INPUT_SIZE', $input_info->input->get_parameter('boxWidth') * 1.1);
		//Set input value
		$input_state = $input_info->state;
		$input_value = $input_info->input->contents_to_maxima($input_state->contents);

		if (!strlen($input_value) AND strlen($input_info->input->get_parameter('syntaxHint')))
		{
			//Use syntax hint
			$input_tpl->setVariable('INPUT_VALUE', $input_info->input->get_parameter('syntaxHint'));
		} else
		{
			//Use value
			$input_tpl->setVariable('INPUT_VALUE', $input_value);
		}

		//Add validation button if needed
		if ($input_info->input->get_parameter('showValidation') AND !assStackQuestionUtils::_useInstantValidation())
		{
			//Validation Button tpl
			$validation_tpl = $this->getPlugin()->getTemplate("tpl.il_as_qpl_xqcas_validation_button.html");
			//Set validation button id
			$validation_tpl->setVariable('BUTTON_NAME', 'cmd[xqcas_' . $this->getDisplay('question_id') . '_' . $input_info->name . ']');
			$input_tpl->setVariable('VALIDATION_BUTTON', $validation_tpl->get());
		} else
		{
			$input_tpl->setVariable('VALIDATION_BUTTON', '');
		}

		return $input_tpl->get();
	}
}