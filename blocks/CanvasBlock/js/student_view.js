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
        'click .cw-canvasblock-clear': 'clear',
        'click .cw-canvasblock-color': 'changeColor',
        'click .cw-canvasblock-size': 'changeSize'
    },

    initialize() { },

    render() {
        return this;
    },

    postRender() {
        var $view = this;
        var canvas = $view.$('.cw-canvasblock-canvas')[0];
        canvas.width = 800;
        canvas.height = 400;
        this.context = canvas.getContext( '2d' );
        this.paint = false;
        this.clickX = new Array();
        this.clickY = new Array();
        this.clickDrag = new Array();

        this.colors = {
                'blue': 'rgba(52,152,219 ,1)',
                'green': 'rgba(46,204,113 ,1)',
                'purple': 'rgba(155,89,182 ,1)',
                'red': 'rgba(231,76,60 ,1)',
                'yellow': 'rgba(254, 211, 48, 1)',
                'orange': 'rgba(243,156,18 ,1)',
                'grey': 'rgba(149,165,166 ,1)',
                'darkgrey': 'rgba(52,73,94 ,1)'
        };
        this.clickColor = new Array();
        this.currentColor = this.colors['blue'];

        this.sizes = {'small': 2, 'normal': 5, 'large': 8, 'huge': 12};
        this.clickSize = new Array();
        this.currentSize = this.sizes['normal'];

        this.redraw();
    },

    mouseDown(e) {
        var mouseX = e.offsetX;
        var mouseY = e.offsetY;
        this.paint = true;
        this.addClick(e.offsetX, e.offsetY);
        this.redraw();
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
    },

    redraw() {
        var context = this.context;
        var clickX = this.clickX;
        var clickY = this.clickY;
        var clickDrag = this.clickDrag;

        var outlineImage = new Image();
        outlineImage.src = this.$('.cw-canvasblock-bgimage').val();
        
        context.clearRect(0, 0, context.canvas.width, context.canvas.height); // Clears the canvas
        context.drawImage(outlineImage, 0, 0, context.canvas.width, context.canvas.height);
        context.lineJoin = "round";

        for(var i=0; i < clickX.length; i++) {
            context.beginPath();
            if(clickDrag[i] && i){
              context.moveTo(clickX[i-1], clickY[i-1]);
             }else{
               context.moveTo(clickX[i]-1, clickY[i]);
             }
             context.lineTo(clickX[i], clickY[i]);
             context.closePath();
             context.strokeStyle = this.clickColor[i];
             context.lineWidth = this.clickSize[i];
             context.stroke();
             
        }
    },

    clear() {
        this.clickX.length = 0;
        this.clickY.length = 0;
        this.clickDrag.length = 0;
        this.clickColor.length = 0;
        this.clickSize.length = 0;
        this.redraw();
    },

    changeColor(e) {
        var color = e.target.value;
        this.currentColor = this.colors[color];
    },

    changeSize(e) {
        var size = e.target.value;
        this.currentSize = this.sizes[size];
    }
});
