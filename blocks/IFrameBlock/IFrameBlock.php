<?php
namespace Mooc\UI\IFrameBlock;

use Mooc\UI\Block;

class IFrameBlock extends Block
{
    const NAME = 'externer Inhalt (iframe)';

    public function initialize()
    {
        $this->defineField('url',    \Mooc\SCOPE_BLOCK, "http://studip.de");
        $this->defineField('height', \Mooc\SCOPE_BLOCK, 600);
        $this->defineField('width', \Mooc\SCOPE_BLOCK, 100);
        $this->defineField('submit_user_id', \Mooc\SCOPE_BLOCK, false);
        $this->defineField('submit_param', \Mooc\SCOPE_BLOCK, "uid");
        $this->defineField('salt', \Mooc\SCOPE_BLOCK, md5(uniqid('', true)));
        $this->defineField('cc_infos',    \Mooc\SCOPE_BLOCK, "");
    }

    private function array_rep($url = "")
    {
        if ($url == "") $url = $this->url;

        return array(
            'url'               => $url,
            'height'            => $this->height,
            'width'             => $this->width,
            'submit_user_id'    => $this->submit_user_id,
            'submit_param'      => $this->submit_param,
            'salt'              => $this->salt,
            'cc_infos'          => $this->cc_infos
        );
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        // on view: grade with 100%
        $this->setGrade(1.0);

        if ($this->submit_user_id){ 
            $url = $this->buildUID(); 
            $array = $this->array_rep($url);
        }else {
            $array = $this->array_rep();
        }

        if ($this->isHTTPS()) {
            $wrong_protocol = strpos($this->url, 'https') <= -1;
        }

        return array_merge($array, array('cc_infos' => json_decode($array['cc_infos']), 'loading_denyed' => $this->isLoadingDenyed(), 'wrong_protocol' => $wrong_protocol));
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $https = $this->isHTTPS();
        if ($https) {
            $wrong_protocol = strpos($this->url, 'https') <= -1;
        }

        return array_merge($this->array_rep(), array('https' => $https, 'loading_denyed' => $this->isLoadingDenyed(), 'wrong_protocol' => $wrong_protocol));
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
        $height  = (int) $data['height'];
        $width   = (int) $data['width'];
        if(($height > 3000) || ($height < 20)){
            $height = 600;
        }
        if(($width > 100) || ($width < 5)){
            $width = 100;
        }
        $this->url              = (string) $data['url'];
        $this->height           = $height;
        $this->width            = $width;
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
            'width'             => $this->width, 
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

    private function isLoadingDenyed()
    {
        $error=false;
        if (in_array('curl', get_loaded_extensions())) {
            $ch = curl_init();
            $options = array(
                    CURLOPT_URL            => $this->url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER         => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_ENCODING       => "",
                    CURLOPT_AUTOREFERER    => true,
                    CURLOPT_CONNECTTIMEOUT => 120,
                    CURLOPT_TIMEOUT        => 120,
                    CURLOPT_MAXREDIRS      => 10,
            );
            curl_setopt_array($ch, $options);
            $headers = substr(curl_exec($ch), 0, curl_getinfo($ch)['header_size']);
            if(strpos($headers, 'X-Frame-Options: deny')>-1||strpos($headers, 'X-Frame-Options: SAMEORIGIN')>-1) {
                    $error = true;
            }
            curl_close($ch);
        }

        return $error;
    }

    private function isHTTPS()
    {
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
            return true; 
        } else {
            return false;
        }
    }
}
