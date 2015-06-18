<? $title = _("Dialog mit dem Ausbilder"); ?>
<? $contentbox_id = "courseware-profile"; ?>

<? foreach ($discussions as $discussion) : ?>

    <? $thread = $discussion->thread; ?>

    <div class="block-content DiscussionBlock">

        <article class="thread loading" id="<?= htmlReady($thread->id) ?>" data-cid="<?= htmlReady($discussion->cid) ?>">
            <header>
                <h1>
                    <?= Course::find($discussion->cid)->name ?>
                </h1>
            </header>
            <ul class="comments"></ul>

            <div class="comment-writer">
                <textarea placeholder="<?= _("Kommentiere dies") ?>"
                          aria-label="<?= _("Kommentiere dies") ?>"></textarea>
            </div>

        </article>

    </div>
<? endforeach ?>

<?= $this->render_partial('courseware/_requirejs', array('main' => 'main-profile')) ?>
