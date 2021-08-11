<?php

namespace CoursewarePlugin;

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

        // the most upper block must alwazs be readable!
        if ($model->type == 'Courseware') {
            return true;
        }

        if ($this->canUpdate($model)) {
            return true;
        }

        if ($model instanceof DbBlock) {
            $perm = false;
            if ($this->isNobody()) {
                $course = \Course::find($model->seminar_id);
                if (get_config('ENABLE_FREE_ACCESS') && $course->lesezugriff == 0) {
                    // only allow access to blocks which are readable by default
                    $perm = $this->hasReadApproval($model);
                } else {
                    $perm = false;
                }
            } else {
                $perm = $this->hasReadApproval($model);
            }

            return $model->isPublished() && $model->isVisible() && $perm;
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

    public function hasReadApproval(DbBlock $block)
    {
        return $block->hasReadApproval($this->id);
    }

    public function hasWriteApproval(DbBlock $block)
    {
        return $block->hasWriteApproval($this->id);
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
        $courseware = $this->container['current_courseware'];
        if(!$courseware){
            $courseware_model = $block->getCoursewareOfThisBlock();
            $courseware = $this->container['block_factory']->makeBlock($courseware_model);
        }
        $approval = false;
        if (!$block->isStructuralBlock()) {
            if($block->parent != null) {
                $approval = $this->hasWriteApproval($block->parent);
            }
        } else {
            if($block != null) {
                $approval = $this->hasWriteApproval($block);
            }
        }

        return $this->hasPerm($block->seminar_id, $courseware->getEditingPermission()) || $approval;
    }
}
