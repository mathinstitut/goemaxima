<?php
/**
 * Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
include_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
 * STACK Question Unit tests Table GUI
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id: 2.4$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionTestTableGUI extends ilTable2GUI
{
	/**
	 * Constructor
	 * @param   assStackQuestionTestGUI $a_parent_obj
	 * @param   string $a_parent_cmd
	 * @return
	 */
	public function __construct($a_parent_obj, $a_parent_cmd)
	{
		global $DIC;

		$this->lng = $DIC->language();
		$this->ctrl = $DIC->ctrl();

		$this->plugin = $a_parent_obj->getPlugin();
		$this->unit_tests = $a_parent_obj->getTests();
		$this->unit_tests_results = $a_parent_obj->getUnitTestResults();
		$this->mode = $a_parent_obj->mode;

		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setStyle('table', 'fullwidth');

		/**
		 * If mode it's TRUE it's because we have results from the test and we have to
		 * show the received results, that adds 5 columns to the table
		 */
		if ($this->mode)
		{
			$this->addColumn($this->plugin->txt('prt_name'));
			$this->addColumn($this->plugin->txt('ut_expected_mark'));
			$this->addColumn($this->plugin->txt('ut_received_mark'));
			$this->addColumn($this->plugin->txt('ut_expected_penalty'));
			$this->addColumn($this->plugin->txt('ut_received_penalty'));
			$this->addColumn($this->plugin->txt('ut_expected_answer_note'));
			$this->addColumn($this->plugin->txt('ut_received_answer_note'));
			$this->addColumn($this->plugin->txt('ut_cas_errors'));
			$this->addColumn($this->plugin->txt('ut_cas_feedback'));
		} else
		{
			$this->addColumn($this->plugin->txt('prt_name'));
			$this->addColumn($this->plugin->txt('ut_expected_mark'));
			$this->addColumn($this->plugin->txt('ut_expected_penalty'));
			$this->addColumn($this->plugin->txt('ut_expected_answer_note'));
		}

		$this->setRowTemplate("tpl.il_as_qpl_xqcas_test_row.html", $a_parent_obj->getPlugin()->getDirectory());
		$this->setEnableAllCommand(FALSE);
		$this->setEnableHeader(TRUE);
		$this->setEnableNumInfo(FALSE);
		$this->setEnableTitle(FALSE);
	}

	/**
	 * Get selectable columns
	 */
	public function getSelectableColumns()
	{
		return array();
	}

	/**
	 * Prepare the data to be shown
	 * This only adds the basic questrion values that will be used for filtering and sorting
	 * The more complex evaluations are only applied for the filled rows of the page
	 * @param assStackQuestionTest $testcase
	 */
	public function prepareData($testcase)
	{
		$data = array();
		if ($this->mode)
		{
			if (isset($testcase["prts"]))
			{
				foreach ($testcase["prts"] as $prt_name => $test_data)
				{
					$row = array();

					if (strlen($prt_name))
					{
						$row["test_prt_name"] = $prt_name;
					} else
					{
						$row["test_prt_name"] = " ";
					}

					if (strlen($test_data["expected_score"]))
					{
						$row["expected_score"] = $test_data["expected_score"];
					} else
					{
						$row["expected_score"] = " ";
					}

					if (strlen($test_data["received_score"]))
					{
						//Add cross if fail
						if ((float)$test_data["received_score"] != (float)$test_data["expected_score"])
						{
							require_once 'Services/UIComponent/Glyph/classes/class.ilGlyphGUI.php';
							$row["received_score"] = $test_data["received_score"] . '<img src="' . ilUtil::getImagePath("icon_not_ok.svg") . '" />';

						} else
						{
							$row["received_score"] = $test_data["received_score"];
						}
					} else
					{
						$row["received_score"] = " ";
					}

					if (strlen($test_data["expected_penalty"]))
					{
						$row["expected_penalty"] = $test_data["expected_penalty"];
					} else
					{
						$row["expected_penalty"] = " ";
					}

					if (strlen($test_data["received_penalty"]))
					{
						//Add cross if fail
						if ((float)$test_data["received_penalty"] != (float)$test_data["expected_penalty"])
						{
							//We are not checking penalties in ILIAS version of STACK
							$row["received_penalty"] = $test_data["received_penalty"];
							/*
							require_once 'Services/UIComponent/Glyph/classes/class.ilGlyphGUI.php';
							$row["received_penalty"] = $test_data["received_penalty"] . '<img src="' . ilUtil::getImagePath("icon_not_ok.svg") . '" />';*/

						} else
						{
							$row["received_penalty"] = $test_data["received_penalty"];
						}
					} else
					{
						$row["received_penalty"] = " ";
					}

					if (strlen($test_data["expected_answernote"]))
					{
						$row["expected_answernote"] = $test_data["expected_answernote"];
					} else
					{
						$row["expected_answernote"] = " ";
					}

					if (strlen($test_data["received_answernote"]))
					{
						//Add cross if fail
						if ((float)$test_data["received_answernote"] != (float)$test_data["expected_answernote"])
						{
							require_once 'Services/UIComponent/Glyph/classes/class.ilGlyphGUI.php';
							$row["received_answernote"] = $test_data["received_answernote"] . '<img src="' . ilUtil::getImagePath("icon_not_ok.svg") . '" />';

						} else
						{
							$row["received_answernote"] = $test_data["received_answernote"];
						}
					} else
					{
						$row["received_answernote"] = " ";
					}

					if (strlen($test_data["cas_errors"]))
					{
						$row["cas_errors"] = $test_data["cas_errors"];
					} else
					{
						$row["cas_errors"] = " ";
					}

					if (strlen($test_data["cas_feedback"]))
					{
						$row["cas_feedback"] = $test_data["cas_feedback"];
					} else
					{
						$row["cas_feedback"] = " ";
					}
					$data[] = $row;
				}
			}

			$this->setData($data);
		} else
		{
			/** @var assStackQuestionTestExpected $test_expected */
			foreach ($testcase->getTestExpected() as $test_expected)
			{
				$row = array();

				if (strlen($test_expected->getTestPRTName()))
				{
					$row["test_prt_name"] = $test_expected->getTestPRTName();
				} else
				{
					$row["test_prt_name"] = " ";
				}

				if (strlen($test_expected->getExpectedScore()))
				{
					$row["expected_score"] = $test_expected->getExpectedScore();
				} else
				{
					$row["expected_score"] = " ";
				}

				if (strlen($test_expected->getExpectedPenalty()))
				{
					$row["expected_penalty"] = $test_expected->getExpectedPenalty();
				} else
				{
					$row["expected_penalty"] = " ";
				}

				if (strlen($test_expected->getExpectedAnswerNote()))
				{
					$row["expected_answer_note"] = $test_expected->getExpectedAnswerNote();
				} else
				{
					$row["expected_answer_note"] = " ";
				}

				$data[] = $row;
			}
			$this->setData($data);
		}

	}

	/**
	 * @param assStackQuestionTestExpected $prt_data
	 */
	public function fillRow($prt_data)
	{
		if ($this->mode)
		{
			$this->tpl->setCurrentBlock('column');
			$this->tpl->setVariable('CONTENT', $prt_data['test_prt_name']);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock('column');
			$this->tpl->setVariable('CONTENT', $prt_data['expected_score']);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock('column');
			$this->tpl->setVariable('CONTENT', $prt_data['received_score']);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock('column');
			$this->tpl->setVariable('CONTENT', $prt_data['expected_penalty']);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock('column');
			$this->tpl->setVariable('CONTENT', $prt_data['received_penalty']);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock('column');
			$this->tpl->setVariable('CONTENT', $prt_data['expected_answernote']);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock('column');
			$this->tpl->setVariable('CONTENT', $prt_data['received_answernote']);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock('column');
			$this->tpl->setVariable('CONTENT', $prt_data['cas_errors']);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock('column');
			$this->tpl->setVariable('CONTENT', $prt_data['cas_feedback']);
			$this->tpl->parseCurrentBlock();
		} else
		{
			$this->tpl->setCurrentBlock('column');
			$this->tpl->setVariable('CONTENT', $prt_data['test_prt_name']);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock('column');
			$this->tpl->setVariable('CONTENT', $prt_data['expected_score']);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock('column');
			$this->tpl->setVariable('CONTENT', $prt_data['expected_penalty']);
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock('column');
			$this->tpl->setVariable('CONTENT', $prt_data['expected_answer_note']);
			$this->tpl->parseCurrentBlock();
		}
	}
}