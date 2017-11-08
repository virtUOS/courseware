<?php
namespace Mooc\DB;

/**
 * @author  <rlucke@uos.de>
 *
 * @property int $thread_id
 * @property int $post_id
 * @property string $seminar_id
 * @property string $user_id
 * @property \User $user
 * @property string $user_name
 * @property string $content
 * @property float $mkdate
 * @property float $chdate
 */
class Post extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'mooc_posts';

        $config['belongs_to']['user'] = array(
            'class_name'  => 'User',
            'foreign_key' => 'user_id');

        parent::configure($config);
    }

    public function __construct($id = null)
    {
        parent::__construct($id);
        $this->registerCallback('before_store', 'denyNobodyPost');
    }
    
    public function findPosts($thread_id, $cid)
    {
        $db = \DBManager::get();
        $stmt = $db->prepare("
            SELECT
                *
            FROM
                mooc_posts
            WHERE
                thread_id = :thread_id
            AND
                seminar_id = :cid
        ");
        $stmt->bindParam(":thread_id", $thread_id);
        $stmt->bindParam(":cid", $cid);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function getNextPostId($thread_id, $cid)
    {
        $db = \DBManager::get();
        $stmt = $db->prepare("
            SELECT
                post_id
            FROM
                mooc_posts
            WHERE
                seminar_id = :cid
            AND 
                thread_id = :thread_id
            ORDER BY
                post_id DESC
            LIMIT
                1
        ");
        $stmt->bindParam(":cid", $cid);
        $stmt->bindParam(":thread_id", $thread_id);
        $stmt->execute();
        $post_id = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

        return $post_id[0] + 1;
    }

    public function getThreadIds($cid)
    {
        $db = \DBManager::get();
        $stmt = $db->prepare("
            SELECT
                thread_id, content
            FROM
                mooc_posts
            WHERE
                seminar_id = :cid
            AND
                post_id = 0
            GROUP BY
                thread_id
        ");
        $stmt->bindParam(":cid", $cid);
        $stmt->execute();
        $thread_ids = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return $thread_ids;
    }

    public function newThreadId($cid)
    {
        $db = \DBManager::get();
        $stmt = $db->prepare("
            SELECT
                thread_id
            FROM
                mooc_posts
            WHERE
                seminar_id = :cid
            ORDER BY
                thread_id DESC
            LIMIT
                1
        ");
        $stmt->bindParam(":cid", $cid);
        $stmt->execute();
        $thread_id = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

        return $thread_id[0] + 1;
    }

    public function denyNobodyPost()
    {
        return $this->content['user_id'] != 'nobody';
    }

}
