<?php
/**
 * Class ContactBlock
 *
 * Extends {@link Block} to facilitate information for a contact
 */
class ContactBlock extends Block
{
    
    /**
     * Fields
     *
     * @var array
     */
    private static $db = array(
        'Role'            => 'Text',
        'Email'            => 'Text',
        'Phone'            => 'Text',
        'Mobile'        => 'Text',
        'Street'        => 'Text',
        'Zip'            => 'Text',
        'City'            => 'Text',
        'Box'            => 'Text',
    );

    /**
     * Summary field for GridField
     *
     * @var array
     */
    private static $summary_fields = array(
        'Thumbnail'        => 'Image',
        'Title'            => 'Name',
        'Email'            => 'Email',
        'Phone'            => 'Phone',
        'Mobile'        => 'Mobile',
        'Address'        => 'Address',
    );

    /**
     * Get CMS fields
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = new FieldList();

        $fields->push(new TextField('Title', _t('ContactBlock.NAME', 'Name')));
        $fields->push(new TextField('Role', _t('ContactBlock.ROLE', 'Role')));
        $fields->push(new EmailField('Email', _t('ContactBlock.EMAIL', 'Email')));
        $fields->push(new TextField('Phone', _t('ContactBlock.PHONE', 'Phone')));
        $fields->push(new TextField('Mobile', _t('ContactBlock.MOBILE', 'Mobile')));
        $fields->push(new TextField('Street', _t('ContactBlock.STREET', 'Street')));
        $fields->push(new TextField('Zip', _t('ContactBlock.ZIP', 'Zip')));
        $fields->push(new TextField('City', _t('ContactBlock.CITY', 'City')));
        $fields->push(new TextField('Box', _t('ContactBlock.BOX', 'Box')));

        $imageField = new UploadField('Image', _t('Block.IMAGE', 'Image'));
        $imageField->getValidator()->setAllowedExtensions(array('jpg', 'gif', 'png'));
        $fields->push($imageField);

        return $fields;
    }
    
    /**
     * Get contact name (alias)
     *
     * @return string
     */
    public function Name()
    {
        return $this->Title;
    }
    
    /**
     * Get compiled address (for use with GridField primarily)
     *
     * @return string
     */
    public function Address()
    {
        $city = array();
        if ($this->Zip) {
            $city[] = $this->Zip;
        }
        if ($this->City) {
            $city[] = $this->City;
        }
        $address = array();
        if ($this->Street) {
            $address[] = $this->Street;
        }
        $address[] = join(' ', $city);
        if ($this->Box) {
            $address[] = $this->Box;
        }
        return join(', ', $address);
    }
}
