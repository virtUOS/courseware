<?
namespace Mooc\UI\IFrameBlock;

use Mooc\UI\Block;

class IFrameBlock extends Block
{
    const NAME = 'IFrame';

    function initialize()
    {
        $this->defineField('url',    \Mooc\SCOPE_BLOCK, "http://studip.de");
        $this->defineField('height', \Mooc\SCOPE_BLOCK, 600);
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
        return $this->array_rep();
    }

    function author_view()
    {
        $this->authorizeUpdate();

        return $this->toJSON();
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

        return $this->array_rep();
    }

    /**
     * {@inheritdoc}
     */
    public function exportProperties()
    {
        return array('url' => $this->url, 'height' => $this->height);
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

        $this->save();
    }
}
