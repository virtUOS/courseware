<?php
namespace Mooc;

use Mooc\DB\Block as DbBlock;
use Mooc\UI\Block as UiBlock;

/**
 * @author  <mlunzena@uos.de>
 *
 * @property string $perms
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
        if ($model instanceof UiBlock) {
            $model = $model->getModel();
        }

        if ($model instanceof DbBlock) {
            return $this->hasPerm($model->seminar_id, 'dozent');
        }

        throw new \RuntimeException('not implemented: ' . __METHOD__);
    }

    public function canRead($model)
    {
        if ($model instanceof UiBlock) {
            $model = $model->getModel();
        }

        if ($this->canUpdate($model)) {
            return true;
        }

        if ($model instanceof DbBlock) {
            return $model->isPublished() && $this->hasPerm($model->seminar_id, 'user');
        }

        throw new \RuntimeException('not implemented: ' . __METHOD__);
    }

    public function canUpdate($model)
    {
        if ($model instanceof UiBlock) {
            $model = $model->getModel();
        }

        if ($model instanceof DbBlock) {
            return $this->hasPerm($model->seminar_id, 'tutor');
        }

        throw new \RuntimeException('not implemented: ' . __METHOD__);
    }

    public function canDelete($model)
    {
        if ($model instanceof UiBlock) {
            $model = $model->getModel();
        }

        if ($model instanceof DbBlock) {
            return $this->hasPerm($model->seminar_id, 'dozent');
        }

        throw new \RuntimeException('not implemented: ' . __METHOD__);
    }

    public function hasPerm($cid, $perm_level)
    {
        if (!$cid) {
            return false;
        }

        /** @var \Seminar_Perm $perm */
        $perm = $GLOBALS['perm'];

        return $perm->have_studip_perm($perm_level, $cid, $this->id);
    }

    public function getPerm()
    {
        return $this->perms;
    }
}
