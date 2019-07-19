<?php
/**
 * @package     TJ-UCM
 * @subpackage  com_tjucm
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Updates the database structure of the component
 *
 * @version  Release: 0.2b
 * @author   Component Creator <support@component-creator.com>
 * @since    0.1b
 */
class Com_TjucmInstallerScript
{
	/**
	 * Method called before install/update the component. Note: This method won't be called during uninstall process.
	 *
	 * @param   string  $type    Type of process [install | update]
	 * @param   mixed   $parent  Object who called this method
	 *
	 * @return boolean True if the process should continue, false otherwise
	 */
	public function preflight($type, $parent)
	{
		$jversion = new JVersion;

		// Installing component manifest file version
		$manifest = $parent->get("manifest");
		$release  = (string) $manifest['version'];

		// Abort if the component wasn't build for the current Joomla version
		if (!$jversion->isCompatible($release))
		{
			JFactory::getApplication()->enqueueMessage(
				JText::_('This component is not compatible with installed Joomla version'),
				'error'
			);

			return false;
		}

		return true;
	}

	/**
	 * Method to install the component
	 *
	 * @param   mixed  $parent  Object who called this method.
	 *
	 * @return void
	 *
	 * @since 0.2b
	 */
	public function install($parent)
	{
	}

	/**
	 * Method to update the component
	 *
	 * @param   mixed  $parent  Object who called this method.
	 *
	 * @return void
	 */
	public function update($parent)
	{
	}

	/**
	 * Method to uninstall the component
	 *
	 * @param   mixed  $parent  Object who called this method.
	 *
	 * @return void
	 */
	public function uninstall($parent)
	{
	}
}
