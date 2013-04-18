<?php
/**
 * Class FeedBlock_GridFieldRefreshButton
 *
 * Extends {@link GridFieldEditButton} to provide a refresh-link for rss feeds presented in a {@link GridField}
 */
class FeedBlock_GridFieldRefreshButton extends GridFieldEditButton {
	
	/**
	 *
	 * @param GridField $gridField
	 * @param DataObject $record
	 * @param string $columnName
	 * @return string - the HTML for the column 
	 */
	public function getColumnContent($gridField, $record, $columnName) {
		$data = new ArrayData(array(
			'Link' => Controller::join_links($gridField->Link('item'), $record->ID, 'refresh')
		));
		$modulePath = realpath(__DIR__.'/..');
		$modulePath = explode('/',$modulePath);
		$modulePath = end($modulePath);
		return $data->renderWith('../'.$modulePath.'/templates/GridFieldRefreshButton.ss');
	}

}
