import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
    events: {
    },

    initialize() { },

    render() {
        return this;
    },

    postRender() {
        if (typeof pdfjsLib === 'undefined') {
            console.log("ERROR: pdfjsLib not found!");
            return;
        }

        var $view = this;
        var url = $view.$('.cw-pdf-file-url').val();
        if(url == "") {
            $view.$('.cw-pdf-wrapper').hide();

            return;
        }

        var pdfDoc = null,
            pageNum = 1,
            pageRendering = false,
            pageNumPending = null,
            scale = 2,
            canvas = $view.$('.cw-pdf-canvas')[0],
            ctx = canvas.getContext('2d');

        function renderPage(num) {
            pageRendering = true;
            pdfDoc.getPage(num).then(function(page) {
                var viewport = page.getViewport({ scale });
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                var renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                var renderTask = page.render(renderContext);

                renderTask.promise.then(function() {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
            });

            $view.$(".cw-pdf-page-num").html(pageNum);
        }

        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }

        function nextPage() {
            if (pageNum <= 1) {
                return;
            }
            pageNum--;
            queueRenderPage(pageNum);
            if(pageNum > 1) {
                $view.$(".cw-pdf-button-prev").prop('title', 'Seite '+(pageNum-1));
            }
            else {
                $view.$(".cw-pdf-button-prev").prop('title', '');
            }
            $view.$(".cw-pdf-button-next").prop('title', 'Seite '+(pageNum+1));
        }

        function  prevPage() {
          if (pageNum >= pdfDoc.numPages) {
            return;
          }
          pageNum++;
          queueRenderPage(pageNum);
            if (pageNum < pdfDoc.numPages) {
                $view.$(".cw-pdf-button-next").prop('title', 'Seite '+(pageNum+1));
            }
            else {
              $view.$(".cw-pdf-button-next").prop('title', '');
            }
            $view.$(".cw-pdf-button-prev").prop('title', 'Seite '+(pageNum-1));
        }

        $(document).keypress( function(e) {
            if (e.defaultPrevented) {
                return;
            }
            var key = e.key || e.keyCode;
            if(key === 'ArrowRight' || key === 37) {
                prevPage();
            }
            if(key === 'ArrowLeft' || key === 38) {
                nextPage();
            }
        });

        $view.$(".cw-pdf-button-prev").click( function() {
            nextPage();
        });

        $view.$(".cw-pdf-button-next").click( function() {
            prevPage();
        });

        pdfjsLib.getDocument(url).promise.then(function(pdfDoc_) {
            pdfDoc = pdfDoc_;
            $view.$(".cw-pdf-page-count").html(pdfDoc.numPages);
            renderPage(pageNum);
        });

        return this;
    }
});
