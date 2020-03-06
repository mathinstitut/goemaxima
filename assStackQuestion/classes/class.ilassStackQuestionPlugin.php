<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE 
 */
require_once "./Modules/TestQuestionPool/classes/class.ilQuestionsPlugin.php";

/**
 * STACK Question plugin for ILIAS 4.4+
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id$
 *
 */
class ilassStackQuestionPlugin extends ilQuestionsPlugin
{

    final function getPluginName()
    {
        return "assStackQuestion";
    }

    final function getQuestionType()
    {
        return "assStackQuestion";
    }

    final function getQuestionTypeTranslation()
    {
        return $this->txt($this->getQuestionType());
    }

}

?>