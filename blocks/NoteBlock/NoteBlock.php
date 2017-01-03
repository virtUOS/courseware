<?
namespace Mooc\UI\NoteBlock;

use Mooc\UI\Block;

class NoteBlock extends Block 
{
    const NAME = 'Notizblock';

    function initialize()
    {
        $this->defineField('note_type', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('note_color', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('note_quantity', \Mooc\SCOPE_BLOCK, '');

    }

    function student_view()
    {
        return array_merge($this->getAttrArray(), array('note_color_student_view' => $this->getColor($this->note_color)) );
    }

    function author_view()
    {
        $this->authorizeUpdate();
        return array_merge($this->getAttrArray());
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['note_type'])) {
            $this->note_type = (string) $data['note_type'];
        } 
        if (isset ($data['note_color'])) {
            $this->note_color = (string) $data['note_color'];
        } 
        if (isset ($data['note_quantity'])) {
            $this->note_quantity = (string) $data['note_quantity'];
        } 

        return;
    }
    
    function download_handler($data)
    {
        $this->setGrade(1.0);
        return ;
    }

    private function getColor($colorname) 
    {
        $colors = array(
            "white"    => "#ffffff",
            "yellow"    => "#fefabc",
            "blue"    => "#bcfefa",
            "green"    => "#bcfabc",
            "red"     => "#febcbc",
            "orange"  => "#febc6c"
        );
        
        return $colors[$colorname];
    }

    private function getAttrArray() 
    {
        return array(
            'note_type' => $this->note_type,
            'note_color' => $this->note_color, 
            'note_quantity' => $this->note_quantity
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
        return 'http://moocip.de/schema/block/note/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/note/note-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['note_type'])) {
            $this->note_type = $properties['note_type'];
        }
        if (isset($properties['note_color'])) {
            $this->note_color = $properties['note_color'];
        }
        if (isset($properties['note_quantity'])) {
            $this->note_quantity = $properties['note_quantity'];
        }

        $this->save();
    }
    
}
