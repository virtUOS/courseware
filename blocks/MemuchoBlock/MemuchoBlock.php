<?
namespace Mooc\UI\MemuchoBlock;

use Mooc\UI\Block;

class MemuchoBlock extends Block
{
    const NAME = 'Memucho Inhalt';

    function initialize()
    {
        $this->defineField('data_id', \Mooc\SCOPE_BLOCK, "");
        $this->defineField('data_t', \Mooc\SCOPE_BLOCK, "");
        $this->defineField('data_questionCount', \Mooc\SCOPE_BLOCK, "");
    }

    function array_rep() {
        return array(
            'data_id'            => $this->data_id,
            'data_t'             => $this->data_t,
            'data_questionCount' => $this->data_questionCount
        );
    }

    function student_view()
    {
        // on view: grade with 100%
        $this->setGrade(1.0);
        return $this->array_rep();
    }

    function author_view()
    {
        $this->authorizeUpdate();
        return $this->array_rep();
    }

    /**
     * Updates the block's data.
     *
     * @param array $data The request data
     *
     * @return array The block's data
     */
    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        $this->data_id            = $data['data_id'];
        $this->data_t             = $data['data_t'];
        $this->data_questionCount = $data['data_questionCount'];

        return $this->array_rep();
    }

    /**
     * {@inheritdoc}
     */
    public function exportProperties()
    {
        return $this->array_rep();
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/memucho/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/memucho/memucho-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['data_id'])) {
            $this->data_id = $properties['data_id'];
        }
        if (isset($properties['data_t'])) {
            $this->data_t = $properties['data_t'];
        }
        if (isset($properties['data_questionCount'])) {
            $this->data_questionCount = $properties['data_questionCount'];
        }
        $this->save();
    }

}
