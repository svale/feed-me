<?php
namespace verbb\feedme\base;

use verbb\feedme\FeedMe;
use verbb\feedme\helpers\DataHelper;

use Craft;
use craft\base\Component;

use Cake\Utility\Hash;

abstract class Field extends Component
{
    // Properties
    // =========================================================================

    public $feedData;
    public $fieldHandle;
    public $fieldInfo;
    public $field;
    public $feed;
    public $element;


    // Public Methods
    // =========================================================================

    public function getName()
    {
        return $this::$name;
    }

    public function getClass()
    {
        return get_class($this);
    }

    public function getFieldClass()
    {
        return $this::$class;
    }


    // Templates
    // =========================================================================

    // public function getMappingTemplate()
    // {
    //     return 'feed-me/_includes/fields/default';
    // }


    // Public Methods
    // =========================================================================

    public function fetchSimpleValue()
    {
        return DataHelper::fetchSimpleValue($this->feedData, $this->fieldInfo, $this->element);
    }

    public function fetchArrayValue()
    {
        return DataHelper::fetchArrayValue($this->feedData, $this->fieldInfo, $this->element);
    }

    public function fetchValue()
    {
        return DataHelper::fetchValue($this->feedData, $this->fieldInfo, $this->element);
    }



    // Protected Methods
    // =========================================================================

    protected function populateElementFields($elementIds)
    {
        $elementsService = Craft::$app->getElements();
        $fields = Hash::get($this->fieldInfo, 'fields');

        $fieldData = [];

        foreach ($elementIds as $key => $elementId) {
            foreach ($fields as $fieldHandle => $fieldInfo) {
                $default = Hash::get($fieldInfo, 'default');

                // Because we're dealing with an element which always has array content, we need to fetch our content
                // in the same way, as it'll be parsed as an array, despite the actual values being likely a single value
                // Even if its an array of one size (importing one element), that's fine!
                $fieldValue = DataHelper::fetchArrayValue($this->feedData, $fieldInfo, $this->element);

                // Arrayed content doesn't provide defaults because its unable to determine how many items it _should_ return
                // This also checks if there was any data that corresponds on the same array index/level as our element
                $value = Hash::get($fieldValue, $key, $default);

                if ($value) {
                    $fieldData[$elementId][$fieldHandle] = $value;
                }
            }
        }

        // Now, for each element, we need to save the contents
        foreach ($fieldData as $elementId => $fieldContent) {
            $element = $elementsService->getElementById($elementId);

            $element->setFieldValues($fieldContent);

            FeedMe::debug($this->feed, [
                $this->fieldHandle => [
                    $elementId => $fieldContent,
                ]
            ]);

            // 3x5 ad hoc HACK: Set postDate to imported date
            if (isset($fieldContent['postDateImport'])) {
                $element->postDate = $fieldContent['postDateImport'];
            }

            if (!$elementsService->saveElement($element)) {
                FeedMe::error($this->feed, 'Unable to save sub-field: ' . json_encode($element->getErrors()));
            }

            FeedMe::info($this->feed, 'Processed ' . $element->displayName() . ' ' . $elementId . '  sub-fields with content: ' . json_encode($fieldContent));
        }
    }


}