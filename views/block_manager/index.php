<? $body_id = 'courseware-blockmanager'; ?>
  <script>
  $( function() {
    $('.chapter-list').sortable().disableSelection();
    $('.subchapter-list').sortable({connectWith:'.subchapter-list'}).disableSelection();
    $('.section-list').sortable({connectWith:'.section-list', placeholder: "highlight"}).disableSelection();
    $('.block-list').sortable({connectWith:'.block-list', placeholder: "highlight"}).disableSelection();
    $('p').siblings('ul').hide();
    $('p').click(function(e){
        $(this).siblings('ul').toggle();
        if(!$(this).hasClass('unfolded')) {
            $(this).addClass('unfolded');
        } else {
            $(this).removeClass('unfolded');
        }
    });
  } );
  </script>
<h1><?= _cw('Block Manager') ?></h1>
<div class="clear"></div>
<ul class="chapter-list">
    <? foreach($courseware['children'] as $chapter): ?>
        <li class="chapter-item" data-id="<?= $chapter['id']?>">
            <p class="chapter-description"><?= $chapter['title']?> <span>Kapitel</span></p>
            <ul class="subchapter-list">
                <? foreach($chapter['children'] as $subchapter): ?>
                    <li class="subchapter-item" data-id="<?= $subchapter['id']?>">
                        <p class="subchapter-description"><?= $subchapter['title'] ?> <span>Unterkapitel</span></p>
                        <ul class="section-list">
                            <? foreach($subchapter['children'] as $section):?>
                                <li class="section-item" data-id="<?= $section['id']?>">
                                    <p class="section-description"><?= $section['title']?> <span>Abschnitt</span></p>
                                    <ul class="block-list">
                                    <? foreach($section['children'] as $block):?>
                                        <li class="block-item cw-block-icon-<?=$block['type']?>" data-id="<?= $block['id']?>"><?= $block['type'] ?></li>
                                    <? endforeach?>
                                    </ul>
                                </li>
                            <? endforeach?>
                        </ul>
                    </li>
                <? endforeach?>
            </ul>
        </li>
    <? endforeach?>
</ul>
