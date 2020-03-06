<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
include_once "./Modules/TestQuestionPool/classes/import/qti12/class.assQuestionImport.php";
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question IMPORT OF QUESTIONS from an ILIAS file
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: 1.8$$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionImport extends assQuestionImport
{
	/**
	 * Receives parameters from a QTI parser and creates a valid ILIAS question object
	 *
	 * @param object $item The QTI item object
	 * @param integer $questionpool_id The id of the parent questionpool
	 * @param integer $tst_id The id of the parent test if the question is part of a test
	 * @param object $tst_object A reference to the parent test object
	 * @param integer $question_counter A reference to a question counter to count the questions of an imported question pool
	 * @param array $import_mapping An array containing references to included ILIAS objects
	 * @access public
	 */
	function fromXML(&$item, $questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
	{
		global $ilUser;

		// empty session variable for imported xhtml mobs
		unset($_SESSION["import_mob_xhtml"]);
		$presentation = $item->getPresentation();
		$duration = $item->getDuration();
		$shuffle = 0;
		$now = getdate();
		$created = sprintf("%04d%02d%02d%02d%02d%02d", $now['year'], $now['mon'], $now['mday'], $now['hours'], $now['minutes'], $now['seconds']);
		$answers = array();

		//Obtain question general data
		$this->addGeneralMetadata($item);
		$this->object->setTitle($item->getTitle());
		$this->object->setNrOfTries($item->getMaxattempts());
		$this->object->setComment($item->getComment());
		$this->object->setAuthor($item->getAuthor());
		$this->object->setOwner($ilUser->getId());
		$this->object->setQuestion(assStackQuestionUtils::_casTextConverter($this->object->QTIMaterialToString($item->getQuestiontext()), $item->getTitle(), TRUE));
		$this->object->setObjId($questionpool_id);
		$this->object->setPoints((float)$item->getMetadataEntry("POINTS"));
		$this->object->setEstimatedWorkingTime($duration["h"], $duration["m"], $duration["s"]);

		$this->object->saveQuestionDataToDb();

		//OPTIONS
		/* @var assStackQuestionOptions $options */
		$this->object->getPlugin()->includeClass("model/ilias_object/class.assStackQuestionOptions.php");
		$options = unserialize(base64_decode($item->getMetadataEntry('options')));
		$options->setSpecificFeedback($this->processNonAbstractedImageReferences(assStackQuestionUtils::_casTextConverter($options->getSpecificFeedback(), $item->getTitle(), TRUE), $item->getIliasSourceNic()));
		$options->setPRTCorrect($this->processNonAbstractedImageReferences($options->getPRTCorrect(), $item->getIliasSourceNic()));
		$options->setPRTIncorrect($this->processNonAbstractedImageReferences($options->getPRTIncorrect(), $item->getIliasSourceNic()));
		$options->setPRTPartiallyCorrect($this->processNonAbstractedImageReferences($options->getPRTPartiallyCorrect(), $item->getIliasSourceNic()));
		$options->setQuestionNote($this->processNonAbstractedImageReferences(assStackQuestionUtils::_casTextConverter($options->getQuestionNote(), $item->getTitle(), TRUE), $item->getIliasSourceNic()));
		$this->object->setOptions($options);

		//Inputs
		$this->object->getPlugin()->includeClass("model/ilias_object/class.assStackQuestionInput.php");
		$this->object->setInputs(unserialize(base64_decode($item->getMetadataEntry('inputs'))));

		//PRTs
		/* @var assStackQuestionPRT $prt */
		/* @var assStackQuestionPRTNode $node */
		$this->object->getPlugin()->includeClass("model/ilias_object/class.assStackQuestionPRT.php");
		$this->object->getPlugin()->includeClass("model/ilias_object/class.assStackQuestionPRTNode.php");
		$prts = unserialize(base64_decode($item->getMetadataEntry('prts')));
		foreach ($prts as $prt_name => $prt) {
			foreach ($prt->getPRTNodes() as $node_name => $node) {
				$node->setFalseFeedback($this->processNonAbstractedImageReferences(assStackQuestionUtils::_casTextConverter($node->getFalseFeedback(), $item->getTitle(), TRUE), $item->getIliasSourceNic()));
				$node->setTrueFeedback($this->processNonAbstractedImageReferences(assStackQuestionUtils::_casTextConverter($node->getTrueFeedback(), $item->getTitle(), TRUE), $item->getIliasSourceNic()));
			}
		}
		$this->object->setPotentialResponsesTrees($prts);

		//SEEDS
		$this->object->getPlugin()->includeClass("model/ilias_object/class.assStackQuestionDeployedSeed.php");
		$this->object->setDeployedSeeds(unserialize(base64_decode($item->getMetadataEntry('seeds'))));

		//TESTS
		$this->object->getPlugin()->includeClass("model/ilias_object/test/class.assStackQuestionTest.php");
		$this->object->getPlugin()->includeClass("model/ilias_object/test/class.assStackQuestionTestInput.php");
		$this->object->getPlugin()->includeClass("model/ilias_object/test/class.assStackQuestionTestExpected.php");
		$this->object->setTests(unserialize(base64_decode($item->getMetadataEntry('tests'))));

		//EXTRA INFO
		/* @var assStackQuestionExtraInfo $extra_info */
		$this->object->getPlugin()->includeClass("model/ilias_object/class.assStackQuestionExtraInfo.php");
		$extra_info = unserialize(base64_decode($item->getMetadataEntry('extra_info')));
		$extra_info->setHowToSolve($this->processNonAbstractedImageReferences(assStackQuestionUtils::_casTextConverter($extra_info->getHowToSolve(), $item->getTitle(), TRUE), $item->getIliasSourceNic()));
		$this->object->setExtraInfo($extra_info);


		// Don't save the question additionally to DB before media object handling
		// this would create double rows for options, prts etc.

		/*********************************
		 * Media object handling
		 * @see assClozeTestImport
		 ********************************/


		// handle the import of media objects in XHTML code
		$questiontext = $this->object->getQuestion();

		if (is_array($_SESSION["import_mob_xhtml"]))
		{
			include_once "./Services/MediaObjects/classes/class.ilObjMediaObject.php";
			include_once "./Services/RTE/classes/class.ilRTE.php";
			foreach ($_SESSION["import_mob_xhtml"] as $mob)
			{
				if ($tst_id > 0)
				{
					//#22754
					$importfile = $this->getTstImportArchivDirectory() . '/' . current(explode('?', $mob["uri"]));
				}
				else
				{
					//#22754
					$importfile = $this->getQplImportArchivDirectory() . '/' . current(explode('?', $mob["uri"]));
				}

				$GLOBALS['ilLog']->write(__METHOD__.': import mob from dir: '. $importfile);

				$media_object =& ilObjMediaObject::_saveTempFileAsMediaObject(basename($importfile), $importfile, FALSE);
				ilObjMediaObject::_saveUsage($media_object->getId(), "qpl:html", $this->object->getId());

				$questiontext = str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $questiontext);
				$options->setSpecificFeedback(str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $options->getSpecificFeedback()));
				$options->setPRTCorrect(str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $options->getPRTCorrect()));
				$options->setPRTIncorrect(str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $options->getPRTIncorrect()));
				$options->setPRTPartiallyCorrect(str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $options->getPRTPartiallyCorrect()));
				$extra_info->setHowToSolve(str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $extra_info->getHowToSolve()));
				foreach ($prts as $prt_name => $prt) {
					foreach ($prt->getPRTNodes() as $node_name => $node) {
						$node->setFalseFeedback(str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $node->getFalseFeedback()));
						$node->setTrueFeedback(str_replace("src=\"" . $mob["mob"] . "\"", "src=\"" . "il_" . IL_INST_ID . "_mob_" . $media_object->getId() . "\"", $node->getTrueFeedback()));
					}
				}
			}
		}
		$this->object->setQuestion(ilRTE::_replaceMediaObjectImageSrc($questiontext, 1));
		$options->setSpecificFeedback(ilRTE::_replaceMediaObjectImageSrc($options->getSpecificFeedback(), 1));
		$options->setPRTCorrect(ilRTE::_replaceMediaObjectImageSrc($options->getPRTCorrect(), 1));
		$options->setPRTIncorrect(ilRTE::_replaceMediaObjectImageSrc($options->getPRTIncorrect(), 1));
		$options->setPRTPartiallyCorrect(ilRTE::_replaceMediaObjectImageSrc($options->getPRTPartiallyCorrect(), 1));
		$extra_info->setHowToSolve(ilRTE::_replaceMediaObjectImageSrc($extra_info->getHowToSolve(), 1));
		foreach ($prts as $prt_name => $prt) {
			foreach ($prt->getPRTNodes() as $node_name => $node) {
				$node->setFalseFeedback(ilRTE::_replaceMediaObjectImageSrc($node->getFalseFeedback(), 1));
				$node->setTrueFeedback(ilRTE::_replaceMediaObjectImageSrc($node->getTrueFeedback(), 1));
			}
		}

		// now save the question as a whole
		$this->object->saveToDb("", TRUE);

		if ($tst_id > 0) {
			$q_1_id = $this->object->getId();
			$question_id = $this->object->duplicate(true, null, null, null, $tst_id);
			$tst_object->questions[$question_counter++] = $question_id;
			$import_mapping[$item->getIdent()] = array("pool" => $q_1_id, "test" => $question_id);
		} else {
			$import_mapping[$item->getIdent()] = array("pool" => $this->object->getId(), "test" => 0);
		}
	}
}
