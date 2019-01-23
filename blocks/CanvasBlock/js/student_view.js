import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
    events: {
        'mousedown .cw-canvasblock-canvas' :'mouseDown',
        'mousemove .cw-canvasblock-canvas': 'mouseMove',
        'mouseup .cw-canvasblock-canvas' :'mouseUp',
        'mouseout .cw-canvasblock-canvas' :'mouseUp',
        'mouseleave .cw-canvasblock-canvas' :'mouseUp',
        'click .cw-canvasblock-reset': 'reset',
        'click .cw-canvasblock-color': 'changeColor',
        'click .cw-canvasblock-size': 'changeSize',
        'click .cw-canvasblock-tool': 'changeTool', 
        'click .cw-canvasblock-download' : 'downloadImage',
        'click .cw-canvasblock-undo': 'undoDraw'
    },

    initialize() { },

    render() {
        return this;
    },

    postRender() {
        var $view = this;
        var $original_img = $view.$('.cw-canvasblock-original-img');
        $view.buildCanvas($original_img);

        $original_img.on('load', function(){
            $view.buildCanvas($original_img);
        });
    },

    buildCanvas($original_img) {
        var $view = this;
        var $canvas = $view.$('.cw-canvasblock-canvas');
        var img = $original_img[0];
        var canvas = $canvas[0];
        canvas.width = 860;
        if ($original_img[0].height > 0) {
            canvas.height = Math.round((canvas.width / $original_img[0].width) * $original_img[0].height);
        } else {
            canvas.height = 484;
        }
        $original_img.hide();
        this.context = canvas.getContext( '2d' );
        this.paint = false;
        this.write = false;
        this.clickX = new Array();
        this.clickY = new Array();
        this.clickDrag = new Array();

        this.colors = {
                'white': 'rgba(255,255,255,1)',
                'blue': 'rgba(52,152,219,1)',
                'green': 'rgba(46,204,113,1)',
                'purple': 'rgba(155,89,182,1)',
                'red': 'rgba(231,76,60,1)',
                'yellow': 'rgba(254,211,48,1)',
                'orange': 'rgba(243,156,18,1)',
                'grey': 'rgba(149,165,166,1)',
                'darkgrey': 'rgba(52,73,94,1)'
        };
        this.$('.cw-canvasblock-color').each(function(index){
            let color = $(this).val();
            $(this).css('background-color', $view.colors[color]);
        });
        
        this.clickColor = new Array();
        this.currentColor = this.colors['blue'];
        this.$('.cw-canvasblock-color[value="blue"]').addClass('selected-color');

        this.sizes = {'small': 2, 'normal': 5, 'large': 8, 'huge': 12};
        this.clickSize = new Array();
        this.currentSize = this.sizes['normal'];
        this.$('.cw-canvasblock-size-normal').addClass('selected-size');

        this.tools = {'pen': 'pen', 'text': 'text'}
        this.clickTool = new Array();
        this.currentTool = this.tools['pen'];
        this.$('.cw-canvasblock-tool-pen').addClass('selected-tool');

        this.Text = new Array();

        $canvas.addClass('cw-canvasblock-tool-selected-'+this.currentTool);

        this.redraw();
    },

    mouseDown(e) {
        if (this.write) {
            return;
        }
        var mouseX = e.offsetX;
        var mouseY = e.offsetY;
        if(this.currentTool == 'pen') {
            this.paint = true;        
            this.addClick(e.offsetX, e.offsetY, false);
            this.redraw();
        }
        if(this.currentTool == 'text') {
            this.write = true;
            this.addClick(e.offsetX, e.offsetY, false);
        }
    },

    mouseMove(e) {
        if(this.paint){
            this.addClick(e.offsetX, e.offsetY, true);
            this.redraw();
        }
    },

    mouseUp(e) {
        this.paint = false;
    },

    addClick(x, y, dragging) {
        this.clickX.push(x);
        this.clickY.push(y);
        this.clickDrag.push(dragging);
        this.clickColor.push(this.currentColor);
        this.clickSize.push(this.currentSize);
        this.clickTool.push(this.currentTool);
        if (this.currentTool == 'text') {
           this.enableTextInput(x, y);
        } else {
            this.Text.push('');
        }
    },

    redraw() {
        var context = this.context;
        var clickX = this.clickX;
        var clickY = this.clickY;
        var clickDrag = this.clickDrag;

        var outlineImage = new Image();
        outlineImage.src = this.$('.cw-canvasblock-original-img').attr('src');

        context.clearRect(0, 0, context.canvas.width, context.canvas.height); // Clears the canvas
        context.fillStyle = "#ffffff";
        context.fillRect(0, 0, context.canvas.width, context.canvas.height); // set background
        context.drawImage(outlineImage, 0, 0, context.canvas.width, context.canvas.height);
        context.lineJoin = "round";

        for(var i=0; i < clickX.length; i++) {
            if (this.clickTool[i] == 'pen') {
                context.beginPath();
                if(clickDrag[i] && i) {
                    context.moveTo(clickX[i-1], clickY[i-1]);
                 } else {
                     context.moveTo(clickX[i]-1, clickY[i]);
                 }
                 context.lineTo(clickX[i], clickY[i]);
                 context.closePath();
                 context.strokeStyle = this.clickColor[i];
                 context.lineWidth = this.clickSize[i];
                 context.stroke();
            }
            if (this.clickTool[i] == 'text') {
                let fontsize = this.clickSize[i]*6;
                context.font = fontsize+"px Arial";
                context.fillStyle = this.clickColor[i];
                context.fillText(this.Text[i], clickX[i], clickY[i]+fontsize); 
                
            }
        }
    },

    reset() {
        this.clickX.length = 0;
        this.clickY.length = 0;
        this.clickDrag.length = 0;
        this.clickColor.length = 0;
        this.clickSize.length = 0;
        this.clickTool.length = 0;
        this.Text.length = 0;
        this.$('input.cw-canvasblock-text-input').remove();
        this.paint = false;
        this.write = false;
        this.redraw();
    },

    changeColor(e) {
        var color = e.target.value;
        this.$('.cw-canvasblock-color').removeClass('selected-color');
        $(e.target).addClass('selected-color');
        this.currentColor = this.colors[color];
    },

    changeSize(e) {
        var size = e.target.value;
        this.$('.cw-canvasblock-size').removeClass('selected-size');
        $(e.target).addClass('selected-size');
        this.currentSize = this.sizes[size];
    },

    changeTool(e) {
        var tool = e.target.value;
        this.$('.cw-canvasblock-tool').removeClass('selected-tool');
        $(e.target).addClass('selected-tool');
        var $canvas = this.$('.cw-canvasblock-canvas');
        this.currentTool = this.tools[tool];
        $canvas.removeClass('cw-canvasblock-tool-selected-pen').removeClass('cw-canvasblock-tool-selected-text');
        $canvas.addClass('cw-canvasblock-tool-selected-'+this.currentTool);
    },

    enableTextInput(x, y) {
        var $view = this;
        this.$('input.cw-canvasblock-text-input').remove();
        let fontsize = this.currentSize*6;
        $view.$('.cw-canvasblock-canvas').before('<input class="cw-canvasblock-text-input">');
        var $input = this.$('input.cw-canvasblock-text-input');
        $input.ready(function(){
            $input.focus();
        });
        $input.css('position', 'absolute');
        $input.css('top', (this.$('canvas')[0].offsetTop + y) + 'px');
        $input.css('left', x +'px');
        $input.css('line-height', fontsize +'px');
        $input.css('font-size', fontsize +'px');
        $input.css('max-width', '300px');

        $input[0].addEventListener('keyup', function(e){
            if (e.defaultPrevented) {
                return;
            }
            var key = e.key || e.keyCode;
            if (key === 'Enter' || key === 13) { 
                $view.Text.push($input.val());
                $view.$('input.cw-canvasblock-text-input').remove();
                $view.write = false;
                $view.redraw();
            }
            if (key === 'Escape' || key === 'Esc' || key === 27) { 
                $view.clickX.pop();
                $view.clickY.pop();
                $view.clickDrag.pop();
                $view.clickColor.pop();
                $view.clickSize.pop();
                $view.clickTool.pop();
                $view.$('input.cw-canvasblock-text-input').remove();
                $view.write = false;
            }
        }, false);

    },

    downloadImage() {
        var image = this.context.canvas.toDataURL();
        $("<a/>", {
            "class": "cw-canvasblock-download-link",
            "text": 'download',
            "title": 'download',
            "href": image,
            "download" : "cw-img.png"
        }).appendTo(this.$el);

        var link = this.$('.cw-canvasblock-download-link');
        link[0].click();
        link.remove();
    },

    undoDraw() {
        var dragging = this.clickDrag[this.clickDrag.length -1];
        
        this.clickX.pop();
        this.clickY.pop();
        this.clickDrag.pop();
        this.clickColor.pop();
        this.clickSize.pop();
        this.clickTool.pop();
        this.Text.pop('');

        if (dragging){
            this.undoDraw();
        }

        this.redraw();
    }
});
