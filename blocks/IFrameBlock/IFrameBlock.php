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
        $this->defineField('cc_infos',    \Mooc\SCOPE_BLOCK, "");
        
    }

    function array_rep($url = "") {
        if ($url == "") $url = $this->url;
        return array(
            'url'               => $url,
            'height'            => $this->height,
            'submit_user_id'    => $this->submit_user_id,
            'submit_param'      => $this->submit_param,
            'salt'              => $this->salt,
            'cc_infos'          => $this->cc_infos
        );
    }

    function student_view()
    {
        // on view: grade with 100%
        $this->setGrade(1.0);
        
        if ($this->submit_user_id){ 
            $url = $this->buildUID(); 
            $array = $this->array_rep($url);
        }else {
            $array = $this->array_rep();
        }
        $array['cc_infos'] = json_decode($array['cc_infos']);
        return $array;
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

        $this->url              = (string) $data['url'];
        $this->height           = (int) $data['height'];
        $this->submit_user_id   = $data['submit_user_id'];
        $this->submit_param     = $data['submit_param'];
        $this->salt             = $data['salt'];
        $this->cc_infos         = $data['cc_infos'];

        return $this->array_rep();
    }

    /**
     * {@inheritdoc}
     */
    public function exportProperties()
    {
        return array(
            'url'               => $this->url, 
            'height'            => $this->height, 
            'submit_user_id'    => $this->submit_user_id, 
            'submit_param'      => $this->submit_param, 
            'salt'              => $this->salt,
            'cc_infos'          => $this->cc_infos
        );
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
        
        if (isset($properties['cc_infos'])) {
            $this->cc_infos = $properties['cc_infos'];
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
