<?php

namespace Mooc\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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
        $this->addArgument('cid', InputArgument::OPTIONAL, 'Which course should be migrated?');
    }
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $startStyle = new OutputFormatterStyle('yellow', 'default', ['bold']);
        $output->getFormatter()->setStyle('start', $startStyle);

        $successStyle = new OutputFormatterStyle('green', 'default', ['bold']);
        $output->getFormatter()->setStyle('success', $successStyle);

        $skipedStyle = new OutputFormatterStyle('green', 'default', []);
        $output->getFormatter()->setStyle('skiped', $skipedStyle);

        $batchInfoStyle = new OutputFormatterStyle('cyan', 'default', []);
        $output->getFormatter()->setStyle('batch-info', $batchInfoStyle);

        $this->plugin_courseware = \PluginManager::getInstance()->getPlugin('Courseware');
        \PluginEngine::getPlugin('CoreForum');

        $arg_cid = $input->getArgument('cid');

        if ($arg_cid !== null) {
            $output->writeln('<start>Start single migration ...</start>');
            $courseware =  dbBlock::findOneBySQL('type = ? AND seminar_id = ?', array('Courseware', $arg_cid));
            if ($courseware !== null) {
                $this->migrateCourse($courseware, $output);
            } else {
                $output->writeln('<comment>Could not find Courseware in Course: ' . $arg_cid . '</comment>');
            }
        } else {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('<question>Are you sure you want to migrate all coursewares at once? (y|n)</question> ', false);
            if(!$helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Migration canceled.</comment>');
                $output->writeln('<info>You can migrate individual courses if you use the course id as an argument.</info>');
                return 0;
            }
            $output->writeln('<start>Start batch migration ...</start>');
            $coursewares =  dbBlock::findBySQL('type = ?', array('Courseware'));
            $coursewares_count = count($coursewares);
            if ($coursewares_count === 1) {
                $output->writeln('<batch-info>Batch migration for only one Courseware.</batch-info>');
            } else {
                $output->writeln('<batch-info>Batch migration for ' . $coursewares_count . ' Coursewares.</batch-info>');
            }

            foreach($coursewares as $courseware) {
                $this->migrateCourse($courseware, $output);
            }
        }

        $output->writeln('<success>Migration complete.</success>');

        return 0;
    }

    private function migrateCourse($courseware, $output)
    {
        $cid = $courseware->seminar_id;
        $course = \Course::find($cid);

        if(empty($course)) {
            $output->writeln('<skiped>Course (' . $cid . ')' . ' does not exist in database.</skiped>');
            return false;
        }

        $plugin_manager = \PluginManager::getInstance();
        $plugin_info = $plugin_manager->getPluginInfo('CoursewareModule');
        $plugin_manager->setPluginActivated($plugin_info['id'], $cid, true);

        $is_migrated = \Mooc\DB\MigrationStatus::findBySQL('seminar_id = ?', array($cid));
        if($is_migrated) {
            $output->writeln('<skiped>Courseware for ' . $course->name . '(' . $course->id . ')' . ' has already been migrated.</skiped>');
            return false;
        }
        $grouped = $this->getGrouped($cid, true);
        $courseware = current($grouped['']);
        $this->buildTree($grouped, $courseware);
        $this->createNewCourseware($courseware, $output);
        $status = \Mooc\DB\MigrationStatus::build([
            'seminar_id' => $cid,
            'mkdate' => time()
        ]);
        $status->store();

        return true;
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
                $arr['db_block'] = $item;
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
        $output->write('creating Courseware for: ' . $course->name . '(' . $course->id . ')' . '... ');
        $courseTeacher = $course->getMembersWithStatus('dozent')[0];
        $teacher = \User::find($courseTeacher->user_id);

        //get courseware settings
        $settings = [];
        $settings['progression'] = ($courseware['ui_block']->progression);
        $settings['editing_permission'] = ($courseware['ui_block']->editing_permission);

        $root = StructuralElement::getCoursewareCourse($cid);

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
        $subchapter_map = [];
        $section_map = [];
        $new_link_blocks = [];
        $structural_element_counter = 0;
        $container_counter = 0;
        $block_counter = 0;
        foreach($courseware['children'] as $chapter) {
            $new_chapter = $this->createStructuralElement($chapter, $root->id, $teacher, $cid, $output);
            $structural_element_counter++;
            foreach($chapter['children'] as $subchapter) {
                $new_subchapter = $this->createStructuralElement($subchapter, $new_chapter->id, $teacher, $cid, $output);
                $structural_element_counter++;
                $subchapter_map[$subchapter['id']] = $new_subchapter->id;
                foreach($subchapter['children'] as $section) {
                    $new_section = $this->createStructuralElement($section, $new_subchapter->id, $teacher, $cid, $output);
                    $structural_element_counter++;
                    $section_map[$section['id']] = $new_section->id;
                    $new_container = $this->createContainer($new_section->id, $teacher, $output);
                    $container_counter++;
                    foreach($section['children'] as $block) {
                        $create_new_block = $this->createBlock($block, $new_container, $teacher, $cid, $courseware['ui_block'], $output);
                        $new_block = $create_new_block['new_block'];
                        if($new_block === null) {
                          continue;
                        }
                        $block_counter++;
                        if($new_block->type->getType() === 'link') {
                            array_push($new_link_blocks, array('block' => $new_block, 'link_target' => $create_new_block['link_target']));
                        }
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
        foreach($new_link_blocks as $link_block) {
            $new_link_block = $link_block['block'];
            $old_target = $link_block['link_target']['id'];
            $payload = json_decode($new_link_block->payload);
            if ($payload->type === 'internal') {
                if($link_block['link_target']['element'] === 'section') {
                    $payload->target = $section_map[$old_target];
                }
                if($link_block['link_target']['element'] === 'subchapter') {
                    $payload->target = $subchapter_map[$old_target];
                }
                $new_link_block->payload = json_encode($payload);
                $new_link_block->store();
            }
        }

        $output->write('<success>');
        $output->write($structural_element_counter . ' structural elements, ');
        $output->write($container_counter . ' containers and ');
        $output->write($block_counter . ' blocks ');
        $output->write('were created.');
        $output->writeln('</success>');

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
    private function createBlock($block, $container, $user, $cid, $courseware, $output)
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
                $audioGalleryFolderName = 'AudioGallery';
                $audioGalleryFolder = \FileManager::createSubFolder(
                    \FileManager::getTypedFolder($rootFolder->id),
                    $user,
                    $folder_type,
                    $audioGalleryFolderName,
                    ''
                );
                $audioGalleryFolder->__set('download_allowed', 1);
                $audioGalleryFolder->store();
                $payload = array(
                    'title' => '',
                    'source' => 'studip_folder',
                    'file_id' => '',
                    'folder_id' => $audioGalleryFolder->id,
                    'web_url' => '',
                    'recorder_enabled' => true
                );
                $block_type = 'audio';
                $addBlock = true;
                break;
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
                $source = json_decode($block['fields']['source']);
                $payload = array(
                    'assignment_id' => $block['fields']['assignment_id'],
                    'overlays' =>  $block['fields']['iav_overlays'],
                    'stops' =>  $block['fields']['iav_stops'],
                    'tests' =>  $block['fields']['iav_tests'],
                    'file_id' => $source->file_id,
                    'file_name' => $source->file_name,
                    'external_source' => $source->external,
                    'range_inactive' => block['fields']['range_inactive']
                );
                $block_type = 'interactive-video';
                $addBlock = true;
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
                //var_dump($block['fields']);die;
                $payload = [
                    'series_id'  => '',
                    'episode_id' => '', $block['fields'][''],
                    'url' => $block['fields']['url_opencast']
                ];
                $block_type = 'opencast-block';
                $addBlock = true;
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
                $payload = array(
                    'assignment_id' => $block['fields']['assignment_id']
                );
                $block_type = 'TestBlock';
                $addBlock = true;
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

        if($new_block && $block['type'] === 'PostBlock') {
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

        if($new_block && $block['type'] === 'AudioGalleryBlock') {
            $recordings = \Mooc\DB\Field::findBySQL('block_id = ? AND name = ?', array($block['id'], "audio_gallery_user_recording"));
            foreach($recordings as $record) {
                $data = json_decode($record['json_data']);
                $recording_user = \User::find($data->user_id);
                $file_ref = \FileRef::find($data->file_ref_id);
                if ($file_ref !== null) {
                    $record_file = \FileManager::copyFile(
                        $file_ref->getFiletype(),
                        $audioGalleryFolder,
                        $recording_user
                    );
                    $record_file_ref = $record_file->getFileRef();
                    $record_file_ref->name = $recording_user->getFullName() . '_' . $record_file_ref->name;
                    $record_file_ref->store();
                }

            }
        }
        if($new_block && $block['type'] === 'CanvasBlock') {
            $drawings = \Mooc\DB\Field::findBySQL('block_id = ? AND name = ?', array($block['id'], "canvas_draw"));
            foreach($drawings as $draw) {
                $data = json_decode($draw['json_data']);
                $canvas_draw = json_decode($data);
                $clickX = json_decode($canvas_draw->clickX);
                foreach($clickX as &$cx) {
                    $cx = intval($cx * 1.26);
                }
                $canvas_draw->clickX = json_encode($clickX);
                $clickY = json_decode($canvas_draw->clickY);
                foreach($clickY as &$cy) {
                    $cy = intval($cy * 1.26);
                }
                $canvas_draw->clickY = json_encode($clickY);
                $payload = array(
                    'canvas_draw' => $canvas_draw
                );
                $user_data_field = \Courseware\UserDataField::build([
                    'user_id' => $draw['user_id'],
                    'block_id' => $new_block->id,
                    'payload' => json_encode($payload)
                ]);
                $user_data_field->store();
            }
        }

        $link_target = [];
        if($new_block && $block['type'] === 'LinkBlock') {
            $target = $block['fields']['link_target'];
            $id = null;

            if ($target == "next") {
                $id = $courseware->getNeighborSections($block['db_block']->parent)["next"]["id"];
                $link_target['element'] = 'section';
            }

            if ($target == "prev") {
                $id = $courseware->getNeighborSections($block['db_block']->parent)["prev"]["id"];
                $link_target['element'] = 'section';
            }

            if (strpos($target, "sibling") > -1) {
                $num = (int) substr($target, 7);
                $id = $block['db_block']->parent->parent->parent->children[$num][
                    "id"
                ];
                $link_target['element'] = 'subchapter';
            }

            if (strpos($target, "other") > -1) {
                $chapter_pos = substr($target, 5);
                $chapter_pos = (int) strtok($chapter_pos, "_cpos");
                $subchapter_pos = (int) substr(
                    $target,
                    strpos($target, "_item") + 5
                );
    
                $thischapter = $block['db_block']->parent->parent->parent;
                $allchapters = $thischapter->parent->children;
                $i = 0;
                $this_chapter_pos = "";
                foreach ($allchapters as $chapter) {
                    if ($thischapter->id == $chapter->id) {
                        $this_chapter_pos = $i;
                    }
                    $i++;
                }

                $chatper = $allchapters[$this_chapter_pos + $chapter_pos];
                $id = $chatper->children[$subchapter_pos]["id"];
                $link_target['element'] = 'subchapter';
            }
            $link_target['id'] = $id;
        }

        return array('new_block' => $new_block, 'link_target' => $link_target);
    }

    private function convertCoursewareDate($date)
    {
        $new_date = date_create_from_format('Y-m-d H:i:s', $date);
        return date_timestamp_get($new_date);
    }
}
