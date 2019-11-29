export default {
    methods: {
        cutTitle(title, length = 30) {
            return title.length > length ? title.substr(0, length - 1) + '…' : title;
        },
        getReadableDate(date) {
            let datetime = new Date(date);
            return (
                ('0' + datetime.getDate()).slice(-2) +
                '.' +
                ('0' + (datetime.getMonth() + 1)).slice(-2) +
                '.' +
                datetime.getFullYear()
            );
        },
        getContent(node, unziped) {
            var json = {};
            switch (node.getAttribute('type')) {
                case 'AssortBlock':
                    return '';
                case 'AudioBlock':
                    var filename = node.getAttribute('audio:audio_file_name');
                    var file_id = node.getAttribute('audio:audio_id');
                    if (file_id != '') {
                        var audio_blob = this.getAudioBlob(unziped, filename);
                        return '<p>'+filename+'</p><audio controls width="100%"><source src="'+audio_blob+'"/></audio>';
                    }
                    return '<p>'+filename+'</p><audio controls width="100%"><source src="'+filename+'"/></audio>';

                case 'AudioGalleryBlock':
                    return '';
                case 'BeforeAfterBlock':
                    json = JSON.parse(node.getAttribute('beforeafter:ba_before'));
                    if (json.source == 'url') {
                        return json.url;
                    }
                    var image = new Image;
                    image.src = this.getImageBlob(unziped, json.file_name);
                    return image.outerHTML;
                case 'BlubberBlock':
                    return '';
                case 'CanvasBlock':
                    json = JSON.parse(node.getAttribute('canvas:canvas_content'));
                    if (json.source == 'web') {
                        return json.image_url;
                    }
                    if (json.source == 'cw') {
                        return json.image_name;
                    }
                    return '';
                case 'ChartBlock':
                    return node.getAttribute('chart:chart_type');
                case 'CodeBlock':
                    return this.escapeHtml(node.getAttribute('code:code_content'));
                case 'ConfirmBlock':
                    return node.getAttribute('title');
                case 'DateBlock':
                    json = JSON.parse(node.getAttribute('date:date_content'));
                    return json.date + ' ' + json.time;
                case 'DialogCardsBlock':
                    json = JSON.parse(node.getAttribute('dialogcards:dialogcards_content'));
                    return json[0].front_img_file_name;
                case 'DiscussionBlock':
                    return '';
                case 'EmbedBlock':
                    return node.getAttribute('embed:embed_source') + ' ' + node.getAttribute('embed:embed_url');
                case 'FolderBlock':
                    return '';
                case 'ForumBlock':
                    return '';
                case 'GalleryBlock':
                    return node.getAttribute('gallery:gallery_folder_name');
                case 'HtmlBlock':
                    return this.escapeHtml(node.textContent);
                case 'IFrameBlock':
                    return node.getAttribute('iframe:url');
                case 'ImageMapBlock':
                    var image_name = JSON.parse(node.getAttribute('imagemap:image_map_content')).image_name;
                    var image = new Image;
                    image.src = this.getImageBlob(unziped, image_name);
                    return image.outerHTML;
                case 'InteractiveVideoBlock':
                    var video_blob = this.getVideoBlob(unziped, JSON.parse(node.getAttribute('interactivevideo:iav_source')).file_name);
                    return '<video controls width="100%"><source src="'+video_blob+'"/></video>';
                case 'KeyPointBlock':
                    return this.escapeHtml(node.getAttribute('keypoint:keypoint_content'));
                case 'LinkBlock':
                    return node.getAttribute('link:link_target');
                case 'OpenCastBlock':
                    return '';
                case 'PdfBlock':
                    return node.getAttribute('pdf:pdf_filename');
                case 'PostBlock':
                    return '';
                case 'SearchBlock':
                    return '';
                case 'TestBlock':
                    var xml = node.getAttribute('test:xml');
                    var parser = new DOMParser();
                    var xmlDoc = parser.parseFromString(xml, 'text/xml');
                    return xmlDoc.firstElementChild.children[0].textContent;
                case 'TypewriterBlock':
                    json = JSON.parse(node.getAttribute('typewriter:typewriter_json'));
                    return this.escapeHtml(json.content);
                case 'VideoBlock':
                    if (node.getAttribute('video:webvideo') != '') {
                        json = JSON.parse(node.getAttribute('video:webvideo'))[0];
                        if (json.source == 'url') {
                            return json.src;
                        } else {
                            var video_blob = this.getVideoBlob(unziped, json.file_name);
                            return '<video controls width="100%"><source src="'+video_blob+'"/></video>';
                        }
                    }
                    return node.getAttribute('video:url');
                default:
                    return '';
            }
        },
        getImageBlob(unziped, filename) {
            var keys = Object.keys(unziped.files);
            var image = '';
            keys.forEach((value, key) => {
                if (value.indexOf(filename) > -1) {
                    if (value.indexOf('png') > -1) {
                        image = unziped.extractAsBlobUrl(value, 'image/png');
                    }
                    else if (value.indexOf('gif') > -1) {
                        image = unziped.extractAsBlobUrl(value, 'image/gif');
                    }
                    else if ((value.indexOf('jpg') > -1) || (value.indexOf('jpeg') > -1)){
                        image = unziped.extractAsBlobUrl(value, 'image/jpeg');
                    }
                    return;
                }
            });
            return image;
        },
        getVideoBlob(unziped, filename) {
            var keys = Object.keys(unziped.files);
            var video = '';
            keys.forEach((value, key) => {
                if (value.indexOf(filename) > -1) {
                    if (value.indexOf('mp4') > -1) {
                        video = unziped.extractAsBlobUrl(value, 'video/mp4');
                    }
                    else if (value.indexOf('ogg') > -1) {
                        video = unziped.extractAsBlobUrl(value, 'video/ogg');
                    }
                    else if (value.indexOf('webm') > -1) {
                        video = unziped.extractAsBlobUrl(value, 'video/webm');
                    }
                    else if ((value.indexOf('mpg') > -1) || (value.indexOf('mpeg') > -1)|| (value.indexOf('mpe') > -1)){
                        video = unziped.extractAsBlobUrl(value, 'video/mpeg');
                    }
                    return;
                }
            });
            return video;
        },
        getAudioBlob(unziped, filename) {
            var keys = Object.keys(unziped.files);
            var audio = '';
            keys.forEach((value, key) => {
                if (value.indexOf(filename) > -1) {
                    if (value.indexOf('mp3') > -1) {
                        audio = unziped.extractAsBlobUrl(value, 'audio/mpeg');
                    }
                    else if (value.indexOf('mp4') > -1) {
                        audio = unziped.extractAsBlobUrl(value, 'audio/mp4');
                    }
                    else if (value.indexOf('ogg') > -1) {
                        audio = unziped.extractAsBlobUrl(audio, 'audio/ogg');
                    }
                    else if (value.indexOf('wav') > -1) {
                        audio = unziped.extractAsBlobUrl(audio, 'audio/wav');
                    }
                    return;
                }
            });
            return audio;
        },
        escapeHtml(text) {
            var map = {
              '&': '&amp;',
              '<': '&lt;',
              '>': '&gt;',
              '"': '&quot;',
              "'": '&#039;'
            };
          
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    }
};
