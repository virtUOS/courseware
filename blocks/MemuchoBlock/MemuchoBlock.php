<?
namespace Mooc\UI\MemuchoBlock;

use Mooc\UI\Block;

class MemuchoBlock extends Block
{
    const NAME = 'Memucho Inhalt';

    function initialize()
    {
        $this->defineField('data_id',    \Mooc\SCOPE_BLOCK, "");
    }

    function array_rep() {
    
        
        return array(
            'data_id'    => $this->data_id
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

        $this->data_id = (int) $data['data_id'];

        return $this->array_rep();
    }

    /**
     * {@inheritdoc}
     */
    public function exportProperties()
    {
        return array('data_id' => $this->data_id);
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
        

        $this->save();
    }

}
