<? $title = _("Dialog mit dem Ausbilder"); ?>
<? $contentbox_id = "courseware-profile"; ?>

<? foreach ($discussions as $discussion) : ?>

    <? $thread = $discussion->thread; ?>

    <div class="block-content DiscussionBlock">

        <article class="thread open loading" id="<?= htmlReady($thread->id) ?>">
            <header>
                <h1>
                    <?= Course::find($thread->seminar_id)->name ?>
                </h1>
            </header>
            <ul class="comments"></ul>

            <div class="writer">
                <textarea placeholder="<?= _("Kommentiere dies") ?>"
                          aria-label="<?= _("Kommentiere dies") ?>"></textarea>
            </div>

        </article>

    </div>
<? endforeach ?>

<script>
    STUDIP.URLHelper.parameters.cid = "<?= $cid ?>";
</script>

<?= $this->render_partial('courseware/_requirejs', array('main' => 'main-profile')) ?>
