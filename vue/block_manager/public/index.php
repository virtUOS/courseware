<noscript>
    <strong>We're sorry but block_mananger doesn't work properly without JavaScript enabled. Please enable it to continue.</strong>
</noscript>
<script>
    const COURSEWARE = {
        config: {
            cid: <?=json_encode($cid)?>
        },
        data: {
            courseware: <?=json_encode($courseware_json)?>,
            remote_courses: <?=json_encode($remote_courses_json)?>,
            block_map: <?=json_encode($block_map)?>,
            lang: '<?= $lang ?>',
            courseware_export_url: '<?= $courseware_export_url ?>',
            courseware_import_url: '<?= $courseware_import_url ?>',
            courseware_export_html_url: '<?= $courseware_export_html_url ?>',
            courseware_export_pdf_url: '<?= $courseware_export_pdf_url ?>'
        }
    }
</script>
<div id="block_mananger_content"></div>
<!-- built files will be auto injected -->
