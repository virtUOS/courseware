<?php
namespace Mooc\UI\SearchBlock;

use Mooc\UI\Block;
use Mooc\DB\Block as DBBlock;

class SearchBlock extends Block
{
    const NAME = 'Suche';

    public function initialize()
    {
        $this->defineField('searchtitle', \Mooc\SCOPE_BLOCK, "");
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        // on view: grade with 100%
        $this->setGrade(1.0);

        return array('searchtitle' => $this->searchtitle);
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array('searchtitle' => $this->searchtitle);
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        $this->searchtitle = ($data['searchtitle']);

        return array('searchtitle' => $this->searchtitle);
    }

    /**
     * {@inheritdoc}
     */
    public function exportProperties()
    {
        return array('searchtitle' => $this->searchtitle);
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/search/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/search/search-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['searchtitle'])) {
            $this->searchtitle = $properties['searchtitle'];
        }

        $this->save();
    }

    public function search_handler(array $data)
    {
        $request = htmlspecialchars($this->Ansi_utf8($data['request']));
        $db = \DBManager::get();
        $cid = $this->container['cid'];
        $uid = $this->container['current_user_id'];
        $isSequential = $this->container['current_courseware']->getProgressionType() == 'seq';
        $answer = array();

        $stmt = $db->prepare('
            SELECT 
                *
            FROM
                mooc_fields
            WHERE
                json_data LIKE CONCAT ("%",:request,"%") 
            AND
                name IN ("webvideo", 
                         "url", 
                         "videoTitle", 
                         "content", 
                         "title", 
                         "audio_description", 
                         "audio_file_name",
                         "download_title", 
                         "file", 
                         "file_name", 
                         "code_lang", 
                         "code_content",
                         "keypoint_content",
                         "gallery_file_names"
                         )
        ');
        $stmt->bindParam(':request', $request);
        $stmt->execute();
        $sqlfields = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($sqlfields as $item) {
            $block = new DBBlock($item['block_id']);
            if ($block->isPublished()) {
                if ($isSequential) {
                    if (!$block->parent->hasUserCompleted($uid)) {continue;}
                }
                if ($item['name'] == 'content') {
                    $content = str_replace( '<!-- HTML: Insert text after this line only. -->', '', $item['json_data']);
                    if(!stripos($content, $request)) {continue;}
                }
                if ($item['name'] == 'url') {
                    // remove opencast part from url 
                    $url = str_replace( '\/engage\/theodul\/ui\/core.html', '', $item['json_data']);
                    if(!stripos($url, $request)) {continue;}
                }
                // get readable name
                $class_name = 'Mooc\UI\\'.$block->type.'\\'.$block->type; 
                $name_constant = $class_name.'::NAME';

                if (defined($name_constant)) {
                    $type = _cw(constant($name_constant));
                } else {
                    $type = $block->type;
                }

                array_push($answer, array(
                    'link'       =>  \PluginEngine::getURL('courseware/courseware').'&selected='.$block->parent_id,
                    'type'       => $type,
                    'title'      => (new DBBlock($block->parent_id))->title, // section title
                    'subchapter' => (new DBBlock($block->parent->parent->id))->title, //subchapter title
                    'chapter'    => (new DBBlock($block->parent->parent->parent->id))->title, //chapter title
                    'chap'       => false,
                    'name'       => str_replace( '\/engage\/theodul\/ui\/core.html', '', $item['json_data'])
                ));
            }
        }

        $stmt = $db->prepare('
            SELECT 
                *
            FROM
                mooc_blocks
            WHERE
                title LIKE CONCAT ("%",:request,"%") 
            AND
                type IN ("Chapter" , "Subchapter" , "Section")
            AND 
                seminar_id = :cid
        ');
        $stmt->bindParam(':request', $request);
        $stmt->bindParam(':cid', $cid);
        $stmt->execute();
        $sqlblocks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($sqlblocks as $item) {
            $block = new DBBlock($item['id']);
            if ($isSequential) {
                    if (!$block->hasUserCompleted($uid)) {continue;}
            }
            if (strpos($item['title'], 'AsideSection') >-1) { 
                continue;
            }
            if ($block->isPublished()) {
                array_push($answer, array(
                    'link'  => \PluginEngine::getURL('courseware/courseware').'&selected='.$item['id'],
                    'title' => $item['title'],
                    'type'  => $item['type'],
                    'chap'  => true
                ));
            }
        }

        return json_encode($answer);
    }

    private static function Ansi_utf8($string)
    {
        $ansi_utf8 = array(
            "À" => "\\\\\\\u00c0",
            "Á" => "\\\\\\\u00c1",
            "Â" => "\\\\\\\u00c2",
            "Ã" => "\\\\\\\u00c3",
            "Ä" => "\\\\\\\u00c4",
            "Å" => "\\\\\\\u00c5",
            "Æ" => "\\\\\\\u00c6",
            "Ç" => "\\\\\\\u00c7",
            "È" => "\\\\\\\u00c8",
            "É" => "\\\\\\\u00c9",
            "Ê" => "\\\\\\\u00ca",
            "Ë" => "\\\\\\\u00cb",
            "Ì" => "\\\\\\\u00cc",
            "Í" => "\\\\\\\u00cd",
            "Î" => "\\\\\\\u00ce",
            "Ï" => "\\\\\\\u00cf",
            "Ñ" => "\\\\\\\u00d1",
            "Ò" => "\\\\\\\u00d2",
            "Ó" => "\\\\\\\u00d3",
            "Ô" => "\\\\\\\u00d4",
            "Õ" => "\\\\\\\u00d5",
            "Ö" => "\\\\\\\u00d6",
            "Ø" => "\\\\\\\u00d8",
            "Ù" => "\\\\\\\u00d9",
            "Ú" => "\\\\\\\u00da",
            "Û" => "\\\\\\\u00db",
            "Ü" => "\\\\\\\u00dc",
            "Ý" => "\\\\\\\u00dd",
            "ß" => "\\\\\\\u00df",
            "à" => "\\\\\\\u00e0",
            "á" => "\\\\\\\u00e1",
            "â" => "\\\\\\\u00e2",
            "ã" => "\\\\\\\u00e3",
            "ä" => "\\\\\\\u00e4",
            "å" => "\\\\\\\u00e5",
            "æ" => "\\\\\\\u00e6",
            "ç" => "\\\\\\\u00e7",
            "è" => "\\\\\\\u00e8",
            "é" => "\\\\\\\u00e9",
            "ê" => "\\\\\\\u00ea",
            "ë" => "\\\\\\\u00eb",
            "ì" => "\\\\\\\u00ec",
            "í" => "\\\\\\\u00ed",
            "î" => "\\\\\\\u00ee",
            "ï" => "\\\\\\\u00ef",
            "ð" => "\\\\\\\u00f0",
            "ñ" => "\\\\\\\u00f1",
            "ò" => "\\\\\\\u00f2",
            "ó" => "\\\\\\\u00f3",
            "ô" => "\\\\\\\u00f4",
            "õ" => "\\\\\\\u00f5",
            "ö" => "\\\\\\\u00f6",
            "ø" => "\\\\\\\u00f8",
            "ù" => "\\\\\\\u00f9",
            "ú" => "\\\\\\\u00fa",
            "û" => "\\\\\\\u00fb",
            "ü" => "\\\\\\\u00fc",
            "ý" => "\\\\\\\u00fd",
            "ÿ" => "\\\\\\\u00ff",
        );

        return strtr($string, $ansi_utf8);      
    }
}
