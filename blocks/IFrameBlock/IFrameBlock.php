<?
namespace Mooc\UI\IFrameBlock;

use Mooc\UI\Block;

class IFrameBlock extends Block
{
    const NAME = 'externer Inhalt (iframe)';

    function initialize()
    {
        $this->defineField('url',    \Mooc\SCOPE_BLOCK, "http://studip.de");
        $this->defineField('height', \Mooc\SCOPE_BLOCK, 600);
        $this->defineField('submit_user_id', \Mooc\SCOPE_BLOCK, false);
        $this->defineField('submit_param', \Mooc\SCOPE_BLOCK, "uid");
        $this->defineField('salt', \Mooc\SCOPE_BLOCK, md5(uniqid('', true)));

        $this->defineField('href1', \Mooc\SCOPE_BLOCK, "");
		$this->defineField('linktitle1', \Mooc\SCOPE_BLOCK, "");
		$this->defineField('href2', \Mooc\SCOPE_BLOCK, "");
		$this->defineField('linktitle2', \Mooc\SCOPE_BLOCK, "");
		$this->defineField('href3', \Mooc\SCOPE_BLOCK, "");
		$this->defineField('linktitle3', \Mooc\SCOPE_BLOCK, "");
    }

    function array_rep() {
    
        $sep1 = "";
		$sep2 = "";

		if($this->linktitle2){
			$sep1 = "from";
		}
		if($this->linktitle3){
			$sep2 = " - ";
		}
        
        return array(
            'url'    => $this->url,
            'height' => $this->height,
            'submit_user_id' => $this->submit_user_id,
            'submit_param' => $this->submit_param,
            'salt' => $this->salt,
            'linktitle1' => $this->linktitle1,
            'linktitle2' => $this->linktitle2,
            'linktitle3' => $this->linktitle3,
            'href1' => $this->href1,
            'href2' => $this->href2,
            'href3' => $this->href3,
            'sep1' => $sep1,
            'sep2' => $sep2

        );
    }

    function student_view()
    {
        // on view: grade with 100%
        $this->setGrade(1.0);
        if ($this->submit_user_id){ 
            $url = $this->buildUID(); 
            return $this->array_rep($url);
        }
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

        $this->url = (string) $data['url'];
        $this->height = (int) $data['height'];

		$this->linktitle1 = (string) $data['linktitle1'];
		$this->href1 = (string) $data['href1'];
		$this->linktitle2 = (string) $data['linktitle2'];
		$this->href2 = (string) $data['href2'];
		$this->linktitle3 = (string) $data['linktitle3'];
		$this->href3 = (string) $data['href3'];

        $this->submit_user_id = $data['submit_user_id'];
        $this->submit_param = $data['submit_param'];
        $this->salt = $data['salt'];

        return $this->array_rep();
    }

    /**
     * {@inheritdoc}
     */
    public function exportProperties()
    {
        return array('url' => $this->url, 'height' => $this->height, 'submit_user_id' => $this->submit_user_id, 'submit_param' => $this->submit_param, 'salt' => $this->salt, 'href1' => $this->href1, 'linktitle1' => $this->linktitle1, 'href1' => $this->href1, 'linktitle1' => $this->linktitle1, 'href2' => $this->href2, 'linktitle3' => $this->linktitle3);
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/iframe/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/iframe/iframe-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['url'])) {
            $this->url = $properties['url'];
        }

        if (isset($properties['height'])) {
            $this->height = $properties['height'];
        }
        
        if (isset($properties['submit_user_id'])) {
            $this->submit_user_id = $properties['submit_user_id'];
        }
        
        if (isset($properties['submit_param'])) {
            $this->submit_param = $properties['submit_param'];
        }
        
        if (isset($properties['salt'])) {
            $this->salt = $properties['salt'];
        }
         if (isset($properties['href1'])) {
            $this->href1 = $properties['href1'];
        }
         if (isset($properties['linktitle1'])) {
            $this->linktitle1 = $properties['linktitle1'];
        }
        if (isset($properties['href2'])) {
            $this->href2 = $properties['href2'];
        }
         if (isset($properties['linktitle2'])) {
            $this->linktitle2 = $properties['linktitle2'];
        }
        if (isset($properties['href3'])) {
            $this->href3 = $properties['href3'];
        }
         if (isset($properties['linktitle3'])) {
            $this->linktitle3 = $properties['linktitle3'];
        }

        $this->save();
    }
    
    private function buildUID()
    {
        $url = $this->url;
        $url .= "?".$this->submit_param."=";
        $userid = $GLOBALS['user']->id;
        $url .= md5($userid . $this->salt);
        return $url;
    }
}
