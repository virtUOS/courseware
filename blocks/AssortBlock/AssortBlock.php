<?php
namespace Mooc\UI\AssortBlock;

use Mooc\UI\Block;

class AssortBlock extends Block 
{
    const NAME = 'Gruppieren';
    const BLOCK_CLASS = 'layout';
    const DESCRIPTION = 'Vereint Blöcke und erzeugt eine zusätzliche Navigation';

    function initialize()
    {
        $this->defineField('assortblocks', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('assorttype', \Mooc\SCOPE_BLOCK, '');
    }

    function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $this->setGrade(1.0);

        return array('assortblocks' => $this->sortByBlockOrder(), 'assorttype' => $this->assorttype);
    }

    function author_view()
    {
        $this->authorizeUpdate();

        return array_merge($this->getBlocksInSection(), $this->getAttrArray());
    }

    public function preview_view()
    {

        return array('assortblocks' => json_decode($this->assortblocks));
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        $this->assorttype = $data['assorttype'];
        // string to json and json to array with objects
        $assortblocks = json_decode(json_encode($data['assortblocks']));
        foreach ($assortblocks as $block) {
           $block->hash = $this->getBlockHash($block->id);
        }
        $this->assortblocks = json_encode($assortblocks);
        
        return $this->getAttrArray();
    }

    private function getAttrArray() 
    {
        return array('assortblocks' => $this->assortblocks, 'assorttype' => $this->assorttype);
    }

    private function sortByBlockOrder()
    {
        $assortblocks = [];
        if(!empty($this->assortblocks)) {
            foreach ( json_decode($this->assortblocks, false) as $assortblock) {
                $block = json_decode(json_encode($assortblock), true);
                $assortblocks[$block['id']] = $assortblock;
            }
        }

        $ordered_array = [];
        foreach($this->getBlocksInSection()["blocks"] as $block_in_section) {
            $block_id = $block_in_section['blockid'];
            if(array_key_exists($block_id, $assortblocks)){
                array_push($ordered_array, $assortblocks[$block_id]);
                unset($assortblocks[$block_id]);
            }
        }

        return json_encode($ordered_array+$assortblocks);
    }

    private function getBlocksInSection()
    {
        $children = $this->getModel()->parent->children;
        $blocks = array();
        foreach($children as $child)
        {
            if (in_array($child["type"], 
                array("HtmlBlock", "VideoBlock", "IFrameBlock", "DownloadBlock", "KeyPointBlock", "LinkBlock", "EmbedBlock")
            )){
                $className = '\Mooc\UI\\'.$child["type"].'\\'.$child["type"];
                $blocks[] = array('blockid' =>$child->id, 'blocktype'=> $child->type, 'blockname' => _cw(constant($className.'::NAME')));
            }
        }

        return array('blocks' => $blocks);
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

            case "IFrameBlock":
                $name = "url";
                break;

            case "DownloadBlock":
                $name = 'file_name';
                break;

            case "KeyPointBlock":
                $name = 'keypoint_content';
                break;

            case "CodeBlock":
                $name = 'code_content';
                break;

            case "LinkBlock":
                $name = 'link_target';
                break;

            case "EmbedBlock":
                $name = 'embed_url';
                break;
        }
        $field = current(\Mooc\DB\Field::findBySQL('user_id = "" AND name = ? AND block_id = ?', array($name , $block->id)));
        if (($block->type == "VideoBlock") && ($field == '')) {
            $field = current(\Mooc\DB\Field::findBySQL('user_id = "" AND name = ? AND block_id = ?', array('webvideo' , $block->id)));
        }
        $hash = hash('md5', trim(preg_replace('/\\\n/', '', json_decode($field->json_data))));

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
            $model = $this->getModel();
            $assortblocks = json_decode($properties['assortblocks']);
            $size = count($assortblocks);
            foreach ($assortblocks as $i => $block) {
                if($model->previousSibling()) {
                    $modelP = $model->previousSibling();
                }
                $blockfound = false;
                $s = 0;
                do {
                    if($modelP){
                        if ($block->hash == $this->getBlockHash($modelP->id)) {
                            $block->id = $modelP->id;
                            $blockfound = true;
                        }
                        else {
                            $modelP = $modelP->previousSibling();
                        }
                    }
                    $s++;
                } while ((!$blockfound)&&($s != $size));

                if (!$blockfound) {unset($assortblocks[$i]);}
            }
            $this->assortblocks = json_encode($assortblocks); 
        }
        if (isset($properties['assorttype'])) {
            $this->assorttype = $properties['assorttype'];
        }

        $this->save();
    }
}
