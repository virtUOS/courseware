<?php

namespace Mooc\UI\ForumBlock;

use Mooc\UI\Block;
use Mooc\UI\Section\Section;

/**
 * Display the contents of an area of the core forum.
 *
 * @author <tgloeggl@uos.de>
 */
class ForumBlock extends Block
{
    const NAME = 'Diskussion';

    public function initialize()
    {
        $this->defineField('area_id', \Mooc\SCOPE_BLOCK, -1);
        $this->connectForum();
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

    // connect this block with an area of the forum
    private function connectForum()
    {
        $seminar_id = $this->container['cid'];
        $user_id    = $this->container['current_user']->id;

        $category_id = $this->findOrCreateCategory();

        if ($this->area_id != -1 && \ForumEntry::getConstraints($this->area_id)) {
            return;
        }

        $this->area_id = md5(uniqid());

        \ForumEntry::insert(
            array(
                'topic_id'    => $this->area_id,
                'seminar_id'  => $seminar_id,
                'user_id'     => $user_id,
                'name'        => $this->getParentTitles(),
                'author'      => get_fullname(),
                'author_host' => getenv('REMOTE_ADDR')
            ), $seminar_id);

        \ForumCat::addArea($category_id, $this->area_id);
    }

    private function findOrCreateCategory()
    {
        $seminar_id = $this->container['cid'];

        // predefined category must have the id md5('mooc' . seminar_id)
        $category_id = md5('mooc' . $seminar_id);

        // check, that correct categories and areas present
        if (!\ForumCat::get($category_id)) {
            $stmt = \DBManager::get()->prepare("INSERT INTO forum_categories
                (category_id, seminar_id, entry_name)
                VALUES (?, ?, ?)");

            $stmt->execute(array($category_id, $seminar_id, 'Diskussionen zu den Aufgaben'));
        }

        return $category_id;
    }

    // get parents' titles
    private function getParentTitles()
    {
        $path = array();
        $parent = $this->getModel();

        while ($parent = $parent->parent) {
            if ($parent->type == 'Courseware') {
                break;
            }
            $path[] = $parent->title;
        }
        return implode(' > ', array_reverse($path));
    }
}
