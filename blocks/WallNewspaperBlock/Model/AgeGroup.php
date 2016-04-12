<?
namespace Mooc\UI\WallNewspaperBlock\Model;

class AgeGroup extends Topic {

    public $testBlock;

    public function __construct($id, $title, $content, $complete, $testBlock)
    {
        parent::__construct($id, $title, $content, $complete);
        $this->testBlock = $testBlock;
    }

    public function toJSON($shallow = false)
    {
        $json = parent::toJSON($shallow);

        $selfTest = null;
        if ($this->testBlock) {
            $selfTest = array('id' => $this->testBlock->id, 'test_id' => null);
            if ($field = \Mooc\DB\Field::find(array($this->testBlock->id, '', 'test_id'))) {
                $test_id = $field->content;
                $selfTest['test_id'] = $test_id === '' ? null : $test_id;
            }
        }

        return array_merge($json, compact('selfTest'));
    }
}
