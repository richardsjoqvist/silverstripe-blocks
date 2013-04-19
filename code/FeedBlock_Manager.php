<?php
/**
 * Class FeedBlock_Manager
 *
 * Extends {@link Block_Manager} to facilitate {@link FeedBlock} items.
 */
class FeedBlock_Manager extends GridField
{
			
	/**
	 * Create GridField for FeedBlocks
	 */
	function __construct($name, $title = null, SS_List $dataList = null, GridFieldConfig $config = null) {
		if(!$config) {
			$config = GridFieldConfig::create()->addComponents(
				new GridFieldToolbarHeader(),
				new GridFieldAddNewButton('toolbar-header-right'),
				new GridFieldSortableHeader(),
				new GridFieldDataColumns(),
				new GridFieldPaginator(20),
				new FeedBlock_GridFieldRefreshButton(),
				new GridFieldEditButton(),
				new GridFieldDeleteAction(),
				new FeedBlock_GridFieldDetailForm()
			);
		}
		parent::__construct($name, $title, $dataList, $config);
	}

	/**
	 * Returns the whole gridfield rendered with all the attached components
	 *
	 * @param array $properties
	 * @return string
	 */
	public function FieldHolder($properties = array()) {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-livequery/jquery.livequery.min.js');
		$modulePath = dirname(dirname(__FILE__));
		$modulePath = str_replace(BASE_PATH, '', $modulePath);
		$modulePath = substr($modulePath, 1);
		Requirements::javascript($modulePath . '/js/feedblock_manager.js');
		Requirements::css($modulePath . '/css/GridFieldRefreshButton.css');
		return parent::FieldHolder($properties);
	}

}