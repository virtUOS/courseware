<?php
namespace Mooc\UI\SearchBlock;

use Mooc\UI\Block;
use Mooc\DB\Block as DBBlock;
use Mooc\DB\Field as Field;

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
        $request = \STUDIP\Markup::purifyHtml($data['request']);
        $request_blocks = htmlentities($request);
        $request_ansi = $this->Ansi_utf8($request);

        $db = \DBManager::get();
        $cid = $this->container['cid'];
        $uid = $this->container['current_user_id'];
        $user = $this->container['current_user'];
        $isSequential = $this->container['current_courseware']->getProgressionType() == 'seq';
        $answer = array();
        // Blocks
        $stmt = $db->prepare('
            SELECT 
                *
            FROM
                mooc_fields
            WHERE
                json_data LIKE CONCAT ("%",:request_blocks,"%")
            OR
                json_data LIKE CONCAT ("%",:request_ansi,"%")
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
        $stmt->bindParam(':request_blocks', $request_blocks);
        $stmt->bindParam(':request_ansi', $request_ansi);
        $stmt->execute();
        $sqlfields = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($sqlfields as $item) {
            $block = new DBBlock($item['block_id']);

            if (($block->belongesToCourse($cid)) && ($block->isPublished())){
                
                if ($isSequential && !$user->hasPerm($cid, 'dozent') && !$block->parent->hasUserCompleted($uid)) {
                    continue;
                }

                if ($block->type == 'PostBlock'){
                    // we handle this type later
                    continue;
                }

                if ($item['name'] == 'content') {
                    $content = str_replace( '<!-- HTML: Insert text after this line only. -->', '', $item['json_data']);
                    if(!stripos($content, $request_blocks)) {
                        continue;
                    }
                }

                if ($item['name'] == 'url') {
                    // remove opencast part from url 
                    $url = str_replace( '\/engage\/theodul\/ui\/core.html', '', $item['json_data']);
                    if(!stripos($url, $request_blocks)) {
                        continue;
                    }
                }

                // get readable name
                $class_name = 'Mooc\UI\\'.$block->type.'\\'.$block->type; 
                $name_constant = $class_name.'::NAME';

                if (defined($name_constant)) {
                    $type = _cw(constant($name_constant));
                } else {
                    $type = $block->type;
                }
                $type .= " " . \Icon::create($this->getBlockIcon($block->type), 'clickable');
                $section = new DBBlock($block->parent_id);
                $title = $section->title; // section title
                $subchapter = (new DBBlock($block->parent->parent->id))->title; //subchapter title
                $chapter = (new DBBlock($block->parent->parent->parent->id))->title; //chapter title
                
                if (($title == null) || ($subchapter == null) || ($chapter == null) ) {
                    continue;
                }
                $link = \PluginEngine::getURL('courseware/courseware').'&selected='.$block->parent_id;

                $html = "<li>".$chapter." &rarr; ".$subchapter." &rarr; ".$title." &rarr; <a href='".$link."'>".$type."</a></li>";

                if (strpos($title, 'AsideSection') >-1) { 
                    $chapter_id = str_replace('AsideSection for block ', '', $title);
                    $title = "Sidebar";
                    $chapter_block = new DBBlock($chapter_id);
                    switch ($chapter_block->type) {
                        case "Chapter":
                            $chapter = $chapter_block->title;
                            $link = \PluginEngine::getURL('courseware/courseware').'&selected='.$chapter_block->id;
                            $html = "<li>".$chapter." &rarr; ".$title." &rarr; <a href='".$link."'>".$type."</a></li>";
                            break;
                        case "Subchapter":
                            $chapter = (new DBBlock($chapter_block->parent->id))->title;
                            $subchapter = $chapter_block->title;
                            $link = \PluginEngine::getURL('courseware/courseware').'&selected='.$chapter_block->id;
                            $html = "<li>".$chapter." &rarr; ".$subchapter." &rarr; ".$title." &rarr; <a href='".$link."'>".$type."</a></li>";
                            break;
                        default:
                            continue;
                    }
                }

                
                array_push($answer, array(
                    'html'  =>  $html
                ));
            }
        }

        //Structural Elements
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

            if ($isSequential && !$user->hasPerm($cid, 'dozent') && !$block->hasUserCompleted($uid)) {
                continue;
            }

            if (strpos($item['title'], 'AsideSection') >-1) { 
                continue;
            }

            if ($block->isPublished()) {
                $link = \PluginEngine::getURL('courseware/courseware').'&selected='.$item['id'];
                $title = $item['title'];
                $title .= " " . \Icon::create('category', 'clickable');
                $type = $item['type'];
                switch ($type) {
                    case "Chapter":
                        $html = "<li><a href='".$link."'>".$title."</a></li>";
                        break;
                    case "Subchapter":
                        $chapter = (new DBBlock($block->parent_id))->title;
                        $html = "<li>".$chapter." &rarr; <a href='".$link."'>".$title."</a></li>";
                        break;
                    case "Section":
                        $chapter = (new DBBlock($block->parent->parent->id))->title;
                        $subchapter = (new DBBlock($block->parent_id))->title;
                        $html = "<li>".$chapter." &rarr; ".$subchapter." &rarr;<a href='".$link."'>".$title."</a></li>";
                        break;
                    default:
                        $html = "";
                }
                array_push($answer, array(
                    'html'  => $html
                ));
            }
        }

        // Threads
        $stmt = $db->prepare('
            SELECT 
                thread_id
            FROM
                mooc_posts
            WHERE
                content LIKE CONCAT ("%",:request,"%") 
            AND 
                seminar_id = :cid
            AND
                hidden = 0
        ');
        $stmt->bindParam(':request', $request);
        $stmt->bindParam(':cid', $cid);
        $stmt->execute();
        $sqlthreads = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($sqlthreads as $thread) {
            $fields = Field::findBySQL('name = ? AND json_data = ?', array("thread_id", $thread['thread_id']));

            foreach ($fields as $field) {
                $block = new DBBlock($field['block_id']);

                if (($block->belongesToCourse($cid)) && ($block->isPublished())){
                    if ($isSequential && !$user->hasPerm($cid, 'dozent') && !$block->parent->hasUserCompleted($uid)) {
                        continue;
                    }
                    // get readable name
                    $name = json_decode(Field::findOneBySQL('block_id = ? AND name = ?', array($block->id, "post_title"))->json_data);
                    $name .= " " . \Icon::create('chat', 'clickable');
                    $title = (new DBBlock($block->parent_id))->title; // section title
                    $subchapter = (new DBBlock($block->parent->parent->id))->title; //subchapter title
                    $chapter = (new DBBlock($block->parent->parent->parent->id))->title; //chapter title
                    $link = \PluginEngine::getURL('courseware/courseware').'&selected='.$block->parent_id;
                    $html = "<li>".$chapter." &rarr; ".$subchapter." &rarr; ".$title." &rarr; <a href='".$link."'>".$name."</a></li>";

                    if (strpos($title, 'AsideSection') >-1) { 
                    $chapter_id = str_replace('AsideSection for block ', '', $title);
                    $title = "Sidebar";
                    $chapter_block = new DBBlock($chapter_id);
                    switch ($chapter_block->type) {
                        case "Chapter":
                            $chapter = $chapter_block->title;
                            $link = \PluginEngine::getURL('courseware/courseware').'&selected='.$chapter_block->id;
                            $html = "<li>".$chapter." &rarr; ".$title." &rarr; <a href='".$link."'>".$name."</a></li>";
                            break;
                        case "Subchapter":
                            $chapter = (new DBBlock($chapter_block->parent->id))->title;
                            $subchapter = $chapter_block->title;
                            $link = \PluginEngine::getURL('courseware/courseware').'&selected='.$chapter_block->id;
                            $html = "<li>".$chapter." &rarr; ".$subchapter." &rarr; ".$title." &rarr; <a href='".$link."'>".$name."</a></li>";
                            break;
                        default:
                            continue;
                    }
                }

                    array_push($answer, array(
                        'html'  =>  $html
                    ));
                } 
            }
        }

        return json_encode($answer);
    }

    private static function getBlockIcon($type)
    {
        $icons = array(
            'BlubberBlock'  => 'blubber',
            'ForumBlock'    => 'forum',
            'VideoBlock'    => 'video',
            'AudioBlock'    => 'play',
            'TestBlock'     => 'question',
            'SearchBlock'   => 'search',
            'CodeBlock'     => 'computer',
            'GalleryBlock'  => 'image',
        );

        if ($icons[$type] != null) {
            return $icons[$type];
        } else {
            return "info";
        }
    }

    private static function Ansi_utf8($string)
    {
        $ansi_utf8 = array(
            "À" => "\\\\u00c0",
            "Á" => "\\\\u00c1",
            "Â" => "\\\\u00c2",
            "Ã" => "\\\\u00c3",
            "Ä" => "\\\\u00c4",
            "Å" => "\\\\u00c5",
            "Æ" => "\\\\u00c6",
            "Ç" => "\\\\u00c7",
            "È" => "\\\\u00c8",
            "É" => "\\\\u00c9",
            "Ê" => "\\\\u00ca",
            "Ë" => "\\\\u00cb",
            "Ì" => "\\\\u00cc",
            "Í" => "\\\\u00cd",
            "Î" => "\\\\u00ce",
            "Ï" => "\\\\u00cf",
            "Ñ" => "\\\\u00d1",
            "Ò" => "\\\\u00d2",
            "Ó" => "\\\\u00d3",
            "Ô" => "\\\\u00d4",
            "Õ" => "\\\\u00d5",
            "Ö" => "\\\\u00d6",
            "Ø" => "\\\\u00d8",
            "Ù" => "\\\\u00d9",
            "Ú" => "\\\\u00da",
            "Û" => "\\\\u00db",
            "Ü" => "\\\\u00dc",
            "Ý" => "\\\\u00dd",
            "ß" => "\\\\u00df",
            "à" => "\\\\u00e0",
            "á" => "\\\\u00e1",
            "â" => "\\\\u00e2",
            "ã" => "\\\\u00e3",
            "ä" => "\\\\u00e4",
            "å" => "\\\\u00e5",
            "æ" => "\\\\u00e6",
            "ç" => "\\\\u00e7",
            "è" => "\\\\u00e8",
            "é" => "\\\\u00e9",
            "ê" => "\\\\u00ea",
            "ë" => "\\\\u00eb",
            "ì" => "\\\\u00ec",
            "í" => "\\\\u00ed",
            "î" => "\\\\u00ee",
            "ï" => "\\\\u00ef",
            "ð" => "\\\\u00f0",
            "ñ" => "\\\\u00f1",
            "ò" => "\\\\u00f2",
            "ó" => "\\\\u00f3",
            "ô" => "\\\\u00f4",
            "õ" => "\\\\u00f5",
            "ö" => "\\\\u00f6",
            "ø" => "\\\\u00f8",
            "ù" => "\\\\u00f9",
            "ú" => "\\\\u00fa",
            "û" => "\\\\u00fb",
            "ü" => "\\\\u00fc",
            "ý" => "\\\\u00fd",
            "ÿ" => "\\\\u00ff",
        );

        return strtr($string, $ansi_utf8);      
    }
}
