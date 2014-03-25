<?php

namespace Mooc\TestBlock\Model;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class Exercise extends \SimpleORMap
{
    public function __construct($id = null)
    {
        $this->db_table = 'vips_aufgabe';

        parent::__construct($id);
    }

    /**
     * {@inheritDoc}
     */
    public static function findThru($testId, $options)
    {
        $class = get_called_class();
        $record = new $class();
        $db = \DBManager::get();
        $stmt = $db->prepare(sprintf(
            'SELECT
              t.*
            FROM
              %s AS te
            INNER JOIN
              %s AS t
            ON
              te.%s = t.%s
            WHERE
              te.%s = :test_id
            ORDER BY
              te.position',
            $options['thru_table'],
            $record->db_table,
            $options['thru_assoc_key'],
            $options['assoc_foreign_key'],
            $options['thru_key']
        ));
        $stmt->bindValue(':test_id', $testId);
        $stmt->execute();

        $exercises = array();

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $exercise = new $class();
            $exercise->setData($row, true);
            $exercise->setNew(false);

            $exercises[] = $exercise;
        }

        return $exercises;
    }
}
