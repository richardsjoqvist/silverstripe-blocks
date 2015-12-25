<?php
/**
 * Class Block
 *
 * Basic block class. This class should be extended for usage in the cms.
 */
class Block extends DataObject
{
    
    /**
     * Fields
     *
     * @var array
     */
    private static $db = array(
        'Title'                => 'Text',
        'LeadIn'            => 'HTMLText',
        'Content'            => 'HTMLText',
        'LinkTitle'            => 'Text',
        'LinkExternal'        => 'Text',
        'SortOrder'            => 'Int',
        'Data'                => 'Text',
    );

    /**
     * Relationships
     *
     * @var array
     */
    private static $has_one = array(
        'Page'                => 'Page',
        'Image'                => 'Image',
        'LinkInternal'        => 'SiteTree',
    );


    /**
     * Summary fields for GridField
     *
     * @var array
     */
    private static $summary_fields = array(
        'Thumbnail'            => 'Image',
        'Title'                => 'Title',
        'LeadInText'        => 'Lead in',
        'ContentText'        => 'Content',
    );

    /**
     * Default sort order
     *
     * @var string
     */
    private static $default_sort = "Block.SortOrder ASC";

    /**
     * Constructor
     *
     * @param array $record
     * @param bool $isSingleton
     */
    public function __construct($record = null, $isSingleton = false)
    {
        // Extract data from legacy Data field (backwards-compatibility)
        if ($record) {
            if ($record['ID'] > 0) {
                $record = (array)$record;
                // Extract data from Data
                if (!empty($record['Data'])) {
                    if ($data = unserialize($record['Data'])) {
                        $setData = true;
                        foreach (array_keys($data) as $fieldname) {
                            if (array_key_exists($fieldname, $record)) {
                                if ($record[$fieldname] != null) {
                                    // Object has been saved in the new version, do not set data
                                    $setData = false;
                                    break;
                                }
                            }
                        }
                        if ($setData) {
                            // Object has not been saved in the new version, set data
                            foreach ($data as $fieldname => $value) {
                                $record[$fieldname] = $value;
                            }
                        }
                    }
                }
            }
        }
        parent::__construct($record, $isSingleton);
    }
    
    /**
     * Get CMS fields
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = new FieldList();
        
        $fields->push(new TextField('Title', _t('Block.TITLE', 'Title')));

        $leadInField = new HTMLEditorField('LeadIn', _t('Block.LEADIN', 'Lead In'));
        $leadInField->setRows(2);
        $fields->push($leadInField);

        $contentField = new HTMLEditorField('Content', _t('Block.CONTENT', 'Content'));
        $contentField->setRows(6);
        $fields->push($contentField);

        $imageField = new UploadField('Image', _t('Block.IMAGE', 'Image'));
        $imageField->getValidator()->setAllowedExtensions(array('jpg', 'gif', 'png'));
        $fields->push($imageField);
        
        $fields->push(new TextField('LinkTitle', _t('Block.LINKTITLE', 'Link title')));
        $fields->push(new TextField('LinkExternal', _t('Block.LINKEXTERNAL', 'External link URL')));

        if (class_exists('OptionalTreeDropdownField')) {
            $treeField = new OptionalTreeDropdownField('LinkInternalID', _t('Block.LINKINTERNAL', 'Internal link'), 'SiteTree');
            $treeField->setEmptyString('(Choose)');
        } else {
            $treeField = new TreeDropdownField('LinkInternalID', _t('Block.LINKINTERNAL', 'Internal link'), 'SiteTree');
        }
        $fields->push($treeField);

        return $fields;
    }

    /**
     * Text-only Lead In
     *
     * @return string
     */
    public function LeadInText()
    {
        if (!$this->LeadIn) {
            return '';
        }
        $str = $this->LeadIn;
        $str = html_entity_decode($str);
        $str = strip_tags($str);
        $str = trim($str);
        return $str;
    }

    /**
     * Text-only Content
     *
     * @return string
     */
    public function ContentText()
    {
        if (!$this->Content) {
            return '';
        }
        $str = $this->Content;
        $str = html_entity_decode($str);
        $str = strip_tags($str);
        $str = trim($str);
        return $str;
    }

    /**
     * Get thumbnail for list in admin
     *
     * @return Image | null
     */
    public function Thumbnail()
    {
        if ($Image = $this->Image()) {
            return $Image->CMSThumbnail();
        }
        return null;
    }
    
    /**
     * Check if this block has a valid link
     *
     * @return bool
     */
    public function HasLink()
    {
        $url = trim($this->LinkExternal);
        return $this->LinkInternalID > 0 || !empty($url);
    }
    
    /**
     * Check if link is external
     *
     * @return bool
     */
    public function LinkIsExternal()
    {
        $url = trim($this->LinkExternal);
        return !empty($url);
    }
    
    /**
     * Get link url
     *
     * @return string
     */
    public function LinkURL()
    {
        $url = trim($this->LinkExternal);
        if ($this->HasLink() && empty($url)) {
            // Internal link
            if ($object = DataObject::get_by_id('SiteTree', $this->LinkInternalID)) {
                return '/'.$object->RelativeLink();
            }
        }
        return $url;
    }
    
    /**
     * Get link title
     *
     * @return string
     */
    public function LinkTitle()
    {
        $title = trim($this->LinkTitle);
        if (empty($title)) {
            return $title;
        }
        if (!$this->LinkIsExternal()) {
            // Internal link
            if ($object = DataObject::get_by_id('SiteTree', $this->LinkInternalID)) {
                return $object->Title;
            }
        } else {
            // Internal link
            return trim($this->Title);
        }
        return '';
    }
    
    /**
     * Get compiled link
     * 
     * @return string
     */
    public function Link()
    {
        if (!$this->HasLink()) {
            return '';
        }
        return '<a href="' . $this->LinkURL() .'" ' . $this->LinkIsExternal()?'class="external"':'' . '>'. $this->LinkTitle() .'</a>';
    }

    /**
     * Get link details
     * 
     * @return string
     */
    public function LinkDetails()
    {
        if (!$this->HasLink()) {
            return '';
        }
        return $this->LinkTitle().': '.$this->LinkURL();
    }
}
