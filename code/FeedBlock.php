<?php
/**
 * Class FeedBlock
 *
 * Extends {@link Block} to facilitate RSS feeds
 */
class FeedBlock extends Block
{

    /**
     * Fields
     *
     * @var array
     */
    private static $db = array(
        'FeedURL'            => 'Text',
        'Results'            => 'Int',
        'SummaryMaxLength'        => 'Int',
        'CacheTime'            => 'Int',
        'Striptags'            => 'Int',
        'Modifier'            => 'Text',
    );

    /**
     * Summary fields for GridField
     *
     * @var array
     */
    private static $summary_fields = array(
        'Thumbnail'        => 'Image',
        'Title'            => 'Title',
        'FeedURL'        => 'URL',
        'ItemsInFeed'        => 'Items in feed',
    );

    /**
     * Get CMS fields
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = new FieldList();
        
        $fields->push(new TextField('Title', _t('Block.TITLE', 'Title')));

        $imageField = new UploadField('Image', _t('Block.IMAGE', 'Image'));
        $imageField->getValidator()->setAllowedExtensions(array('jpg', 'gif', 'png'));
        $fields->push($imageField);

        $fields->push(new TextField('FeedURL', _t('FeedBlock.FEEDURL', 'FeedURL')));
        $fields->push(new NumericField('Results', _t('FeedBlock.RESULTS', 'Results')));
        $fields->push(new NumericField('SummaryMaxLength', _t('FeedBlock.SUMMARYMAXLENGTH', 'SummaryMaxLength')));
        $fields->push(new NumericField('CacheTime', _t('FeedBlock.CACHETIME', 'CacheTime')));
        $fields->push(new CheckboxField('Striptags', _t('FeedBlock.STRIPTAGS', 'Striptags')));

        // Add modifier field (select function to run feed item through before displaying it)
        if ($this->modifier_functions) {
            if (isset($this->modifier_functions)) {
                $options = array('' => 'None');
                foreach ($this->modifier_functions as $f) {
                    $options[$f] = $f;
                }
                $fields->push(new DropdownField('Modifier', _t('FeedBlock.MODIFIER', 'Feed item filter'), $options));
            }
        }

        $fields->push(new TextField('LinkExternal', _t('FeedBlock.LINKEXTERNAL', 'External link URL')));

        if (class_exists('OptionalTreeDropdownField')) {
            $treeField = new OptionalTreeDropdownField('LinkInternalID', _t('Block.LINKINTERNAL', 'Internal link'), 'SiteTree');
            $treeField->setEmptyString('No page');
        } else {
            $treeField = new TreeDropdownField('LinkInternalID', _t('Block.LINKINTERNAL', 'Internal link'), 'SiteTree');
        }
        $fields->push($treeField);

        return $fields;
    }

    /**
     * Get block link url
     *
     * @return string
     */
    public function URL()
    {
        return parent::LinkURL();
    }
    
    /**
     * Get block link is external
     *
     * @return bool
     */
    public function IsExternal()
    {
        return parent::LinkIsExternal();
    }

    /**
     * Get block link is internal
     *
     * @return bool
     */
    public function IsInternal()
    {
        return !self::IsExternal();
    }
    
    /**
     * Load feed xml and return items
     * 
     * @param boolean $refresh
     * @return ArrayList or boolean false
     */
    public function Items($refresh=false)
    {
        if (!$this->FeedURL) {
            return false;
        }
        if (!$xml = $this->loadXml()) {
            return false;
        }

        $result = new ArrayList;
        $counter = (int) $this->Results;
        if (!$counter) {
            $counter = -1;
        } // Return all posts

        foreach ($xml->channel->item as $item) {
            // Date
            $date = new SS_Datetime('Date');
            $date->setValue((string) $item->pubDate);
            // Title
            $itemTitle = (string) $item->title;
            $itemTitle = html_entity_decode($itemTitle);
            $title = new Text('Title');
            $title->setValue($itemTitle);
            // Description
            $itemDescription = (string) $item->description;
            $itemDescription = html_entity_decode($itemDescription);
            $description = new Text('Description');
            if ($this->Striptags) {
                $itemDescription = strip_tags($itemDescription);
            }
            $description->setValue($itemDescription);
            // Link
            $link = (string) $item->link;
            // Summary
            $summary = $itemDescription;
            $summary = strip_tags($summary);
            $summary = trim($summary);
            // Get first paragraph
            $summary = explode("\n", $summary);
            $summary = array_shift($summary);
            // Truncate summary if necessary
            $maxLength = (int) $this->SummaryMaxLength;
            if ($maxLength && strlen($summary) > $maxLength) {
                $summary = substr($summary, 0, $maxLength) . '...';
            }
            // Add to result list
            $result->push(new ArrayData(array(
                'Title'            => $title,
                'Date'            => $date,
                'Link'            => $link,
                'Description'        => $description,
                'Summary'        => $summary,
            )));
            if ($counter) {
                $counter--;
                if (!$counter) {
                    break;
                }
            }
        }
        // Apply custom modifier function to each entry
        if (
            count($result)
            && !empty($this->Modifier)
            && $this->ClassName != __CLASS__
            && method_exists($this->ClassName, $this->Modifier)
        ) {
            try {
                $m = $this->Modifier;
                foreach ($result as $item) {
                    $this->$m($item);
                }
            } catch (Exception $e) {
                // Ignore
            }
        }
        // Return items
        return $result;
    }
    
    /**
     * Get number of items in feed
     *
     * @return string
     */
    public function ItemsInFeed()
    {
        return count($this->Items());
    }

    /**
     * Load RSS Feed XML
     *
     * @param bool $refresh
     * @return bool|SimpleXMLElement
     */
    private function loadXml($refresh=false)
    {
        if (!$this->FeedURL) {
            return false;
        }
        $cacheKey = md5($this->FeedURL);
        // Get the Zend Cache to load/store cache into
        $cache = SS_Cache::factory('FeedBlock_xml_', 'Output', array(
            'automatic_serialization' => false,
            'lifetime' => $this->CacheTime
        ));
        if ((int)$this->CacheTime) {
            // Unless force refreshing, try loading from cache
            if (!$refresh) {
                if ($xml = $cache->load($cacheKey)) {
                    return simplexml_load_string($xml);
                }
            }
        }
        // Load feed and cache it
        $xml = file_get_contents($this->FeedURL);
        if (!empty($xml)) {
            try {
                $xmlObj = @simplexml_load_string($xml);
            } catch (Exception $e) {
                return false;
            }
            if (isset($xmlObj->channel)) {
                $cache->save($xml, $cacheKey, array('FeedBlock'), $this->CacheTime);
                return $xmlObj;
            }
        }
        return false;
    }

    /**
     * Refresh feed
     */
    public function refresh()
    {
        if ($xml = $this->loadXml(true)) {
            return get_class($xml) === 'SimpleXMLElement';
        }
        return false;
    }
}
