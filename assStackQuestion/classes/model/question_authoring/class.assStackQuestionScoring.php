<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question scoring management class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id: 2.0$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionScoring
{

	/**
	 * @var mixed array with the potential response trees.
	 */
	private $potentialresponse_trees;

	/**
	 * @var float question points
	 */
	private $question_points;


	/**
	 * @param $potentialresponse_trees array of assStackQuestionPRT
	 */
	function __construct($potentialresponse_trees)
	{
		$this->setPotentialresponseTrees($potentialresponse_trees);
	}

	/**
	 * ### MAIN METHOD OF THIS CLASS ###
	 * fill an structure with the points value per each node in a prt which will be used
	 * to present the scoring of a question in assStackQuestionScoring
	 */
	public function reScalePotentialresponseTrees($question_points)
	{
		//Set variables
		$this->setQuestionPoints($question_points);
		$max_weight = 0.0;
		$structure = array();

		//Get max weight of the PRT
		foreach ($this->getPotentialresponseTrees() as $prt_name => $prt) {
			$max_weight += $prt->getPRTValue();
		}

		//fill the structure
		foreach ($this->getPotentialresponseTrees() as $prt_name => $prt) {
			$prt_max_weight = $prt->getPRTValue();
			$prt_max_points = ($prt_max_weight / $max_weight) * $this->getQuestionPoints();
			$structure[$prt_name]['max_points'] = $prt_max_points;
			foreach ($prt->getPRTNodes() as $node_name => $node) {
				$structure[$prt_name][$node_name]['true_mode'] = $node->getTrueScoreMode();
				$structure[$prt_name][$node_name]['true_value'] = ($node->getTrueScore() * $prt_max_points);
				$structure[$prt_name][$node_name]['false_mode'] = $node->getFalseScoreMode();
				$structure[$prt_name][$node_name]['false_value'] = ($node->getFalseScore() * $prt_max_points);
			}
		}

		return $structure;
	}

	/**
	 * @param mixed $potentialresponse_trees
	 */
	public function setPotentialresponseTrees($potentialresponse_trees)
	{
		$this->potentialresponse_trees = $potentialresponse_trees;
	}

	/**
	 * @return mixed
	 */
	public function getPotentialresponseTrees()
	{
		return $this->potentialresponse_trees;
	}

	/**
	 * @param float $question_points
	 */
	public function setQuestionPoints($question_points)
	{
		$this->question_points = $question_points;
	}

	/**
	 * @return float
	 */
	public function getQuestionPoints()
	{
		return $this->question_points;
	}
} 