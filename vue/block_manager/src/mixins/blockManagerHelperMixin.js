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
        getContent(node) {
            var json = {};
            switch (node.getAttribute('type')) {
                case 'AssortBlock':
                    return '';
                case 'AudioBlock':
                    return node.getAttribute('audio:audio_file_name');
                case 'AudioGalleryBlock':
                    return '';
                case 'BeforeAfterBlock':
                    json = JSON.parse(node.getAttribute('beforeafter:ba_before'));
                    if (json.source == 'url') {
                        return json.url;
                    }
                    return json.file_name;
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
                    return node.getAttribute('code:code_content');
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
                    return node.textContent;
                case 'IFrameBlock':
                    return node.getAttribute('iframe:url');
                case 'ImageMapBlock':
                    return JSON.parse(node.getAttribute('imagemap:image_map_content')).image_name;
                case 'InteractiveVideoBlock':
                    return JSON.parse(node.getAttribute('interactivevideo:iav_source')).file_name;
                case 'KeyPointBlock':
                    return node.getAttribute('keypoint:keypoint_content');
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
                    return json.content;
                case 'VideoBlock':
                    if (node.getAttribute('video:webvideo') != '') {
                        json = JSON.parse(node.getAttribute('video:webvideo'))[0];
                        if (json.source == 'url') {
                            return json.src;
                        } else {
                            return json.file_name;
                        }
                    }
                    return node.getAttribute('video:url');
                default:
                    return '';
            }
        }
    }
};
