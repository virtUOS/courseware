<head><link href=../../../plugins_packages/virtUOS/Courseware/views/block_manager/../../assets/vue/css/chunk-vendors.css rel=preload as=style><link href=../../../plugins_packages/virtUOS/Courseware/views/block_manager/../../assets/vue/js/chunk-vendors.js rel=preload as=script><link href=../../../plugins_packages/virtUOS/Courseware/views/block_manager/../../assets/vue/js/index.js rel=preload as=script><link href=../../../plugins_packages/virtUOS/Courseware/views/block_manager/../../assets/vue/css/chunk-vendors.css rel=stylesheet></head><noscript><strong>We're sorry but block_mananger doesn't work properly without JavaScript enabled. Please enable it to continue.</strong></noscript><script>const COURSEWARE = {
        config: {
            cid: <?=json_encode($cid)?> },
        data: {
            courseware: <?=json_encode($courseware_json)?>,
            remote_courses: <?=json_encode($remote_courses_json)?>,
            block_map: <?=json_encode($block_map)?>,
            lang: '<?= $lang ?>',
            courseware_export_url: '<?= $courseware_export_url ?>'
        }
    }</script><div id=block_mananger_content></div><script src=../../../plugins_packages/virtUOS/Courseware/views/block_manager/../../assets/vue/js/chunk-vendors.js></script><script src=../../../plugins_packages/virtUOS/Courseware/views/block_manager/../../assets/vue/js/index.js></script>