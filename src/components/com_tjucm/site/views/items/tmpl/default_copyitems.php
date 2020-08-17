<?php
/**
 * @package    TJ-UCM
 * 
 * @author     TechJoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

Factory::getDocument()->addScriptDeclaration("
	jQuery(window).load(function()
	{
		var currentUcmType = new FormData();
		currentUcmType.append('client', '"  . $this->client . "');
		var afterCheckCompatibilityOfUcmType = function(error, response){
			response = JSON.parse(response);

			if (response.data)
			{
				jQuery.each(response.data, function(key, value) {
				 jQuery('#ucm_list').append(jQuery('<option></option>').attr('value',value.value).text(value.text)); 
				 jQuery('#ucm_list').trigger('liszt:updated');
				});
			}
			else
			{
				jQuery('.ucmListField').addClass('hide');
			}
		};
		
		// Code to check ucm type compatibility to copy item
		com_tjucm.Services.Items.chekCompatibility(currentUcmType, afterCheckCompatibilityOfUcmType);

		var afterGetClusterField = function(error, response){
			response = JSON.parse(response);
			if (response.data != null)
			{
				jQuery.each(response.data, function(key, value) {
				 jQuery('#cluster_list').append(jQuery('<option></option>').attr('value',value.value).text(value.text)); 
				 jQuery('#cluster_list').trigger('liszt:updated');
				});
			}
			else
			{
				jQuery('.clusterListField').addClass('hide');
			}
		};
		com_tjucm.Services.Items.getClusterField(currentUcmType, afterGetClusterField);
	});
	
	function copyItem()
	{
		var afterCopyItem = function(error, response){
			response = JSON.parse(response);
			console.log(response);
			if(response.data !== null)
			{
				Joomla.renderMessages({'success':[response.message]});
			}
			else
			{
				Joomla.renderMessages({'error':[response.message]});
			}
		}
	
		var copyItemData =  jQuery('#adminForm').serialize();

		// Code to copy item to ucm type
		com_tjucm.Services.Items.copyItem(copyItemData, afterCopyItem);
	}
");
?>
<!--
<form action="" id="adminForm" name="adminForm" method="post" enctype="multipart/form-data">
-->
	<div>
		<div class="col-sm-4 ucmListField">
			<label class="control-label"><strong><?php echo Text::_('COM_TJUCM_COPY_ITEMS_SELECT_UCM_TYPE'); ?></strong></label>
			<?php echo JHTML::_('select.genericlist', '', 'filter[ucm_list]', 'class="ucm_list" onchange=""', 'text', 'value', $this->state->get('filter.ucm_list'), 'ucm_list' ); ?>
		</div>
		<div class="col-sm-4 clusterListField">
			<label class="control-label"><strong><?php echo Text::_('COM_TJUCM_COPY_ITEMS_SELECT_CLUSTER'); ?></strong></label>
			<?php echo JHTML::_('select.genericlist', '', 'filter[cluster_list]', 'class="cluster_list" onchange=""', 'text', 'value', $this->state->get('filter.cluster_list'), 'cluster_list' ); ?>
		</div>
		<div class="col-sm-8">
			<div>&nbsp;</div>
			<button class="btn btn-primary" onclick="copyItem()">
				<i class="fa fa-clone"></i>
				<?php echo Text::_('COM_TJUCM_COPY_ITEMS_BUTTON'); ?>
			</button>
		</div>
		<div class="clearfix">&nbsp;</div>
		<div class="col-sm-12">
			<div id="records-import-msg" style="display:none;" class="alert alert-info"><?php echo Text::_("COM_TJUCM_ITEMS_IMPORTING_MSG")?></div>
		</div>
	</div>
<!--
	<input type="hidden" name="client" value="<?php echo $this->client;?>"/>
-->
	<input type="hidden" name="option" value="com_tjucm"/>
<!--
	<input type="hidden" name="task" value="itemform.copyItem"/>
-->
	<?php //echo HTMLHelper::_('form.token'); ?>
<!--
</form>
-->
