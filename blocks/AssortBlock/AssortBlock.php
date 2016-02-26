<?
namespace Mooc\UI\AssortBlock;

use Mooc\UI\Block;

class AssortBlock extends Block 
{
    const NAME = 'Gruppieren';

    function initialize()
    {
        $this->defineField('assortblocks', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('assorttype', \Mooc\SCOPE_BLOCK, '');
        // for testing only
        //$this->defineField('assorthash', \Mooc\SCOPE_BLOCK, '');
    }

    function student_view()
    {
        $this->setGrade(1.0);
        return $this->getAttrArray();
    }

    function author_view()
    {
        $this->authorizeUpdate();

        return array_merge($this->getBlocksInSection(), $this->getAttrArray());
    }
    
    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        $assortblocks = json_decode($data['assortblocks']);
        // get all blocks
        // get hash for all blocks
        // encode json
        // $this->assortblocks = $assortblocks;
       
        // for testing only
        //$this->assorttype = $data['assorttype'];$this->assorthash = $this->getBlockHash('42');
        
        
        
        return $this->getAttrArray();
    }
    
    private function getAttrArray() 
    {
        return array('assortblocks' => $this->assortblocks, 'assorttype' => $this->assorttype);
    }
    
    private function getBlocksInSection()
    {
        $children = $this->getModel()->parent->children;
        $blocks = array();
        
        foreach($children as $child)
        {
            if ($child["type"] !== "AssortBlock"){
                $blocks[] = array('blockid' =>$child["id"]);
            }
            
        }
       
        return array(
            'blocks'    => $blocks
        );
    }

    private function getBlockHash($blockid)
    {
		$block = \Mooc\DB\Block::find($blockid);
		switch ($block->type) {
			case "HtmlBlock":
				$name = 'content';
				break;
				
			case "VideoBlock":
				$name = 'url';
				break;
			
			case "TestBlock":
				$name = '';
				break;
			case "IFrameBlock":
				$name = "url";
				break;
		}
		
		$field = current(\Mooc\DB\Field::findBySQL('user_id = "" AND name = ? AND block_id = ?', array($name , $block->id)));
		$hash = hash('md5', $field->json_data);
		
		return $hash;
	}
    
    public function exportProperties()
    {
       return array('assortblocks' => $this->assortblocks, 'assorttype' => $this->assorttype);
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/assort/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/assort/assort-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['assortblocks'])) {
            $this->assortblocks = $properties['assortblocks'];
        }

        if (isset($properties['assorttype'])) {
            $this->assorttype = $properties['assorttype'];
        }

        $this->save();
    }
}
