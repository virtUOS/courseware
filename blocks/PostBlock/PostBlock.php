<?php

namespace Mooc\UI\PostBlock;

use Mooc\UI\Block;
use Mooc\DB\Post as Post;
use Courseware\User as User;

class PostBlock extends Block
{
    const NAME = 'Kommentare & Diskussion';

    public function initialize()
    {
        $this->defineField('post_title', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('thread_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('has_to_post', \Mooc\SCOPE_BLOCK, false);
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }

        $user = new User($this->container, $this->container['current_user_id']);

        if(!$this->has_to_post){
            $this->setGrade(1.0);
        }
        return array_merge(
            $this->getAttrArray(),
            Post::findPosts($this->thread_id, $this->container['cid'], $this->container['current_user_id']),
            array('nobody' => $user->isNobody())
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $post_ids = Post::getThreadIds($this->container['cid']);

        return array_merge($this->getAttrArray(), array('post_ids' => $post_ids, 'has_to_post' => $this->has_to_post));
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if ($this->post_title == Post::findPost($this->thread_id, 0, $this->container['cid'])["content"]) {
            Post::alterPost($this->thread_id, 0, $this->container['cid'], (string) $data['post_title']);
        }

        if ($data['post_title'] == "") {
            if ($data['thread_id'] == 'new'){
                $this->post_title = 'Diskussion-'. $this->_model->id;
            } else {
                $this->post_title = Post::findOneBySQL('thread_id = ? AND seminar_id = ? AND post_id = 0', 
                                    array($data['thread_id'], $this->container['cid']))->content;
            }
        } else {
             $this->post_title = (string) $data['post_title'];
        }

        if (isset($data['has_to_post'])) {
            $this->has_to_post = $data['has_to_post'];
        } else {
            $this->has_to_post = false;
        }

        if ($data['thread_id'] != 'new'){
            $this->thread_id = (string) $data['thread_id'];
        } else {
            $this->thread_id = Post::newThreadId($this->container['cid']);
            $data = array(
                'thread_id' => $this->thread_id ,
                'post_id' => 0,
                'seminar_id' => $this->container['cid'],
                'user_id' => $this->container['current_user_id'],
                'content' => $this->post_title,
                'mkdate' => (new \DateTime())->format('Y-m-d H:i:s'),
                'chdate' => (new \DateTime())->format('Y-m-d H:i:s')
            );
            Post::create($data);
        }


        return;
    }

    public function message_handler($data)
    {
        if (isset($data['message'])) {
            $this->setGrade(1.0);
            $post_id = Post::getNextPostId($this->thread_id, $this->container['cid']);

            $data = array(
                'thread_id' => $this->thread_id ,
                'post_id' => $post_id,
                'seminar_id' => $this->container['cid'],
                'user_id' => $this->container['current_user_id'],
                'content' => $data["message"],
                'mkdate' => (new \DateTime())->format('Y-m-d H:i:s'),
                'chdate' => (new \DateTime())->format('Y-m-d H:i:s')
            );

            Post::create($data);
        }

        return array();
    }

    public function update_handler($data)
    {
        if (isset($data['timestamp'])) {
            $posts = Post::findBySQL("thread_id ORDER BY mkdate DESC LIMIT 1", array($this->thread_id));

            if (strtotime($posts[0]["mkdate"]) > $data['timestamp']) {
                return array_merge(
                    $this->getAttrArray(), 
                    Post::findPosts($this->thread_id, $this->container['cid'], $this->container['current_user_id']), 
                    array("update" => true, "timestamp" =>strtotime($posts[0]["mkdate"]))
                );
            }
        }

        return array("update" => false);
    }

    private function getAttrArray()
    {
        return array(
            'post_title'    => $this->post_title,
            'thread_id'     => $this->thread_id
        );
    }

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/post/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/post/post-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['post_title'])) {
            $this->post_title = $properties['post_title'];
            $this->thread_id = Post::newThreadId($this->container['cid']);
                $data = array(
                    'thread_id' => $this->thread_id ,
                    'post_id' => 0,
                    'seminar_id' => $this->container['cid'],
                    'user_id' => $this->container['current_user_id'],
                    'content' => $properties['post_title'],
                    'mkdate' => (new \DateTime())->format('Y-m-d H:i:s')
                );
                POST::create($data);
        }
        
        if (isset($properties['has_to_post'])) {
            $this->has_to_post = $properties['has_to_post'];
        }

        $this->save();
    }

}
