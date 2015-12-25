<?php
/**
 * Class Block_Manager
 *
 * Basic block manager. Extends {@link GridField} to allow to easy creation.
 */
class Block_Manager extends GridField
{
            
    /**
     * Create general GridField for Blocks
     */
    public function __construct($name, $title = null, SS_List $dataList = null, GridFieldConfig $config = null)
    {
        if (!$config) {
            $config = GridFieldConfig::create()->addComponents(
                new GridFieldToolbarHeader(),
                new GridFieldAddNewButton('toolbar-header-right'),
                new GridFieldSortableHeader(),
                new GridFieldDataColumns(),
                new GridFieldPaginator(20),
                new GridFieldEditButton(),
                new GridFieldDeleteAction(),
                new GridFieldDetailForm()
            );
            if (count($dataList) > 1 && class_exists('GridFieldSortableRows')) {
                $config->addComponent(new GridFieldSortableRows('SortOrder'));
            }
        }
        parent::__construct($name, $title, $dataList, $config);
    }
}
