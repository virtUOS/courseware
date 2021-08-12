<?php

namespace Mooc\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Mooc\DB\Block as dbBlock;
use Courseware\StructuralElement as StructuralElement;
use Courseware\Container as Container;
use Courseware\Block as Block;

/**
 * @author Ron Lucke <lucke@elan-ev.de>
 */

class MigrateCoursewareCommand extends Command
{
    protected function configure()
    {
        $this->setName('courseware:migrate');
        $this->setDescription('migrate from courseware plugin data to couseware core');
    }
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Start migration ...');
        $this->plugin_courseware = \PluginManager::getInstance()->getPlugin('Courseware');
        \PluginEngine::getPlugin('CoreForum');
        $coursewares =  dbBlock::findBySQL('type = ?', array('Courseware'));
        // TODO: for each cid not migrated jet
        $cid = $coursewares[1]->seminar_id;
        $grouped = $this->getGrouped($cid, true);
        $courseware = current($grouped['']);
        $this->buildTree($grouped, $courseware);
        //TODO: create new Courseware elements
        $this->createNewCourseware($courseware, $output);
        //TODO: store migration success in db
        $output->writeln('Migration complete.');

        return 0;
    }

    private function getGrouped($cid)
    {
        $grouped = array_reduce(
            dbBlock::findBySQL('seminar_id = ? ORDER BY id, position', array($cid)),
            function($memo, $item) use($remote) {
                $arr = $item->toArray();
                $arr['isStrucutalElement'] = true;
                $arr['childType'] = $this->getSubElement($arr['type']);
                $ui_block = $this->plugin_courseware->getBlockFactory()->makeBlock($item);
                $arr['ui_block'] = $ui_block;
                if (!$item->isStructuralBlock()) {
                    $arr['isStrucutalElement'] = false;
                    $arr['isBlock'] = true;
                    $arr['fields'] = $ui_block->getFields();
                }
                $memo[$item->parent_id][] = $arr;
                return $memo;
            },
            array());

        return $grouped;
    }

    private function getSubElement($type) {
        $sub_element = null;
        switch($type) {
            case 'Courseware':
                $sub_element = 'Chapter';
                break;
            case 'Chapter':
                $sub_element = 'Subchapter';
                break;
            case 'Subchapter':
                $sub_element = 'Section';
                break;
            case 'Section':
                $sub_element = 'Block';
                break;
            case 'Block':
            default:
        }

        return $sub_element;
    }

    private function buildTree($grouped, &$root)
    {
        $this->addChildren($grouped, $root);
        if ($root['type'] !== 'Section') {
            if (!empty($root['children'])) {
                foreach($root['children'] as &$child) {
                    $this->buildTree($grouped, $child);
                }
            }
        } else {
            $root['children'] = $this->addChildren($grouped, $root);
        }
    }

    private function addChildren($grouped, &$parent)
    {
        $parent['children'] = $grouped[$parent['id']];
        if ($parent['children'] != null) {
            usort($parent['children'], function($a, $b) {
                return $a['position'] - $b['position'];
            });
        }

        return $parent['children'];
    }

    private function createNewCourseware($courseware, $output)
    {
        $cid = $courseware['seminar_id'];
        $course = \Course::find($cid);
        $courseTeacher = $course->getMembersWithStatus('dozent')[0];
        $teacher = \User::find($courseTeacher->user_id);

        //get courseware settings
        $settings = [];
        $settings['progression'] = ($courseware['ui_block']->progression);
        $settings['editing_permission'] = ($courseware['ui_block']->editing_permission);

        //TODO: courseware root - is a root present?
        $root = StructuralElement::getCoursewareCourse($cid);

        //TODO: set root name
        if($root === null) {
            $root = StructuralElement::build([
                'parent_id' => null,
                'range_id' => $cid,
                'range_type' => 'course',
                'owner_id' => $teacher->id,
                'editor_id' => $teacher->id,
                'purpose' => 'content',
                'title' => $course->name,
            ]);
    
            $root->store();
        }

        foreach($courseware['children'] as $chapter) {
            $new_chapter = $this->createStructuralElement($chapter, $root->id, $teacher, $cid, $output);
            foreach($chapter['children'] as $subchapter) {
                $new_subchapter = $this->createStructuralElement($subchapter, $new_chapter->id, $teacher, $cid, $output);
                foreach($subchapter['children'] as $section) {
                    $new_section = $this->createStructuralElement($section, $new_subchapter->id, $teacher, $cid, $output);
                    $new_container = $this->createContainer($new_section->id, $teacher, $output);
                    foreach($section['children'] as $block) {
                        $new_block = $this->createBlock($block, $new_container, $teacher, $cid, $output);
                        $user_progresses = \Mooc\DB\UserProgress::findBySQL('block_id = ?', array($block['id']));
                        foreach($user_progresses as $user_progress) {
                            $progress = \Courseware\UserProgress::build([
                                'user_id' => $user_progress['user_id'],
                                'block_id' => $new_block->id,
                                'grade' => $user_progress['grade'] / $user_progress['max_grade'],
                                'mkdate' => $this->convertCoursewareDate($user_progress['mkdate']),
                                'chdate' => $this->convertCoursewareDate($user_progress['chdate'])
                            ]);
                            $progress->store();
                        }
                        //add block to container payload
                        $new_container->type->addBlock($new_block);
                        $new_container->store();
                    }
                }
            }
        }

        return true;
    }

    private function createStructuralElement($element, $parent_id, $user, $cid, $output)
    {
        $element = StructuralElement::build([
            'parent_id' => $parent_id,
            'range_id' => $cid,
            'range_type' => 'course',
            'owner_id' => $user->id,
            'editor_id' => $user->id,
            'purpose' => 'content',
            'title' => $element['title'],
        ]);

        $element->store();

        return $element;
    }
    private function createContainer($structural_element_id, $user, $output)
    {
        $payload = array(
            'colspan' => 'full',
            'sections' => [array('name' => 'Liste', 'icon' => '', 'blocks' => [])]
        );

        $container = Container::build([
            'structural_element_id' => $structural_element_id,
            'owner_id' => $user->id,
            'editor_id' => $user->id,
            'container_type' => 'list',
            'payload' => json_encode($payload)
        ]);
        $container->store();

        return $container;
    }
    private function createBlock($block, $container, $user, $cid, $output)
    {
        $addBlock = false;
        $block_type = '';
        $payload = [];
        switch($block['type']) {
            case 'AssortBlock':
                // we skip this block type
                break;
            case 'AudioBlock':
                $source = false;
                if ($block['fields']['audio_source'] === 'cw') {
                    $source = 'studip_file';
                }
                if ($block['fields']['audio_source'] === 'webaudio') {
                    $source = 'web';
                }
                if (!$source) {
                    break;
                }
                $url = $source === 'web' ? $block['fields']['audio_file']: '';
                $payload = array(
                    'title' => $block['fields']['audio_description'],
                    'source' => $source,
                    'file_id' => $block['fields']['audio_id'],
                    'folder_id' => '',
                    'web_url' => $url,
                    'recorder_enabled' => false
                );
                $block_type = 'audio';
                $addBlock = true;
                break;
            case 'AudioGalleryBlock':
                $rootFolder = \Folder::findTopFolder($cid);
                $folder_type = 'HiddenFolder';
                $destinationFolderName = 'AudioGallery';
                $destinationFolder = \FileManager::createSubFolder(
                    \FileManager::getTypedFolder($rootFolder->id),
                    $user,
                    $folder_type,
                    $destinationFolderName,
                    ''
                );
                $destinationFolder->__set('download_allowed', 1);
                $destinationFolder->store();
                $payload = array(
                    'title' => '',
                    'source' => 'studip_folder',
                    'file_id' => '',
                    'folder_id' => $destinationFolder->id,
                    'web_url' => '',
                    'recorder_enabled' => true
                );
                $block_type = 'audio';
                $addBlock = true;
            case 'BeforeAfterBlock':
                $before = json_decode($block['fields']['ba_before']);
                if($before->source === 'file') {
                    $before_source = 'studip';
                }
                if($before->source === 'url') {
                    $before_source = 'web';
                }
                $after = json_decode($block['fields']['ba_after']);
                if($after->source === 'file') {
                    $after_source = 'studip';
                }
                if($after->source === 'url') {
                    $after_source = 'web';
                }
                $payload = array(
                    'before_source' => $before_source,
                    'after_source' => $after_source,
                    'before_file_id' => $before->file_id,
                    'after_file_id' => $after->file_id,
                    'before_web_url' => $before->url,
                    'after_web_url' => $after->url,
                );
                $block_type = 'before-after';
                $addBlock = true;
                break;
            case 'BlubberBlock':
                // we skip this block type
                break;
            case 'CanvasBlock':
                $canvas_content = json_decode($block['fields']['canvas_content']);
                if ($canvas_content->source !== 'cw' || !$canvas_content->image) {
                    $image = 'false';
                } else {
                    $image = 'true';
                }
                $payload = array(
                    'title' => $canvas_content->description,
                    'image' => $image,
                    'file_id' => $canvas_content->image_id,
                    'upload_folder_id' =>  $canvas_content->upload_folder_id,
                    'show_usersdata' => $canvas_content->show_userdata,
                );
                $block_type = 'canvas';
                $addBlock = true;
                break;
            case 'ChartBlock':
                $chart_content = json_decode($block['fields']['chart_content']);
                $payload = array(
                    'content' => $chart_content,
                    'label' => $block['fields']['chart_label'],
                    'type' =>  $block['fields']['chart_type']
                );
                $block_type = 'chart';
                $addBlock = true;
                break;
            case 'CodeBlock':
                $payload = array(
                    'content' => $block['fields']['code_content'],
                    'lang' =>  $block['fields']['code_lang']
                );
                $block_type = 'code';
                $addBlock = true;
                break;
            case 'ConfirmBlock':
                $payload = array(
                    'text' => $block['title']
                );
                $block_type = 'confirm';
                $addBlock = true;
                break;
            case 'DateBlock':
                $date_content = json_decode($block['fields']['date_content']);
                $date = date_create_from_format('Y-m-d H:i', $date_content->date . ' ' . $date_content->time);
                $payload = array(
                    'style' => $date_content->type,
                    'timestamp' => date_timestamp_get($date) * 1000
                );
                $block_type = 'date';
                $addBlock = true;
                break;
            case 'DialogCardsBlock':
                $cards = json_decode($block['fields']['dialogcards_content']);
                foreach($cards as &$card) {
                    $card->front_file_id = $card->front_img_file_id;
                    unset($card->front_img_file_id);
                    unset($card->front_img_file_name);
                    unset($card->front_img);
                    unset($card->front_external_file);
                    $card->back_file_id = $card->back_img_file_id;
                    unset($card->back_img_file_id);
                    unset($card->back_img_file_name);
                    unset($card->back_img);
                    unset($card->back_external_file);
                    $card->active = false; 
                }
                $payload = array(
                    'cards' => $cards
                );
                $block_type = 'dialog-cards';
                $addBlock = true;
                break;
            case 'DiscussionBlock':
                // we skip this block type
                break;
            case 'DownloadBlock':
                $payload = array(
                    'title' => $block['fields']['download_title'],
                    'file_id' => $block['fields']['file_id'],
                    'info' => $block['fields']['download_info'],
                    'success' =>  $block['fields']['download_success'],
                    'grade' => $block['fields']['download_grade']
                );
                $block_type = 'download';
                $addBlock = true;
                break;
            case 'EmbedBlock':
                $embed_time = json_decode($block['fields']['embed_time']);
                $starttime = sprintf('%02d:%02d:%02d', floor($embed_time->start / 3600), floor($embed_time->start / 60 % 60), floor($embed_time->start % 60));
                $endtime = sprintf('%02d:%02d:%02d', floor($embed_time->end / 3600), floor($embed_time->end / 60 % 60), floor($embed_time->end % 60));
                $payload = array(
                    'title' => $block['fields']['embed_title'],
                    'url' => $block['fields']['embed_url'],
                    'source' => $block['fields']['embed_source'],
                    'starttime' => $starttime,
                    'endtime' => $endtime
                );
                $block_type = 'embed';
                $addBlock = true;
                break;
            case 'EvaluationBlock':
                //we skip this block type
                break;
            case 'FolderBlock':
                $folder_content = json_decode($block['fields']['folder_content']);
                $payload = array(
                    'title' => $folder_content->folder_title,
                    'folder_id' => $folder_content->folder_id
                );
                $block_type = 'folder';
                $addBlock = true;
                break;
            case 'ForumBlock':
                //we skip this block type
                break;
            case 'GalleryBlock':
                $payload = array(
                    'folder_id' => $block['fields']['gallery_folder_id'],
                    'autoplay' => $block['fields']['gallery_autoplay'] === '1' ? 'true' : 'false',
                    'autoplay_timer' => $block['fields']['gallery_autoplay_timer'],
                    'nav' =>  $block['fields']['gallery_hidenav'] === '1' ? 'false' :'true',
                    'height' => $block['fields']['gallery_height'],
                    'show_filenames' => $block['fields']['gallery_show_names'] === '1' ? 'true' : 'false',
                );
                $block_type = 'gallery';
                $addBlock = true;
                break;
            case 'HtmlBlock':
                $block_type = 'text';
                $payload = array(
                    'text' => $block['fields']['content']
                );
                $addBlock = true;
                break;
            case 'IFrameBlock':
                $cc = json_decode($block['fields']['cc_infos'])[0];
                $payload = array(
                    'title' => $block['fields']['header'],
                    'url' =>  $block['fields']['url'],
                    'height' =>  $block['fields']['height'],
                    'submit_user_id' =>  $block['fields']['submit_user_id'] ? 'true' : 'false',
                    'submit_param' =>  $block['fields']['submit_param'],
                    'salt' =>  $block['fields']['salt'],
                    'cc_info' =>  $cc->cc_type,
                    'cc_work' =>  $cc->work_name . ' ' . $cc->work_url,
                    'cc_author' =>  $cc->author_name . ' ' . $cc->author_url,
                    'cc_base' =>  $cc->license_name . ' ' . $cc->license_url,
                );
                $block_type = 'iframe';
                $addBlock = true;
                break;
            case 'ImageMapBlock':
                $image_map_content = json_decode($block['fields']['image_map_content']);
                $shapes = $image_map_content->shapes;
                foreach($shapes as &$shape) {
                    $shape->data->color = $shape->data->colorName;
                    unset($shape->data->colorName);
                    unset($shape->data->fillStyle);
                    if($shape->link_type === 'internal') {
                        $shape->target_internal = $shape->target;
                        $shape->target_external = '';
                    }
                    if($shape->link_type === 'external') {
                        $shape->target_internal = '';
                        $shape->target_external = $shape->target;
                    }
                    unset($shape->target);
                }
                $payload = array(
                    'file_id' => $image_map_content->image_id,
                    'shapes' => $shapes
                );
                $block_type = 'image-map';
                $addBlock = true;
                break;
            case 'InteractiveVideoBlock':
                // we need a block for this type!!!
                break;
            case 'KeyPointBlock':
                $payload = array(
                    'text' => $block['fields']['keypoint_content'],
                    'color' =>  $block['fields']['keypoint_color'],
                    'icon' =>  $block['fields']['keypoint_icon']
                );
                $block_type = 'key-point';
                $addBlock = true;
                break;
            case 'LinkBlock':
                $linkTarget = $block['fields']['link_target'];
                $type = $block['fields']['link_type'];
                $target = '';
                $url = '';
                if($type === 'external') {
                    $url = $linkTarget;
                }
                $payload = array(
                    'type' => $type,
                    'target' =>  $target,
                    'url' =>  $url,
                    'title' =>  $block['fields']['link_title']
                );
                $block_type = 'link';
                $addBlock = true;
                break;
            case 'OpenCastBlock':
                // we need a block for this type!!!
                break;
            case 'PdfBlock':
                $payload = array(
                    'title' => $block['fields']['pdf_title'],
                    'file_id' =>  $block['fields']['pdf_file_id'],
                    'downloadable' =>  $block['fields']['pdf_disable_download'] ? 'false' : 'true',
                    'doc_type' => 'pdf'
                );
                $block_type = 'document';
                $addBlock = true;
                break;
            case 'PostBlock':
                // convert this to a TextBlock and put content into comments
                $block_type = 'text';
                $payload = array(
                    'text' => $block['fields']['post_title']
                );
                $addBlock = true;
                break;
            case 'ScrollyBlock':
                //we skip this block type
                break;
            case 'SearchBlock':
                //we skip this block type
                break;
            case 'TestBlock':
                // we need a block for this!!!
                break;
            case 'TypewriterBlock':
                $typewriter = json_decode($block['fields']['typewriter_json']);
                $payload = array(
                    'text' => $typewriter->content,
                    'speed' =>  $typewriter->speed,
                    'font' =>  $typewriter->font,
                    'size' => $typewriter->size
                );
                $block_type = 'typewriter';
                $addBlock = true;
                break;
            case 'VideoBlock':
                $webvideo = json_decode($block['fields']['webvideo'])[0];
                if ($webvideo->source === 'url') {
                    $source = 'web';
                    $file_id = '';
                    $web_url = $webvideo->src;
                }
                if ($webvideo->source === 'file') {
                    $source = 'studip';
                    $file_id = $webvideo->file_id;
                    $web_url = '';
                }
                $payload = array(
                    'title' => $block['fields']['videoTitle'],
                    'source' => $source,
                    'file_id' => $file_id,
                    'web_url' => $web_url,
                    'aspect' => $block['fields']['aspect'],
                    'context_menu' => strpos($block['fields']['webvideosettings'], 'oncontextmenu') > -1 ? 'disabled' : 'enabled',
                    'autoplay' => strpos($block['fields']['webvideosettings'], 'autoplay') > -1 ? 'enabled' : 'disabled'
                );
                $block_type = 'video';
                $addBlock = true;
                break;
            default:
                //skip all exotic blocks
                break;
        }
        if($addBlock){
            $new_block = Block::build([
                'container_id' => $container->id,
                'owner_id' => $user->id,
                'editor_id' => $user->id,
                'edit_blocker_id' => null,
                'position' => $container->countBlocks(),
                'block_type' => $block_type,
                'payload' => json_encode($payload),
                'visible' => 1,
            ]);
            $new_block->store();
        } else {
            $new_block = null;
        }

        if($block['type'] === 'PostBlock') {
            $thread_id = $block['fields']['thread_id'];
            $posts = \Mooc\DB\Post::findBySQL('thread_id = ? AND post_id > 0', array($thread_id));
            foreach($posts as $post) {
                $block_comment = \Courseware\BlockComment::build([
                    'block_id' => $new_block->id,
                    'user_id' => $post['user_id'],
                    'comment' => $post['content'],
                    'mkdate' =>  $this->convertCoursewareDate($post['mkdate']),
                    'chdate' => $this->convertCoursewareDate($post['chdate'])
                ]);
                $block_comment->store();
            }
        }

        if($block['type'] === 'AudioGallery') {
            //todo copy audio files into course
        }
        if($block['type'] === 'Canvas') {
            //todo migrate user data -> drawings
        }
        if($block['type'] === 'TestBlock') {
            //todo migrate user data -> tries
        }
        if($block['type'] === 'InteractiveVideoBlock') {
            //todo migrate user data -> tries
        }

        return $new_block;
    }

    private function convertCoursewareDate($date)
    {
        $new_date = date_create_from_format('Y-m-d H:i:s', $date);
        return date_timestamp_get($new_date);
    }
}
