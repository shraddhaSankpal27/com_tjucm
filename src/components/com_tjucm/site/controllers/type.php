<?php
/**
 * @package     TJ-UCM
 * @subpackage  com_tjucm
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Type controller class.
 *
 * @since  __DEPLOY_VERSION__
 */
class TjucmControllerType extends JControllerForm
{
	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->view_list = 'types';
		parent::__construct();
	}

	/**
	 * Method to check the compatibility between ucm types
	 *
	 * @return  mixed
	 * 
	 * @since    __DEPLOY_VERSION__
	 */
	public function getCompatableUcmType()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app 	= Factory::getApplication();
		$post 	= $app->input->post;
		$client = $post->get('client', '', 'STRING');

		if (empty($client))
		{
			echo new JResponseJson(null);
			$app->close();
		}

		JLoader::import('components.com_tjucm.models.types', JPATH_ADMINISTRATOR);
		$typesModel = BaseDatabaseModel::getInstance('Types', 'TjucmModel');
		$typesModel->setState('filter.state', 1);
		$ucmTypes 	= $typesModel->getItems();

		JLoader::import('components.com_tjucm.models.type', JPATH_ADMINISTRATOR);
		$typeModel = BaseDatabaseModel::getInstance('Type', 'TjucmModel');

		$validUcmType = array();
		$validUcmType[0]['value'] = "";
		$validUcmType[0]['text'] = Text::_('COM_TJUCM_SELECT_UCM_TYPE_DESC');

		foreach ($ucmTypes as $key => $type)
		{
			$result = $typeModel->getCompatableUcmType($client, $type->unique_identifier);

			if ($result)
			{
				$validUcmType[$key]['value'] = $type->unique_identifier;
				$validUcmType[$key]['text']  = $type->title;
			}
		}

		echo new JResponseJson($validUcmType);
		$app->close();
	}
}
