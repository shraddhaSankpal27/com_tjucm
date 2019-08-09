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

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Tjucm records.
 *
 * @since  1.6
 */
class TjucmModelItems extends JModelList
{
	private $client;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'ordering', 'a.ordering',
				'state', 'a.state',
				'type_id', 'a.type_id',
				'created_by', 'a.created_by',
				'created_date', 'a.created_date',
				'modified_by', 'a.modified_by',
				'modified_date', 'a.modified_date',
			);
		}

		$this->loginuserid = JFactory::getUser()->id;

		$this->fields_separator = "#:";
		$this->records_separator = "#=>";

		// Load fields model
		JLoader::import('components.com_tjfields.models.fields', JPATH_ADMINISTRATOR);

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since    1.6
	 */
	protected function populateState($ordering = "a.id", $direction = "DESC")
	{
		$app  = JFactory::getApplication();
		$user = JFactory::getUser();
		$db = JFactory::getDbo();

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'STRING');
		$this->setState('filter.search', $search);

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/models');
		$tjUcmModelType = JModelLegacy::getInstance('Type', 'TjucmModel');

		$typeId = $app->input->get('id', "", "INT");

		JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjucm/tables');
		$typeTable = JTable::getInstance('Type', 'TjucmTable', array('dbo', $db));
		$typeTable->load(array('id' => $typeId));
		$ucmType = $typeTable->unique_identifier;

		if (empty($ucmType))
		{
			// Get the active item
			$menuitem   = $app->getMenu()->getActive();

			// Get the params
			$this->menuparams = $menuitem->params;

			if (!empty($this->menuparams))
			{
				$this->ucm_type   = $this->menuparams->get('ucm_type');

				if (!empty($this->ucm_type))
				{
					$ucmType     = 'com_tjucm.' . $this->ucm_type;
				}
			}
		}

		if (empty($ucmType))
		{
			// Get UCM type id from uniquue identifier
			$ucmType = $app->input->get('client', '', 'STRING');
		}

		if (empty($typeId))
		{
			$typeId = $tjUcmModelType->getTypeId($ucmType);
		}

		$clusterId = $app->getUserStateFromRequest($this->context . '.cluster', 'cluster');

		if ($clusterId)
		{
			$this->setState('filter.cluster_id', $clusterId);
		}

		$this->setState('ucm.client', $ucmType);
		$this->setState("ucmType.id", $typeId);

		$createdBy = $app->input->get('created_by', "", "INT");
		$canView = $user->authorise('core.type.viewitem', 'com_tjucm.type.' . $typeId);

		if (!$canView)
		{
			$createdBy = $user->id;
		}

		$this->setState("created_by", $createdBy);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   JDatabaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$group_concat = 'GROUP_CONCAT(CONCAT_WS("' . $this->fields_separator . '",
		' . $db->quoteName('fields.id') . ',' . $db->quoteName('fieldValue.value') . ')';

		$group_concat .= 'SEPARATOR "' . $this->records_separator . '") AS field_values';

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'DISTINCT ' . $db->quoteName('a.id') . ', '
				. $db->quoteName('a.state') . ', '
				. $db->quoteName('a.cluster_id') . ', '
				. $db->quoteName('a.created_by') . ',' . $group_concat
			)
		);

		$query->from($db->quoteName('#__tj_ucm_data', 'a'));

		// Join over the users for the checked out user
		$query->select($db->quoteName('uc.name', 'uEditor'));
		$query->join("LEFT", $db->quoteName('#__users', 'uc') . ' ON (' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out') . ')');

		// Join over the foreign key 'type_id'
		$query->join("INNER", $db->quoteName('#__tj_ucm_types', 'types') .
		' ON (' . $db->quoteName('types.id') . ' = ' . $db->quoteName('a.type_id') . ')');
		$query->where('(' . $db->quoteName('types.state') . ' IN (1))');

		// Join over the user field 'created_by'

		$query->select($db->quoteName('ucby.name', 'created_by_name'));
		$query->join("INNER", $db->quoteName('#__users', 'ucby') . ' ON (' . $db->quoteName('ucby.id') .
		' = ' . $db->quoteName('a.created_by') . ')');

		// Join over the user field 'modified_by'
		$query->select($db->quoteName('um.name', 'modified_by_name'));
		$query->join("LEFT", $db->quoteName('#__users', 'um') .
		' ON (' . $db->quoteName('um.id') . ' = ' . $db->quoteName('a.modified_by') . ')');

		// Join over the tjfield
		$query->join("INNER", $db->quoteName('#__tjfields_fields', 'fields') .
		' ON (' . $db->quoteName('fields.client') . ' = ' . $db->quoteName('a.client') . ')');

		// Join over the tjfield value
		$query->join("INNER", $db->quoteName('#__tjfields_fields_value', 'fieldValue') .
		' ON (' . $db->quoteName('fieldValue.content_id') . ' = ' . $db->quoteName('a.id') . ')');

		$this->client = $this->getState('ucm.client');

		if (!empty($this->client))
		{
			$query->where($db->quoteName('a.client') . ' = ' . $db->quote($db->escape($this->client)));
		}

		$query->where($db->quoteName('fields.id') . ' = ' . $db->quoteName('fieldValue.field_id'));

		$ucmType = $this->getState('ucmType.id', '', 'INT');

		if (!empty($ucmType))
		{
			$query->where($db->quoteName('a.type_id') . ' = ' . (INT) $ucmType);
		}

		$createdBy = $this->getState('created_by', '', 'INT');

		if (!empty($createdBy))
		{
			$query->where($db->quoteName('a.created_by') . ' = ' . (INT) $createdBy);
		}

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where($db->quoteName('a.state') . ' = ' . (INT) $published);
		}
		elseif ($published === '')
		{
			$query->where(($db->quoteName('(a.state) ') . ' IN (0, 1)'));
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->escape(trim($search), true);

			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->quoteName('a.id') . ' = ' . (int) $search);
			}
			else
			{
				$fields = $this->getFields();
				$filterFieldFound = 0;
				$searchString = '';

				if (!empty($fields))
				{
					$fieldCount = 0;

					foreach ($fields as $fieldId => $field)
					{
						$fieldCount++;
						$searchString .= $db->quoteName('field_values') . ' LIKE ' . $db->q('%' . $fieldId . '#:' . $search . '%');

						if ($fieldCount < count($fields))
						{
							$searchString .= ' OR ';
						}

						// For field specific search
						if (stripos($search, $field . ':') === 0)
						{
							$search = trim(str_replace($field . ':', '', $search));
							$query->having($db->quoteName('field_values') . ' LIKE ' . $db->q('%' . $fieldId . '#:' . $search . '%'));
							$filterFieldFound = 1;

							break;
						}
					}
				}

				// For generic search
				if ($filterFieldFound == 0)
				{
					$query->having($searchString);
				}
			}
		}

		// Filter by cluster
		$clusterId = (int) $this->getState('filter.cluster_id');

		if ($clusterId)
		{
			$query->where($db->quoteName('a.cluster_id') . ' = ' . $clusterId);
		}

		$query->group('fieldValue.content_id');

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Get an array of data items
	 *
	 * @return mixed Array of data items on success, false on failure.
	 */
	public function getFields()
	{
		JLoader::import('components.com_tjfields.models.fields', JPATH_ADMINISTRATOR);
		$items_model = JModelLegacy::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$items_model->setState('filter.showonlist', 1);
		$items_model->setState('filter.state', 1);
		$this->client = $this->getState('ucm.client');

		if (!empty($this->client))
		{
			$items_model->setState('filter.client', $this->client);
		}

		$items = $items_model->getItems();

		$data = array();

		foreach ($items as $item)
		{
			$data[$item->id] = $item->label;
		}

		return $data;
	}

	/**
	 * Get an array of data items
	 *
	 * @return mixed Array of data items on success, false on failure.
	 */
	public function getItems()
	{
		$typeId = $this->getState('ucmType.id');
		$createdBy = $this->getState('created_by', '');

		JLoader::import('components.com_tjucm.models.item', JPATH_SITE);
		$itemModel = new TjucmModelItem;
		$canView = $itemModel->canView($typeId);
		$user = JFactory::getUser();

		// If user is not allowed to view the records and if the created_by is not the logged in user then do not show the records
		if (!$canView)
		{
			if (!empty($createdBy) && $createdBy == $user->id)
			{
				$canView = true;
			}
		}

		if (!$canView)
		{
			return false;
		}

		$items = parent::getItems();

		foreach ($items as $item)
		{
			if (!empty($item->field_values))
			{
				$explode_field_values = explode($this->records_separator, $item->field_values);

				$colValue = array();

				foreach ($explode_field_values as $field_values)
				{
					$explode_explode_field_values = explode($this->fields_separator, $field_values);

					$fieldId = $explode_explode_field_values[0];
					$fieldValue = $explode_explode_field_values[1];

					$colValue[$fieldId] = $fieldValue;
				}

				$listcolumns = $this->getFields();

				if (!empty($listcolumns))
				{
					$fieldData = array();

					foreach ($listcolumns as $col_id => $col_name)
					{
						if (array_key_exists($col_id, $colValue))
						{
							$fieldData[$col_id] = $colValue[$col_id];
						}
						else
						{
							$fieldData[$col_id] = "";
						}

						$item->field_values = $fieldData;
					}
				}
			}
		}

		return $items;
	}

	/**
	 * Overrides the default function to check Date fields format, identified by
	 * "_dateformat" suffix, and erases the field if it's not correct.
	 *
	 * @return void
	 */
	protected function loadFormData()
	{
		$app              = JFactory::getApplication();
		$filters          = $app->getUserState($this->context . '.filter', array());
		$error_dateformat = false;

		foreach ($filters as $key => $value)
		{
			if (strpos($key, '_dateformat') && !empty($value) && $this->isValidDate($value) == null)
			{
				$filters[$key]    = '';
				$error_dateformat = true;
			}
		}

		if ($error_dateformat)
		{
			$app->enqueueMessage(JText::_("COM_TJUCM_SEARCH_FILTER_DATE_FORMAT"), "warning");
			$app->setUserState($this->context . '.filter', $filters);
		}

		return parent::loadFormData();
	}

	/**
	 * Checks if a given date is valid and in a specified format (YYYY-MM-DD)
	 *
	 * @param   string  $date  Date to be checked
	 *
	 * @return bool
	 */
	private function isValidDate($date)
	{
		$date = str_replace('/', '-', $date);

		return (date_create($date)) ? JFactory::getDate($date)->format("Y-m-d") : null;
	}

	/**
	 * Method to getAliasFieldNameByView
	 *
	 * @param   array  $view  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   12.2
	 */
	public function getAliasFieldNameByView($view)
	{
		switch ($view)
		{
			case 'items':
				return 'alias';
			break;
		}
	}

	/**
	 * Get an item by alias
	 *
	 * @param   string  $alias  Alias string
	 *
	 * @return int Element id
	 */
	public function getItemIdByAlias($alias)
	{
		$db = JFactory::getDbo();
		$table = JTable::getInstance('type', 'TjucmTable', array('dbo', $db));

		$table->load(array('alias' => $alias));

		return $table->id;
	}

	/**
	 * Check if there are fields to show in list view
	 *
	 * @param   string  $client  Client
	 *
	 * @return boolean
	 */
	public function showListCheck($client)
	{
		if (!empty($client))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select("count(" . $db->quoteName('id') . ")");
			$query->from($db->quoteName('#__tjfields_fields'));
			$query->where($db->quoteName('client') . '=' . $db->quote($client));
			$query->where($db->quoteName('showonlist') . '=1');
			$db->setQuery($query);

			$result = $db->loadResult();

			return $result;
		}
		else
		{
			return false;
		}
	}
}
