<?php
/**
 * Class FeedBlock_GridFieldDetailForm
 *
 * Extends {@link GridFieldDetailForm} to provide a refresh action for FeedBlock
 */
class FeedBlock_GridFieldDetailForm extends GridFieldDetailForm
{
}

class FeedBlock_GridFieldDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest
{

    public static $allowed_actions = array(
        'refresh'
    );

    /**
     * Refresh RSS Feed through AJAX-call
     *
     * @param $request
     * @return string (int)
     */
    public function refresh($request)
    {
        if (empty($this->record)) {
            return '0';
        }
        if ($this->record->ID !== 0) {
            return $this->record->refresh() ? '1' : '0';
        }
        return '0';
    }
}
