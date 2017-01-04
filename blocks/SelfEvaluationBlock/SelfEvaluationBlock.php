<?
namespace Mooc\UI\SelfEvaluationBlock;

use Mooc\UI\Block;

class SelfEvaluationBlock extends Block 
{
    const NAME = 'Selbsteinschätzung';

    function initialize()
    {
        $this->defineField('selfevaluation_title', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('selfevaluation_description', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('selfevaluation_value', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('selfevaluation_content', \Mooc\SCOPE_BLOCK, '');

    }

    function student_view()
    {
        return array_merge($this->getAttrArray());
    }

    function author_view()
    {
        $this->authorizeUpdate();
        return array_merge($this->getAttrArray());
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['selfevaluation_title'])) {
            $this->selfevaluation_title = \STUDIP\Markup::purify( (string) $data['selfevaluation_title']);
        } 
        if (isset ($data['selfevaluation_description'])) {
            $this->selfevaluation_description = \STUDIP\Markup::purify((string) $data['selfevaluation_description']);
        } 
        if (isset ($data['selfevaluation_value'])) {
            $this->selfevaluation_value = (string) $data['selfevaluation_value'];
        } 
        if (isset ($data['selfevaluation_content'])) {
            $this->selfevaluation_content = (string) $data['selfevaluation_content'];
        } 

        return;
    }
    
    function download_handler($data)
    {
        $this->setGrade(1.0);
        return ;
    }

    private function getAttrArray() 
    {
        return array(
            'selfevaluation_title'          => $this->selfevaluation_title,
            'selfevaluation_description'    => $this->selfevaluation_description, 
            'selfevaluation_value'          => $this->selfevaluation_value, 
            'selfevaluation_content'        => $this->selfevaluation_content
        );
    }

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/selfevaluation/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/selfevaluation/selfevaluation-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['selfevaluation_title'])) {
            $this->selfevaluation_title = $properties['selfevaluation_title'];
        }
        if (isset($properties['selfevaluation_description'])) {
            $this->selfevaluation_description = $properties['selfevaluation_description'];
        }
        if (isset($properties['selfevaluation_value'])) {
            $this->selfevaluation_value = $properties['selfevaluation_value'];
        }
        if (isset($properties['selfevaluation_content'])) {
            $this->selfevaluation_content = $properties['selfevaluation_content'];
        }

        $this->save();
    }
    
}
