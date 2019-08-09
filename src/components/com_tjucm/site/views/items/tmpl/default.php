<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

use Joomla\CMS\Component\ComponentHelper;

$user = JFactory::getUser();
$userId = $user->get('id');
$tjUcmFrontendHelper = new TjucmHelpersTjucm;
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
$appendUrl = '';
$csrf = "&" . JSession::getFormToken() . '=1';

if (!empty($this->created_by))
{
	$appendUrl .= "&created_by=" . $this->created_by;
}

if (!empty($this->client))
{
	$appendUrl .= "&client=" . $this->client;
}

$tmpListColumn = $this->listcolumn;
reset($tmpListColumn);
$firstListColumn = key($tmpListColumn);

$link = 'index.php?option=com_tjucm&view=items' . $appendUrl;
$itemId = $tjUcmFrontendHelper->getItemId($link);
?>
<form action="<?php echo JRoute::_($link . '&Itemid=' . $itemId); ?>" method="post" name="adminForm" id="adminForm">
	<div id="filter-progress-bar">
		<div class="pull-left">
			<input type="text" name="filter_search" id="filter_search"
				title="<?php echo empty($firstListColumn) ? JText::_('JSEARCH_FILTER') : JText::sprintf('COM_TJUCM_ITEMS_SEARCH_TITLE', $this->listcolumn[$firstListColumn]); ?>"
				value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
				placeholder="<?php echo JText::_('JSEARCH_FILTER'); ?>"/>
		</div>
		<div class="pull-left">
			<button class="btn btn-default" type="submit" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><span class="icon-search"></span></button>
			<button class="btn btn-default qtc-hasTooltip" id="clear-search-button" onclick="getElementById('filter_search').value='';this.form.submit();" type="button" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>"><span class="icon-remove"></span></button>
		</div>
		<div class="btn-group pull-right hidden-xs">
			<?php echo $this->pagination->getLimitBox(); ?>
		</div>
		<?php
		// Check if com_cluster component is installed
		if (ComponentHelper::getComponent('com_cluster', true)->enabled)
		{
			JFormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
			$cluster           = JFormHelper::loadFieldType('cluster', false);
			$this->clusterList = $cluster->getOptionsExternally();
			?>
			<div class="btn-group pull-right hidden-xs">
				<?php
					echo JHtml::_('select.genericlist', $this->clusterList, "cluster", 'class="input-medium" size="1" onchange="this.form.submit();"', "value", "text", $this->state->get('filter.cluster_id', '', 'INT'));
				?>
			</div>
			<?php
		}
		?>
	</div>
	<br><br>
	<div>
		<table class="table table-striped" id="itemList">
			<?php
			if (!empty($this->showList))
			{
				if (!empty($this->items))
				{?>
			<thead>
				<tr>
					<?php
					if (isset($this->items[0]->state))
					{
						?>
						<th width="5%">
							<?php echo JHtml::_('grid.sort', 'JPUBLISHED', 'a.state', $listDirn, $listOrder); ?>
						</th>
						<?php
					}
					?>
					<th class=''>
						<?php echo JHtml::_('grid.sort', 'COM_TJUCM_ITEMS_ID', 'a.id', $listDirn, $listOrder); ?>
					</th>
					<?php

					if (!empty($this->listcolumn))
					{
						foreach ($this->listcolumn as $col_name)
						{
							?>
							<th class='left'>
								<?php echo htmlspecialchars($col_name, ENT_COMPAT, 'UTF-8'); ?>
							</th>
							<?php
						}
					}

					if ($this->canEdit || $this->canDelete)
					{
						?>
						<th class="center">
							<?php echo JText::_('COM_TJUCM_ITEMS_ACTIONS'); ?>
						</th>
					<?php
					}
					?>
				</tr>
			</thead>
			<?php
				}
			}?>
			<?php
			if (!empty($this->items))
			{
			?>
			<tfoot>
				<tr>
					<td colspan="<?php echo isset($this->items[0]) ? count($this->items[0]->field_values)+3 : 10; ?>">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<?php
			}
			?>
			<tbody>
			<?php
			if (!empty($this->showList))
			{
				if (!empty($this->items))
				{
					foreach ($this->items as $i => $item)
					{
						$link = JRoute::_('index.php?option=com_tjucm&view=item&id=' . $item->id . "&client=" . $this->client . '&Itemid=' . $itemId, false);

						$editown = false;
						if ($this->canEditOwn)
						{
							$editown = (JFactory::getUser()->id == $item->created_by ? true : false);
						}

						$deleteOwn = false;
						if ($this->canDeleteOwn)
						{
							$deleteOwn = (JFactory::getUser()->id == $item->created_by ? true : false);
						}
						
						?>
						<tr class="row<?php echo $i % 2; ?>">
							<?php
							if (isset($this->items[0]->state))
							{
								$class = ($this->canChange) ? 'active' : 'disabled'; ?>
								<td class="center">
									<a class="<?php echo $class; ?>" href="<?php echo ($this->canChange) ? 'index.php?option=com_tjucm&task=item.publish&id=' . $item->id . '&state=' . (($item->state + 1) % 2) . $appendUrl . $csrf : '#'; ?>">
									<?php
									if ($item->state == 1)
									{
										?><span class="icon-publish"></span><?php
									}
									else
									{
										?><span class="icon-unpublish"></span><?php
									}
									?>
									</a>
								</td>
							<?php
							}
							?>
							<td>
								<?php
								if (isset($item->checked_out) && $item->checked_out)
								{
									echo JHtml::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'items.', $canCheckin);
								}
								?>
								<a href="<?php echo JRoute::_('index.php?option=com_tjucm&view=item&id=' . (int) $item->id . "&client=" . $this->client . '&Itemid=' . $itemId, false); ?>">
									<?php echo $this->escape($item->id); ?>
								</a>
							</td>
							<?php
								if (!empty($item->field_values))
								{
									foreach ($item->field_values as $field_values)
									{
										?>
										<td>
										<?php
										if (is_array(json_decode($field_values, true)))
										{
											$subFormData = json_decode($field_values);

											foreach ($subFormData as $subFormDataRow)
											{
												?>
												<table class="table table-bordered">
												<?php
												foreach ($subFormDataRow as $key => $subFormDataColumn)
												{
													?>
													<tr>
													<?php
													echo !empty($subFormDataColumn) ? '<td>' . $key . '</td><td>' . $subFormDataColumn . '</td>' : '';
													?>
													</tr>
													<?php
												}
												?>
												</table>
												<?php
											}
										}
										else
										{
											?>
											<a href="<?php echo $link;?>">
												<?php echo $field_values; ?>
											</a>
											<?php
										}
										?>
										</td>
										<?php
									}
								}

								if ($this->canEdit || $this->canDelete || $editown || $deleteOwn)
								{
									?>
									<td class="center">
									<?php
									if ($this->canEdit || $editown)
									{
										?>
										<a target="_blank" href="<?php echo 'index.php?option=com_tjucm&task=itemform.edit&id=' . $item->id . $appendUrl; ?>" class="btn btn-mini" type="button"><i class="icon-apply" aria-hidden="true"></i></a>
										<?php
									}
									if ($this->canDelete || $deleteOwn)
									{
										?>
										<a href="<?php echo 'index.php?option=com_tjucm&task=itemform.remove' . '&id=' . $item->id . $appendUrl . $csrf; ?>" class="btn btn-mini delete-button" type="button"><i class="icon-delete" aria-hidden="true"></i></a>
										<?php
									}
									?>
									</td>
								<?php
								}
								?>
						</tr>
					<?php
					}
				}
				else
				{
					?>
					<div class="alert alert-warning"><?php echo JText::_('COM_TJUCM_NO_DATA_FOUND');?></div>
				<?php
				}
			}
			else
			{
			?>
				<div class="alert alert-warning"><?php echo JText::_('COM_TJUCM_NO_DATA_FOUND');?></div>
			<?php
			}
			?>
			</tbody>
		</table>
	</div>
	<?php
	if ($this->allowedToAdd)
	{
		?>
		<a target="_blank" href="<?php echo JRoute::_('index.php?option=com_tjucm&task=itemform.edit' . $appendUrl, false, 2); ?>" class="btn btn-success btn-small">
			<i class="icon-plus"></i><?php echo JText::_('COM_TJUCM_ADD_ITEM'); ?>
		</a>
		<?php
	}
	?>
	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="boxchecked" value="0"/>
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
	<?php echo JHtml::_('form.token'); ?>
</form>
<?php
if ($this->canDelete)
{
	?>
	<script type="text/javascript">
	jQuery(document).ready(function () {
		jQuery('.delete-button').click(deleteItem);
	});

	function deleteItem()
	{
		if (!confirm("<?php echo JText::_('COM_TJUCM_DELETE_MESSAGE'); ?>"))
		{
			return false;
		}
	}
	</script>
<?php
}
