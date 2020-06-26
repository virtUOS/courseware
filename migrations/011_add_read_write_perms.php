<?php

class AddReadWritePerms extends Migration
{
    public function description()
    {
        return 'adds a coloum for user editing permissions on chapters and subchapters to mooc_blocks table';
    }

    public function up()
    {
        $db = DBManager::get();

        $result = $db->query("SELECT id, approval
            FROM mooc_blocks
            WHERE approval != ''
                AND approval != '{\"users\":[]}'
                AND approval != '{\"users\":[],\"groups\":[]}'
        ");

        $stmt = $db->prepare("UPDATE mooc_blocks
            SET approval = ?
            WHERE id = ?
        ");

        while ($data = $result->fetch(PDO::FETCH_ASSOC)) {
            $new_app = [];
            $app = json_decode($data['approval'], true);

            if ($app['users']
                && !$app['users']['read']
                && !$app['users']['write']
            ) {
                $new_app['users'][$app['users']] = 'write';
            }

            if ($app['groups']
                && !$app['groups']['read']
                && !$app['groups']['write']
            ) {
                $new_app['groups'][$app['groups']] = 'write';
            }

            if (!empty($new_app)) {
                $stmt->execute([json_encode($new_app), $data['id']]);
            }
        }
    }

    function down()
    {
    }
}
