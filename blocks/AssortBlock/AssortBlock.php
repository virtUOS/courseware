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
        $this->assortblocks = json_encode($data['assortblocks']);
        $this->assorttype = $data['assorttype'];
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
}
