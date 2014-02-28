<?
namespace Mooc\UI;

// TODO: lots!
class IFrameBlock extends Block {

    function initialize()
    {
        $this->defineField('url', \Mooc\SCOPE_BLOCK, "http://studip.de");
        $this->defineField('height', \Mooc\SCOPE_BLOCK, 600);
        $this->defineField('width', \Mooc\SCOPE_BLOCK, "100%");
        // $this->content = new StringField(BLOCK_SCOPE, "Hello World!");
    }

	function array_rep() {
        return array('url' => $this->url, 
                     'height' => $this->height, 
                     'width' => $this->width);		
	}
	
    function student_view()
    {
        return $this->array_rep();
    }

    function author_view()
    {
        return $this->toJSON();
    }

    function foo_handler($data)
    {
        $this->url = (string) $data['url'];
		$this->height = parseInt((string) $data['height'], 10);
		$this->width = parseInt((string) $data['width'], 10);
        return $this->array_rep();
    }
}
