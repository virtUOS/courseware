<?php

/**
 * This migration ensures that all blocks on all levels are enumerated properly.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class FixPositions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Fixes positions on blocks';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $db = DBManager::get();

        $stmt = $db->prepare(
            'SELECT
              DISTINCT parent_id
            FROM
              mooc_blocks
            WHERE
              parent_id > 0
            ORDER BY
              parent_id'
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $selectStmt = $db->prepare(
            'SELECT
              id
            FROM
              mooc_blocks
            WHERE
              parent_id = :parent_id
            ORDER BY
              position'
        );
        $updateStmt = $db->prepare(
            'UPDATE
              mooc_blocks
            SET
              position = :position
            WHERE
              id = :id'
        );

        foreach ($rows as $row) {
            $selectStmt->bindValue(':parent_id', $row['parent_id']);
            $selectStmt->execute();
            $childRows = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

            for ($i = 0; $i < count($childRows); $i++) {
                $updateStmt->bindValue(':position', $i);
                $updateStmt->bindValue(':id', $childRows[$i]['id']);
                $updateStmt->execute();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
    }
}
