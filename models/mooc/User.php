<?php
namespace Mooc;

/**
 * @author  <mlunzena@uos.de>
 */
class User extends \User
{

    private $container;

    /**
     * constructor, give primary key of record as param to fetch
     * corresponding record from db if available, if not preset primary key
     * with given value. Give null to create new record
     *
     * @param \Mooc\Container $container  the DI container to use
     * @param mixed           $id         primary key of table
     */
    function __construct(Container $container, $id = null)
    {
        $this->container = $container;
        parent::__construct($id);
    }


    public function canCreate($model)
    {
        // TODO
        throw new \RuntimeException('not implemented: ' . __METHOD__);
    }

    public function canRead($model)
    {
        if ($model instanceof \Mooc\AbstractBlock) {
            return $this->hasPerm($model->seminar_id, 'user');
        }

        throw new \RuntimeException('not implemented: ' . __METHOD__);
    }

    public function canUpdate($model)
    {
        // TODO
        throw new \RuntimeException('not implemented: ' . __METHOD__);
    }

    public function canDelete($model)
    {
        // TODO
        throw new \RuntimeException('not implemented: ' . __METHOD__);
    }


    private function hasPerm($cid, $perm_level)
    {
        if (!$cid) {
            throw new \InvalidArgumentException('$cid may not be null');
        }
        return $GLOBALS['perm']->have_studip_perm($perm_level, $cid, $this->id);
    }
}
