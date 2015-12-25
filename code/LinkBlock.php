<?php
/**
 * Class FeedBlock
 *
 * Extends {@link Block} to facilitate links
 */
class LinkBlock extends Block
{
    
    /**
     * Fields
     *
     * @var array
     */
    private static $db = array(
        'CssClasses'    => 'Text',
        'Attributes'    => 'Text',
    );

    /**
     * Summary fields for GridField
     *
     * @var array
     */
    private static $summary_fields = array(
        'Thumbnail'        => 'Image',
        'Title'            => 'Title',
        'URL'            => 'URL',
    );

    /**
     * Get CMS fields
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = new FieldList();

        $fields->push(new TextField('LinkTitle', _t('Block.LINKTITLE', 'Link title')));
        $fields->push(new TextField('LinkExternal', _t('Block.LINKEXTERNAL', 'External link URL')));

        if (class_exists('OptionalTreeDropdownField')) {
            $treeField = new OptionalTreeDropdownField('LinkInternalID', _t('Block.LINKINTERNAL', 'Internal link'), 'SiteTree');
            $treeField->setEmptyString('No Page');
        } else {
            $treeField = new TreeDropdownField('LinkInternalID', _t('Block.LINKINTERNAL', 'Internal link'), 'SiteTree');
        }
        $fields->push($treeField);

        $fields->push(new TextField('CssClasses', _t('Block.CSSCLASSES', 'Css Classes')));
        $fields->push(new TextareaField('Attributes', _t('Block.ATTRIBUTES', 'Attributes')));

        $imageField = new UploadField('Image', _t('Block.IMAGE', 'Image'));
        $imageField->getValidator()->setAllowedExtensions(array('jpg', 'gif', 'png'));
        $fields->push($imageField);

        return $fields;
    }

    /**
     * Get link url
     *
     * @return string
     */
    public function URL()
    {
        return parent::LinkURL();
    }
    
    /**
     * Get link title
     *
     * @return string
     */
    public function Title()
    {
        return parent::LinkTitle();
    }
    
    /**
     * Get link is external
     *
     * @return bool
     */
    public function IsExternal()
    {
        return parent::LinkIsExternal();
    }

    /**
     * Get link is internal
     *
     * @return bool
     */
    public function IsInternal()
    {
        return !self::IsExternal();
    }
}
