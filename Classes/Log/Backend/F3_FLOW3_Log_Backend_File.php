<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Log\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Log
 * @version $Id$
 */

/**
 * A log backend which writes log entries into a file
 *
 * @package FLOW3
 * @subpackage Log
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @scope prototype
 */
class File extends \F3\FLOW3\Log\AbstractBackend {

	/**
	 * An array of severity labels, indexed by their integer constant
	 * @var array
	 */
	protected $severityLabels;

	/**
	 * @var string
	 */
	protected $logFileURL = '';

	/**
	 * @var resource
	 */
	protected $fileHandle;

	/**
	 * Sets URL pointing to the log file. Usually the full directory and
	 * the filename, however any valid stream URL is possible.
	 *
	 * @param string $logFileURL URL pointing to the log file
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setLogFileURL($logFileURL) {
		$this->logFileURL = $logFileURL;
	}

	/**
	 * Carries out all actions necessary to prepare the logging backend, such as opening
	 * the log file or opening a database connection.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function open() {
		$this->severityLabels = array(
			\F3\FLOW3\Log\LoggerInterface::SEVERITY_EMERGENCY => 'EMERGENCY',
			\F3\FLOW3\Log\LoggerInterface::SEVERITY_ALERT     => 'ALERT    ',
			\F3\FLOW3\Log\LoggerInterface::SEVERITY_CRITICAL  => 'CRITICAL ',
			\F3\FLOW3\Log\LoggerInterface::SEVERITY_ERROR     => 'ERROR    ',
			\F3\FLOW3\Log\LoggerInterface::SEVERITY_WARNING   => 'WARNING  ',
			\F3\FLOW3\Log\LoggerInterface::SEVERITY_NOTICE    => 'NOTICE   ',
			\F3\FLOW3\Log\LoggerInterface::SEVERITY_INFO      => 'INFO     ',
			\F3\FLOW3\Log\LoggerInterface::SEVERITY_DEBUG     => 'DEBUG    ',
		);

		$this->fileHandle = fopen($this->logFileURL, 'at');
		if ($this->fileHandle === FALSE) throw new \F3\FLOW3\Log\Exception\CouldNotOpenResource('Could not open log file "' . $this->logFileURL . '" for write access.', 1229448440);
	}

	/**
	 * Appends the given message along with the additional information into the log.
	 *
	 * @param string $message The message to log
	 * @param integer $severity One of the SEVERITY_* constants
	 * @param mixed $additionalData A variable containing more information about the event to be logged
	 * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
	 * @param string $className Name of the class triggering the log (determined automatically if not specified)
	 * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function append($message, $severity = 6, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL) {
		$severityLabel = (isset($this->severityLabels[$severity])) ? $this->severityLabels[$severity] : 'UNKNOWN  ';
		$output = strftime ('%y-%m-%d %H:%M:%S', time()) . ' ' . $severityLabel . ' ' . str_pad($packageKey, 20) . ' ' . $message . chr(10);
		if (!empty($additionalData)) {
			$output .= $this->getFormattedVarDump($additionalData) . chr(10);
		}
		if ($this->fileHandle !== FALSE) {
			fputs($this->fileHandle, $output);
		}
	}

	/**
	 * Carries out all actions necessary to cleanly close the logging backend, such as
	 * closing the log file or disconnecting from a database.
	 *
	 * @return void
	 */
	public function close() {
		fclose($this->fileHandle);
		$this->fileHandle = FALSE;
	}

	/**
	 * Returns a suitable form of a variable (be it a string, array, object ...) for logfile output
	 *
	 * @param mixed $var The variable
	 * @param integer $spaces Number of spaces to add before a line
	 * @return string text output
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getFormattedVarDump($var, $spaces=4) {
		if ($spaces > 100) {
			return NULL;
		}
		$output = '';
		if (is_array($var)) {
			foreach ($var as $k=>$v) {
				if (is_array($v)) {
					$output .= str_repeat(' ',$spaces) . $k . ' => array (' . chr(10) . $this->getFormattedVarDump($v, $spaces+3) . str_repeat (' ', $spaces) . ')' . chr(10);
				} else {
					if (is_object($v)) {
						$output .= str_repeat(' ', $spaces) . $k . ' => object: ' . get_class($v) . chr(10);
					} else {
						$output .= str_repeat(' ',$spaces) . $k . ' => ' . $v . chr(10);
					}
				}
			}
		} else {
			if (is_object($var)) {
				$output .= str_repeat(' ', $spaces) . ' [ OBJECT: ' . \F3\PHP6\Functions::strtoupper(get_class($var)) . ' ]:' . chr(10);
				if (is_array(get_object_vars ($var))) {
					foreach (get_object_vars ($var) as $objVarName => $objVarValue) {
						if (is_array($objVarValue) || is_object($objVarValue)) {
							$output .= str_repeat(' ', $spaces) . $objVarName . ' => ' . chr(10);
							$output .= $this->getFormattedVarDump($objVarValue, $spaces+3);
						} else {
							$output .= str_repeat(' ', $spaces) . $objVarName . ' => ' . $objVarValue . chr(10);
						}
					}
				}
				$output .= chr(10);
			} else {
				$output .= str_repeat(' ', $spaces) . '=> ' . $var . chr(10);
			}
		}
		return $output;
	}
}
?>