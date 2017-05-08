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
        $this->defineField('width', \Mooc\SCOPE_BLOCK, 100);
        $this->defineField('submit_user_id', \Mooc\SCOPE_BLOCK, false);
        $this->defineField('submit_param', \Mooc\SCOPE_BLOCK, "uid");
        $this->defineField('salt', \Mooc\SCOPE_BLOCK, md5(uniqid('', true)));
    }

    function array_rep($url = "") {
        if ($url == "") $url = $this->url;
        return array(
            'url'    => $url,
            'height' => $this->height,
            'width' => $this->width,
            'submit_user_id' => $this->submit_user_id,
            'submit_param' => $this->submit_param,
            'salt' => $this->salt
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
        $this->width = (int) $data['width'];
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
        return array('url' => $this->url, 'height' => $this->height, 'width' => $this->width, 'submit_user_id' => $this->submit_user_id, 'submit_param' => $this->submit_param, 'salt' => $this->salt);
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
        
        if (isset($properties['width'])) {
            $this->width = $properties['width'];
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
