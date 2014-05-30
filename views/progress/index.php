<?php
$body_id = 'mooc-progress-index';

$courseware = current($grouped['']);
$children = function ($parent) use ($grouped) {
  return $grouped[$parent->id];
};
?>



<h1><?= $courseware->title ?></h1>

<table class=chapters>
  <? foreach ($children($courseware) as $chapter) : ?>
    <tr class=chapter>
      <th colspan=2><?= $chapter->title ?></th>
    </tr>

    <? foreach ($children($chapter) as $subchapter) : ?>
      <tr class=subchapter>
        <th><?= $subchapter->title ?></th>
        <td>
          <ol class=sections>
            <? foreach ($children($subchapter) as $section) : ?>
              <li>
                <a href="#"
                   title="<?= htmlReady($section->title) ?>"
                   data-progress="<?= 75 ?>">
                  <progress value=75
                            max=100><span>75</span>%</progress>
                </a>
              </li>
            <? endforeach ?>
          </ol>
        </td>
      </tr>
    <? endforeach ?>

  <? endforeach ?>
</table>
