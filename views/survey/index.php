<script>
Chart.defaults.global.legend.display = false;
</script>
<h1 class="survey-header">
    <p><?= _("Umfrage") ?></p>
    <span>
        <a target="_blank" href="<?= URLHelper::getLink('plugins.php/courseware/survey/print') ?>"><?= Assets::img('icons/16/white/print.png', tooltip2(_('Drucken'))) ?></a>
    </span>
</h1>
    
<? foreach ($survey as $key => $item) :?>

    <h1><?= VipsTest::find($key)->getTitle();?></h1>
    <div class="survey-block">
        <? foreach ($item as $itemkey => $testaggregation) :?>
            <div class="survey-item">
                <? $type = SurveyController::getTestType($key, $itemkey); ?>
                <h2><?= SurveyController::getFullTestTypeName($type) ?></h2>
                <h3><?= SurveyController::getTestTitle($key, $itemkey); ?></h3>
                <? $label = '['; $data = '['?>
                <? if (($type != 'lt_exercise') & ($type != 'tb_exercise')): ?>
                    <? foreach($testaggregation as  $testkey => $testvalue): ?>
                        <? $label .= "'".substr($testkey, 0 , 42)."'," ?>
                        <? $data .= "'".$testvalue."'," ?>
                    <? endforeach ?>
                    <? $label .= ']'; $data .= ']';?>
                    <div class="chartcontainer">
                        <canvas id="chart-<?= $itemkey?>"></canvas>
                        <script>
                            var $ctx = $("#chart-<?= $itemkey?>");
                            var $chart = new Chart($ctx, {
                                options: {
                                    scales: {
                                        yAxes: [{
                                            ticks: {
                                                min: 0,
                                                stepSize: 1
                                            }
                                        }],
                                        xAxes: [{
                                            ticks: {
                                                autoSkip: false
                                            }
                                        }]
                                    }
                                },
                                type: 'bar',
                                
                                data: {
                                    labels: <?= $label?>,
                                    datasets: [{
                                        label: '',
                                        data: <?= $data?>,
                                        backgroundColor: '#007f4b',
                                        borderWidth: 1
                                    }]
                                }
                            });
                            
                        </script>
                    </div>
                <? else: ?>
                    <p>
                    <? foreach($testaggregation as  $testkey => $testvalue): ?>
                        <span class="lt-exercise-list"><?= $testvalue ?></span>
                    <? endforeach ?>
                    </p>
                <? endif ?>
            </div>
            <br>
            <br>
        <? endforeach?>
    </div>
<? endforeach?>
