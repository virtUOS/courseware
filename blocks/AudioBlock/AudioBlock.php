<?
namespace Mooc\UI\AudioBlock;

use Mooc\UI\Block;

class AudioBlock extends Block 
{
    const NAME = 'Audio';

    function initialize()
    {
        $this->defineField('audio_description', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('audio_source', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('audio_file', \Mooc\SCOPE_BLOCK, '');
    }

    function student_view()
    {   
        return array_merge($this->getAttrArray(), array("audio_played"=> $this->getProgress()['grade']));
    }

    function author_view()
    {
        $this->authorizeUpdate();
        return array_merge($this->getAttrArray(), array("audio_files"=>$this->showFiles()));
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['audio_description'])) {
            $this->audio_description = (string) $data['audio_description'];
        } 
        if (isset ($data['audio_source'])) {
            $this->audio_source = (string) $data['audio_source'];
        } 
        if (isset ($data['audio_file'])) {
            $this->audio_file = (string) $data['audio_file'];
        } 
        

        return;
    }
    
    function play_handler($data)
    {
        $this->setGrade(1.0);
        return ;
    }
    
    private function showFiles()
    {
        $db = \DBManager::get();
        $stmt = $db->prepare("
            SELECT 
                * 
            FROM 
                dokumente 
            WHERE 
                seminar_id = :seminar_id
            ORDER BY 
                name
        ");
        $stmt->bindParam(":seminar_id", $this->container['cid']);
        $stmt->execute();
        $response = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $filesarray = array();
        foreach ($response as $item) {
            if((strpos($item['filename'], "mp3") > -1) || (strpos($item['filename'], "ogg") > -1) || (strpos($item['filename'], "wav") > -1)){
                $filesarray[] = $item;
            }
        }
        return $filesarray;
        
    }

    private function getAttrArray() 
    {
        return array(
            'audio_description' => $this->audio_description,
            'audio_source' => $this->audio_source,
            'audio_file' => $this->audio_file
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
        return 'http://moocip.de/schema/block/audio/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/audio/audio-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['audio_description'])) {
            $this->audio_description = $properties['audio_description'];
        }
        if (isset($properties['audio_source'])) {
            $this->audio_source = $properties['audio_source'];
        }
        if (isset($properties['audio_file'])) {
            $this->audio_file = $properties['audio_file'];
        }
        

        $this->save();
    }
    
}
