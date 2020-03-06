<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/FormProperties/class.ilMultipartFormPropertyGUI.php';

/**
 * Columns property GUI class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 *
 */
class ilColumnsFormPropertyGUI extends ilMultipartFormPropertyGUI
{

	/**
	 * @var ilTemplate
	 */
	private $template;

	/**
	 * Distribution of width between the parts, key is part name.
	 * @var array
	 */
	private $columns_width = array();


	function __construct($a_title = "", $a_postvar = "", $a_container_width = "", $a_show_title = "", $a_columns_width = "")
	{
		parent::__construct($a_title, $a_postvar, $a_container_width, $a_show_title);

		//Set template for columns
		$template = new ilTemplate('./Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/templates/tpl.columns_form_property.html', TRUE, TRUE);
		$this->setTemplate($template);

		//Set columns width
		if ($a_columns_width) {
			$this->setColumnsWidth($a_columns_width);
		}
	}

	/**
	 * @param string $show_part_name
	 * @return HTML for this form property
	 */
	protected function render($show_part_name = "")
	{
		$this->getTemplate()->setVariable("CONTAINER_WIDTH", $this->getContainerWidth());

		//Fill Headers
		foreach ($this->getParts() as $index => $part) {
			$this->getTemplate()->setCurrentBlock('column_header');
			$this->getTemplate()->setVariable("COLUMN_WIDTH", $this->getColumnsWidth($part->getTitle()));
			if ($show_part_name) {
				$this->getTemplate()->setVariable("PART_NAME", $part->getTitle());
			}
			$this->getTemplate()->parseCurrentBlock();
		}

		//Fill Contents
		foreach ($this->getParts() as $index => $part) {
			//Fill column content
			$this->getTemplate()->setCurrentBlock('column_content');
			$this->getTemplate()->setVariable("COLUMN_WIDTH", $this->getColumnsWidth($part->getTitle()));
			$this->getTemplate()->setVariable("PART_TYPE", $part->getType());

			foreach ($part->getContent() as $form_property) {
				//Fill column content properties
				$this->getTemplate()->setCurrentBlock('prop_container');

				//Set width
				$this->getTemplate()->setVariable("TITLE_WIDTH", $this->getWidthDivision('title'));
				$this->getTemplate()->setVariable("CONTENT_WIDTH", $this->getWidthDivision('content'));
				$this->getTemplate()->setVariable("FOOTER_WIDTH", $this->getWidthDivision('footer'));

				//Show title and info
				if ($this->getShowTitle())
				{
					if ($form_property->getRequired())
					{
						$this->getTemplate()->setVariable("PROP_TITLE", $form_property->getTitle() . "<font color=\"red\"> *</font>");
					} else
					{
						$this->getTemplate()->setVariable("PROP_TITLE", $form_property->getTitle());
					}
				}
				if (isset($form_property->info)) {
					$this->getTemplate()->setVariable("PROP_INFO", $form_property->getInfo());
				}

				//Add specific test info
				$castext_english = "In this field you can use CAS Text. CASText is CAS-enabled text. CASText is simply HTML into which LaTeX mathematics and CAS commands can be embedded. These CAS commands are executed before the question is displayed to the user. Use only simple LaTeX mathematics structures. Only a small part of core LaTeX is supported.";
				$castext_german = "In diesem Feld können Sie CAS Text verwenden, CASText ist CAS-aktivierter Text. CASText ist einfach HTML, in das LaTeX-Mathematik und CAS-Befehle eingebettet werden können. Diese CAS-Befehle werden ausgeführt, bevor die Frage dem Benutzer angezeigt wird. Verwenden Sie nur einfache LaTeX-Mathematikstrukturen. Nur ein kleiner Teil des LaTeX-Kerns wird unterstützt.";
				$html_english = "In this field, only HTML elements are allowed, CASText won't be rendered";
				$html_german = "In diesem Feld sind nur HTML-Elemente erlaubt, CASText wird nicht gerendert";
				$casexpresion_english = "In this field, you can only use CAS expresion, but not HTML code.";
				$casexpresion_german = "In diesem Feld können sie nur CAS-Ausdruck verwenden, kein HTML-Code.";

				global $DIC;

				$lng = $DIC->language();

				//Student answer
				include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';
				if (assStackQuestionUtils::_endsWith($form_property->postvar, "_student_answer"))
				{
					$comment_id = rand(100000, 999999);
					require_once("Services/UIComponent/Tooltip/classes/class.ilTooltipGUI.php");
					ilTooltipGUI::addTooltip('ilAssStackQuestion' . $comment_id, $lng->getUserLanguage() == "de" ? $casexpresion_german : $casexpresion_english);
					$this->getTemplate()->setVariable("COMMENT_ID", $comment_id);
					$this->getTemplate()->setVariable("SPECIFIC_TEXT_INFO", $lng->getUserLanguage() == "de" ? "<a href='javascript:;'>[CAS Ausdruck]</a>" : "<a href='javascript:;'>[CAS Expresion]</a>");
				}

				//Teacher answer
				include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';
				if (assStackQuestionUtils::_endsWith($form_property->postvar, "_teacher_answer"))
				{
					$comment_id = rand(100000, 999999);
					require_once("Services/UIComponent/Tooltip/classes/class.ilTooltipGUI.php");
					ilTooltipGUI::addTooltip('ilAssStackQuestion' . $comment_id, $lng->getUserLanguage() == "de" ? $casexpresion_german : $casexpresion_english);
					$this->getTemplate()->setVariable("COMMENT_ID", $comment_id);
					$this->getTemplate()->setVariable("SPECIFIC_TEXT_INFO", $lng->getUserLanguage() == "de" ? "<a href='javascript:;'>[CAS Ausdruck]</a>" : "<a href='javascript:;'>[CAS Expresion]</a>");
				}

				//Node options
				include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';
				if (assStackQuestionUtils::_endsWith($form_property->postvar, "_options"))
				{
					$comment_id = rand(100000, 999999);
					require_once("Services/UIComponent/Tooltip/classes/class.ilTooltipGUI.php");
					ilTooltipGUI::addTooltip('ilAssStackQuestion' . $comment_id, $lng->getUserLanguage() == "de" ? $casexpresion_german : $casexpresion_english);
					$this->getTemplate()->setVariable("COMMENT_ID", $comment_id);
					$this->getTemplate()->setVariable("SPECIFIC_TEXT_INFO", $lng->getUserLanguage() == "de" ? "<a href='javascript:;'>[CAS Ausdruck]</a>" : "<a href='javascript:;'>[CAS Expresion]</a>");
				}

				//specific feedback
				include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';
				if (assStackQuestionUtils::_endsWith($form_property->postvar, "_specific_feedback"))
				{
					$comment_id = rand(100000, 999999);
					require_once("Services/UIComponent/Tooltip/classes/class.ilTooltipGUI.php");
					ilTooltipGUI::addTooltip('ilAssStackQuestion' . $comment_id, $lng->getUserLanguage() == "de" ? $castext_german : $castext_english);
					$this->getTemplate()->setVariable("COMMENT_ID", $comment_id);
					$this->getTemplate()->setVariable("SPECIFIC_TEXT_INFO", "<a href='javascript:;'>[CAS Text]</a>");
				}

				//Fill property
				$form_property->insert($this->getTemplate());
				$this->getTemplate()->setCurrentBlock('prop_container');

				//Set width
				$this->getTemplate()->setVariable("TITLE_WIDTH", $this->getWidthDivision('title'));
				$this->getTemplate()->setVariable("CONTENT_WIDTH", $this->getWidthDivision('content'));
				$this->getTemplate()->setVariable("FOOTER_WIDTH", $this->getWidthDivision('footer'));

				//Parse prop container
				$this->getTemplate()->parseCurrentBlock();
			}
			//Parse column content
			$this->getTemplate()->setCurrentBlock('column_content');
			$this->getTemplate()->parseCurrentBlock();

		}

		//Return template
		return $this->getTemplate()->get();
	}

	/**
	 * Add a part to the form, setting the position value in the part object
	 * and in the parts array of this class.
	 * @param ilMultipartFormPart $part
	 */
	public function addPart(ilMultipartFormPart $part, $column_width = "")
	{
		if (!$column_width) {
			throw new Exception('No column width when creating part of ilColumnsFormPropertyGUI');
		}

		//Set column width
		$this->columns_width[$part->getTitle()] = $column_width;
		parent::addPart($part);
	}

	/*
	 * GETTERS AND SETTERS
	 */

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
	 * @param array $columns_width
	 */
	public function setColumnsWidth($columns_width)
	{
		//Check valid column width array
		foreach ($this->getParts() as $part) {
			if (!isset($columns_width[$part->getTitle()])) {
				throw new Exception('Columns width not valid');
			}
		}

		$this->columns_width = $columns_width;
	}

	/**
	 * @return array
	 */
	public function getColumnsWidth($part_title = "")
	{
		if ($part_title) {
			if (isset($this->columns_width[$part_title])) {
				return $this->columns_width[$part_title];
			} else {
				throw new Exception('Unknown width for part %s', $part_title);
			}
		}
		return $this->columns_width;
	}


}