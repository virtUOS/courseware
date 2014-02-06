<li id=section-<?= intval($_section->id) ?>
    class="section <?= $_section->id === $section->id ? ' selected' : '' ?>" >
  <a title="<?= htmlReady($_section->title) ?>">
    <!-- <? var_dump($_section->toArray()) ?> -->
    [<?= $_section->id ?>]
  </a>
</li>
