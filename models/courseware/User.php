<?php

namespace Courseware;

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
     * with given value. Give null to create new record.
     *
     * @param \Courseware\Container $container the DI container to use
     * @param mixed                 $id        primary key of table
     */
    public function __construct(Container $container, $id = null)
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
            return $this->canEditBlock($model);
        }

        throw new \RuntimeException('not implemented: '.__METHOD__);
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
            $perm = false;
            if ($this->isNobody()) {
                $course = \Course::find($model->seminar_id);
                $perm = get_config('ENABLE_FREE_ACCESS') && $course->lesezugriff == 0;
            } else {
                $perm = $this->hasPerm($model->seminar_id, 'user');
            }

            return $model->isPublished() && $perm;
        }

        throw new \RuntimeException('not implemented: '.__METHOD__);
    }

    public function canUpdate($model)
    {
        if ($model instanceof UiBlock) {
            $model = $model->getModel();
        }

        if ($model instanceof DbBlock) {
            return $this->canEditBlock($model);
        }

        throw new \RuntimeException('not implemented: '.__METHOD__);
    }

    public function canDelete($model)
    {
        if ($model instanceof UiBlock) {
            $model = $model->getModel();
        }

        if ($model instanceof DbBlock) {
            return $this->canEditBlock($model);
        }

        throw new \RuntimeException('not implemented: '.__METHOD__);
    }

    public function hasPerm($cid, $perm_level)
    {
        if (!$cid) {
            return false;
        }

        return $GLOBALS['perm']->have_studip_perm($perm_level, $cid, $this->id);
    }

    public function getPerm($cid)
    {
        if (!$cid) {
            return false;
        }

        return $GLOBALS['perm']->get_studip_perm($cid, $this->id);
    }

    public function isNobody()
    {
        return $this->id === 'nobody';
    }

    /////////////////////
    // PRIVATE HELPERS //
    /////////////////////

    // get the editing permission level from the courseware's settings
    private function canEditBlock(DbBlock $block)
    {
        if ($this->isNobody()) {
            return false;
        }

        // optimistically get the current courseware
        $courseware = $this->container['current_courseware'];

        // if the $block is not a descendant of it, get its courseware
        if ($courseware->getModel()->seminar_id !== $block->seminar_id) {
            $courseware_model = $this->container['courseware_factory']->makeCourseware($block->seminar_id);
            $courseware = $this->container['block_factory']->makeBlock($courseware_model);
        }

        return $this->hasPerm($block->seminar_id, $courseware->getEditingPermission());
    }
}
