<script>
Chart.defaults.global.legend.display = false;
</script>
<h1><?= _("Umfrage") ?></h1>
<? foreach ($survey as $key => $item) :?>
    <h1>Test ID: <?= $key?></h1>
    <div class="survey-block">
        <? foreach ($item as $itemkey => $testaggregation) :?>
            <div class="survey-item">
                <? $type = SurveyController::getTestType($key, $itemkey); ?>
                <h2><?= SurveyController::getFullTestTypeName($type) ?></h2>
                <h3><?= SurveyController::getTestTitle($key, $itemkey); ?></h3>
                <? $label = '['; $data = '['?>
                <? if ($type != 'lt_exercise'): ?>
                    <? foreach($testaggregation as  $testkey => $testvalue): ?>
                        <? $label .= '"'.$testkey.'",' ?>
                        <? $data .= '"'.$testvalue.'",' ?>
                    <? endforeach ?>
                    <? $label .= ']'; $data .= ']';?>
                    <div class="chartcontainer">
                        <canvas id="chart-<?= $itemkey?>"></canvas>
                        <script>
                            var $ctx = $("#chart-<?= $itemkey?>");
                            var $chart = new Chart($ctx, {
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