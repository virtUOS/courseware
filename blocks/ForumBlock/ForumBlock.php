<?php

namespace Mooc\UI\ForumBlock;

use Mooc\UI\Block;
use Mooc\UI\Section\Section;

/**
 * Display the contents of a Blubber stream in a (M)ooc.IP block.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class ForumBlock extends Block
{
    const NAME = 'Diskussion';

    public function initialize()
    {
        $this->defineField('area_id', \Mooc\SCOPE_BLOCK, -1);

        $seminar_id = $this->container['cid'];
        $user_id    = $this->container['current_user']->id;

        // predefined category must have the id md5('mooc' . seminar_id)
        $category_id = md5('mooc' . $seminar_id);

        // check, that correct categories and areas present
        if (!\ForumCat::get($category_id)) {
            $stmt = \DBManager::get()->prepare("INSERT INTO forum_categories
                (category_id, seminar_id, entry_name)
                VALUES (?, ?, ?)");

            $stmt->execute(array($category_id, $seminar_id, 'Diskussionen zu den Aufgaben'));
        }

        if ($this->area_id == -1 || !\ForumEntry::getConstraints($this->area_id)) {
            $this->area_id = md5(uniqid());

            $parent = $this->getModel();
            while ($parent = $parent->parent) {
                if ($parent->type == 'Courseware') {
                    break;
                }
                $path[] = $parent->title;
            }

            \ForumEntry::insert(array(
                'topic_id'    => $this->area_id,
                'seminar_id'  => $seminar_id,
                'user_id'     => $user_id,
                'name'        => implode(' > ', array_reverse($path)),
                'author'      => get_fullname(),
                'author_host' => getenv('REMOTE_ADDR')
            ), $seminar_id);

            \ForumCat::addArea($category_id, $this->area_id);
        }
    }

    public function student_view()
    {
        // on view: grade with 100%
        $this->setGrade(1.0);

        // get 5 most recent topics
        $topics = \ForumEntry::getList('list', $this->area_id);
        $topics = array_values(array_slice($topics['list'], 0, 5));

        foreach ($topics as $key => $topic) {
            $topics[$key]['human_date']   = date('d.m.Y', $topic['chdate']);
            $topics[$key]['link_to_post'] = \URLHelper::getLink('plugins.php/coreforum/index/index/' . $topic['topic_id']);
        }

       return array(
            'topics'       => $topics,
            'link_to_area' => \URLHelper::getLink('plugins.php/coreforum/index/index/' . $this->area_id)
        );
    }

    public function author_view()
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function isEditable()
    {
        return false;
    }
}
