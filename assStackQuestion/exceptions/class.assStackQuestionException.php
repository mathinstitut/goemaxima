<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Exceptions/classes/class.ilException.php';

/**
 * Class for advanced editing exception handling in ILIAS.
 *
 * @author Michael Jansen <mjansen@databay.de>
 * @version $Id$
 *
 */
class assStackQuestionException extends ilException
{
	/**
	 * Constructor
	 *
	 * A message is not optional as in build in class Exception
	 *
	 * @param        string $a_message message
	 */
	public function __construct($a_message)
	{
		if (DEVMODE)
		{
			foreach (debug_backtrace() as $step)
			{
				if ($i > 0)
				{
					$backtrace .= '['.$i.'] '.$step['file'].' '.$step['line'].': '.$step['function']."()\n";
				}
				$i++;
			}
			$backtrace .= '['.$i.'] '.$_SERVER['REQUEST_URI'];

			$a_message = $a_message."\n".$backtrace;
			parent::__construct($a_message);
		}
		else
		{
			parent::__construct($a_message);
		}
	}
}
?>