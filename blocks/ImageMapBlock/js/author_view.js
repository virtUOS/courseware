import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({
    events: {
        'click button[name=save]' : 'onSave',
        'click button[name=cancel]' : 'switchBack',
        'mousedown .cw-image-map-canvas' : 'mouseDownListener',
        'mousemove .cw-image-map-canvas' : 'mouseMoveListener',
        'mouseup .cw-image-map-canvas' : 'mouseUpListener',
        'click .cw-image-map-canvas': 'selectShape',
        'click .add-shape': 'addShape',
        'click .remove-shape': 'removeShape',
        'click .cw-image-map-color': 'selectColor',
        'click .cw-image-map-resize': 'shapeResize',
        'input .shape-text': 'changeText',
        'input .cw-image-map-shape-link-target': 'changeTarget',
        'click .cw-image-map-shape-link-target option': 'changeTarget',
        'change .cw-image-map-shape-link-protocol': 'changeTarget',
        'change .cw-image-map-shape-link-type': 'selectLinkType',
        'input .cw-image-map-shape-title': 'changeTitle',
        'change select.cw-image-map-file' : 'selectFile'
    },

    initialize() {
        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
        this.colors = {
            'transparent': 'rgba(0,0,0,0)',
            'white': 'rgba(255,255,255,1)',
            'blue': 'rgba(52,152,219,1)',
            'green': 'rgba(46,204,113,1)',
            'purple': 'rgba(155,89,182,1)',
            'red': 'rgba(231,76,60,1)',
            'yellow': 'rgba(254,211,48,1)',
            'orange': 'rgba(243,156,18,1)',
            'grey': 'rgba(236, 240, 241,1)',
            'darkgrey': 'rgba(52,73,94,1)',
            'black': 'rgba(0,0,0,1)'
        };
        this.darkColors = ['black', 'darkgrey', 'purple'];
    },

    render() {
        return this;
    },

    postRender() {
        let view = this;

        this.$('.cw-image-map-file').select2({
            templateResult: state => {
              if (!state.id) { return state.text; }
              var $state = $(
                '<span data-filename="' + state.element.dataset.filename +'">' + state.text + '</span>'
              );
              return $state;
            }
        });

        // set colors
        this.$('.cw-image-map-color').each(function(index) {
            let color = $(this).val();
            $(this).css('background-color', view.colors[color]);
        });
        this.selected_color = 'transparent';
        this.$('.cw-image-map-color[value="transparent"]').addClass('selected-color');

        //hide input fields
        this.$('.remove-shape').hide();
        this.$('.resize-buttons').hide();
        this.$('.cw-image-map-resize').hide();

        let content = this.$('.cw-image-map-stored-content').val();
        if (content != '') {
            content = JSON.parse(content);
            this.shapes = content.shapes;
            //this.$('.cw-image-map-file option[file-id="'+content.image_id+'"]').prop('selected', true);
            this.$('.cw-image-map-file').val(content.image_id).trigger('change');
            this.$('.cw-image-map-source option[value="cw"]').prop('selected', true);

        } else {
            this.shapes = [];
        }
        this.setFormContent();
        this.selectFile();


        return this;
    },

    buildCanvas($original_img) {
        let canvas = this.$('.cw-image-map-canvas')[0];
        canvas.width = 860;
        if ($original_img[0].height > 0) {
            canvas.height = Math.round((canvas.width / $original_img[0].width) * $original_img[0].height);
        } else {
            canvas.height = 484;
        }
        $original_img.hide();

        this.context = canvas.getContext( '2d' );
        this.drawScreen();
    },

    drawScreen(){
        var context = this.context;
        let view = this;
        var outlineImage = new Image();
        outlineImage.src = this.$('.cw-image-map-original-img').attr('src');
        $(outlineImage).on('load', function() {// chrome needs this!
            context.clearRect(0, 0, context.canvas.width, context.canvas.height); // Clears the canvas
            context.fillStyle = "#ffffff";
            context.fillRect(0, 0, context.canvas.width, context.canvas.height); // set background
            if (outlineImage.src != '') { 
                context.drawImage(outlineImage, 0, 0, context.canvas.width, context.canvas.height);
            }
            view.drawShapes();
        });

        if (outlineImage.src == '') {
            $(outlineImage).trigger('load');
        }
    },

    drawShapes(){
        let context = this.context;
        let view = this;
        $.each(this.shapes, function(key, value) {
            let shape = value;
            let text = shape.data.text;
            let shape_width = 0, shape_height = 0, text_X = 0, text_Y = 0;

            context.beginPath();
            switch (shape.type) {
                case 'arc':
                    shape_width =  Math.round((2*shape.data.radius)/Math.sqrt(2));
                    shape_height =  shape_width;
                    text_X = shape.data.centerX;
                    text_Y = shape.data.centerY - shape.data.radius*0.75;
                    context.arc(shape.data.centerX, shape.data.centerY, shape.data.radius, 0, 2 * Math.PI); // x, y, r, startAngle, endAngle ... Angle in radians!
                    context.fillStyle = shape.data.fillStyle;
                    context.fill();
                    break;
                case 'ellipse':
                    shape_width = shape.data.radiusX;
                    shape_height = shape.data.radiusY*1.75;
                    text_X = shape.data.X ;
                    text_Y = shape.data.Y - shape.data.radiusY*0.8;
                    context.ellipse(shape.data.X, shape.data.Y, shape.data.radiusX, shape.data.radiusY, 0, 0, 2 * Math.PI);
                    context.fillStyle = shape.data.fillStyle;
                    context.fill();
                    break;
                case 'rect':
                    shape_width = shape.data.width;
                    shape_height = shape.data.height;
                    text_X = shape.data.X + shape.data.width/2;
                    text_Y = shape.data.Y;
                    context.rect(shape.data.X, shape.data.Y, shape.data.width, shape.data.height);
                    context.fillStyle = shape.data.fillStyle;
                    context.fill();
                    break;
                default:
                    return;
            }

            if ((text) && (shape.data.colorName != 'transparent')) {
                text = view.fitTextToShape(context, text, shape_width);
                context.textAlign = "center"; 
                context.font = "14px Arial"
                if (view.darkColors.indexOf(shape.data.colorName) > -1) {
                    context.fillStyle = '#ffffff';
                } else { 
                    context.fillStyle = '#000000';
                }
                let lineHeight = shape_height/(text.length+1);
                $.each(text, function(key, value){
                    context.fillText(value, text_X, text_Y + lineHeight*(key+1));
                });
            }

            if (shape.data.border) {
                context.lineWidth = 1;
                context.stroke();
            }

            if (view.shape_selection_index == key) {
                context.lineWidth = 3;
                context.stroke();
            }

            context.closePath();
        });
    },

    fitTextToShape(context, text, shapeWidth) {
        shapeWidth = shapeWidth || 0;

        let newText = [];
        
        if (shapeWidth <= 0) {
            return [text];
        }
        let words = text.split(' ');
        let i = 1;
        while (words.length > 0 && i <= words.length) {
            let word = words.slice(0, i).join(' ');
            let wordWidth = context.measureText(word).width + 2;
            if ( wordWidth > shapeWidth ) {
                if (i === 1) {
                    i = 2;
                }
                newText.push(words.slice(0, i - 1).join(' '));
                words = words.splice(i - 1);
                i = 1;
            }
            else {
                i++;
            }
        }
        if (i > 0) {
            newText.push(words.join(' '));
        }

        return newText;
    },

    hitTest(mouseX, mouseY, shape) {
        if (shape.type == 'arc') {
            let dx = shape.data.centerX - mouseX;
            let dy = shape.data.centerY - mouseY;

            return(dx*dx + dy*dy < shape.data.radius*shape.data.radius);
        }
        if ((shape.type == 'rect') || (shape.type == 'text')) {
            let dx = mouseX - shape.data.X ;
            let dy = mouseY - shape.data.Y;

            return((dx <= shape.data.width) && (dy <= shape.data.height) && (dx >= 0) && (dy >= 0));
        }
        if (shape.type == 'ellipse') {
            let dx = shape.data.X - mouseX ;
            let dy = shape.data.Y - mouseY;

            return ((Math.abs(dx) < shape.data.radiusX) && (Math.abs(dy) < shape.data.radiusY));
        }
    },

    mouseDownListener(event) {
        let canvas = this.$('.cw-image-map-canvas')[0];
        let bRect = canvas.getBoundingClientRect();
        let mouseX = (event.clientX - bRect.left)*(canvas.width/bRect.width);
        let mouseY = (event.clientY - bRect.top)*(canvas.height/bRect.height);
        let view = this;
        this.shape_selected = false;
        this.shape_dragging = false;
        this.shape_selection_index = null;

        $.each(this.shapes, function(key, value) {
            if (view.hitTest(mouseX, mouseY, value)) {
                view.shape_selected = true;
                view.shape_dragging = true;
                view.shape_selection_index = key;
            }
        });
    },

    mouseMoveListener(event) {
        if (!this.shape_dragging) {
            return;
        }
        let canvas = this.$('.cw-image-map-canvas')[0];
        let bRect = canvas.getBoundingClientRect();
        let targetX = Math.round((event.clientX - bRect.left)*(canvas.width/bRect.width));
        let targetY = Math.round((event.clientY - bRect.top)*(canvas.height/bRect.height));
        let shape = this.shapes[this.shape_selection_index];
        switch(shape.type) {
            case 'arc':
                shape.data.centerX = shape.data.centerX + 0.45*(targetX - shape.data.centerX);
                shape.data.centerY = shape.data.centerY + 0.45*(targetY - shape.data.centerY);
                break;
            case 'rect':
            case 'text':
                shape.data.X = shape.data.X + (targetX - shape.data.X) - 0.5 * shape.data.width;
                shape.data.Y = shape.data.Y + (targetY - shape.data.Y) - 0.5 * shape.data.height;
                break;
            case 'ellipse':
                shape.data.X = shape.data.X + (targetX - shape.data.X);
                shape.data.Y = shape.data.Y + (targetY - shape.data.Y);
                break;
        }
        this.drawScreen();
    },

    mouseUpListener(event) {
        this.shape_dragging = false;
    },

    selectShape() {
        let canvas = this.$('.cw-image-map-canvas')[0];
        let bRect = canvas.getBoundingClientRect();
        let mouseX = (event.clientX - bRect.left)*(canvas.width/bRect.width);
        let mouseY = (event.clientY - bRect.top)*(canvas.height/bRect.height);
        let view = this;

        this.shape_selected = false;
        this.shape_dragging = false;
        this.shape_selection_index = null;
        this.$('.add-shape').show();
        this.$('.remove-shape').hide();

        $.each(this.shapes, function(key, value) {
            if (view.hitTest(mouseX, mouseY, value)) {
                view.shape_selected = true;
                view.shape_selection_index = key;
                view.$('.cw-image-map-color[value="'+value.data.colorName+'"]').click();

                return false; // break the each
            }
        });
        view.setFormContent();
        this.drawScreen();
    },

    setFormContent(){
        let shape = this.shapes[this.shape_selection_index];
        this.$('.cw-image-map-shape-title').val('');
        this.$('.shape-text').val('');
        this.$('.resize-buttons').hide();
        this.$('.cw-image-map-resize').hide();
        this.$('.cw-image-map-shape-link-type option[value="external"]').prop('selected', true);
        this.$('input.cw-image-map-shape-link-target').val('');

        if (shape) {
            this.$('.shape-text').prop('disabled', false);
            this.$('.cw-image-map-shape-link-type').prop('disabled', false);
            this.$('.cw-image-map-shape-link-protocol').prop('disabled', false);
            this.$('.cw-image-map-shape-link-target').prop('disabled', false);
            this.$('.cw-image-map-shape-title').prop('disabled', false);
            this.$('.cw-image-map-data-input').removeClass('disabled');
            this.$('.resize-buttons').show();

            switch (shape.type) {
                case 'arc':
                    this.$('.resize-arc').show();
                    break;
                case 'rect':
                case 'ellipse':
                    this.$('.resize-rect').show();
                    break;
                case 'text':
                    this.$('.resize-rect').show();
                    break;
            }
            this.$('.add-shape').hide();
            this.$('.remove-shape').show();
            this.$('.shape-target').val(shape.target);
            this.$('.shape-text').val(shape.data.text);
            this.$('.cw-image-map-shape-title').val(shape.title);
            this.$('.cw-image-map-shape-link-type option[value="'+shape.link_type+'"]').prop('selected', true);
            switch (shape.link_type) {
                case 'external':
                    this.$('select.cw-image-map-shape-link-target').hide();
                    this.$('input.cw-image-map-shape-link-target').show();
                    this.$('.cw-image-map-shape-link-protocol').show();
                    this.$('input.cw-image-map-shape-link-target').val(shape.target.replace('http://','').replace('https://',''));
                    if (shape.target.indexOf('https://') > -1) {
                        this.$('.cw-image-map-shape-link-protocol option[value="https://"]').prop('selected', true);
                    }
                    break;
                case 'internal':
                    this.$('select.cw-image-map-shape-link-target option[value="'+shape.target+'"]').prop('selected', true);
                    this.$('select.cw-image-map-shape-link-target').show();
                    this.$('input.cw-image-map-shape-link-target').hide();
                    this.$('.cw-image-map-shape-link-protocol').hide();
                    break;
                default:
                    this.$('select.cw-image-map-shape-link-target').hide();
                    this.$('input.cw-image-map-shape-link-target').show();
                    this.$('.cw-image-map-shape-link-protocol').show();
                    this.$('.cw-image-map-shape-link-type option[value="external"]').prop('selected', true);
            }
        } else {
            this.$('.shape-text').prop('disabled', true);
            this.$('.cw-image-map-shape-link-type').prop('disabled', true);
            this.$('.cw-image-map-shape-link-protocol').prop('disabled', true);
            this.$('.cw-image-map-shape-link-target').prop('disabled', true);
            this.$('.cw-image-map-shape-title').prop('disabled', true);
            this.$('.cw-image-map-data-input').addClass('disabled');
            this.$('select.cw-image-map-shape-link-target').hide();
            this.$('input.cw-image-map-shape-link-target').show();
            this.$('.cw-image-map-shape-link-protocol').show();
            this.$('.cw-image-map-shape-link-type option[value="external"]').prop('selected', true);
        }
    },

    addShape(event) {
        let shape = {};
        shape.type = this.$(event.target).data('shape-type');
        shape.data = {};
        switch (shape.type) {
            case 'arc':
                shape.data.centerX = 60;
                shape.data.centerY = 60;
                shape.data.radius = 50;
                break;
            case 'rect':
                shape.data.X = 60;
                shape.data.Y = 60;
                shape.data.width = 100;
                shape.data.height = 50;
                break;
            case 'ellipse':
                shape.data.X = 60;
                shape.data.Y = 60;
                shape.data.radiusX = 50;
                shape.data.radiusY = 20;
                break;
            default: 

                return;
        }
        shape.data.fillStyle = this.getColor();
        shape.data.colorName = this.selected_color;
        if (shape.data.colorName == 'transparent') {
            shape.data.border = true;
        }
        this.shape_selection_index = this.shapes.push(shape)-1;
        this.shape_selected = true;
        this.setFormContent();

        this.drawScreen();
    },

    removeShape(event) {
        this.shapes.splice(this.shape_selection_index, 1);
        this.shape_selection_index = null;
        this.shape_selected = false;
        this.shape_dragging = false;
        this.selectShape();
        this.drawScreen();
    },

    shapeResize(event) {
        let button = this.$(event.target);
        let resize = button.data('resize');
        let shape = this.shapes[this.shape_selection_index];
        switch(resize) {
            case 'raise-radius':
                shape.data.radius = shape.data.radius + 5;
                break;
            case 'reduce-radius':
                    if (shape.data.radius > 5) {
                        shape.data.radius = shape.data.radius - 5;
                    }
                break;
            case 'raise-width':
                shape.data.width = shape.data.width + 10;
                shape.data.radiusX = shape.data.radiusX + 10;
                break;
            case 'reduce-width':
                    if ((shape.data.width > 10) || shape.data.radiusX > 10 ) {
                        shape.data.width = shape.data.width - 10;
                        shape.data.radiusX = shape.data.radiusX - 10;
                    }
                break;
            case 'raise-height':
                shape.data.height = shape.data.height + 10;
                shape.data.radiusY = shape.data.radiusY + 10;
                break;
            case 'reduce-height':
                if ((shape.data.height > 10)|| (shape.data.radiusY > 10)) {
                    shape.data.height = shape.data.height - 10;
                    shape.data.radiusY = shape.data.radiusY - 10;
                }
                break;
            default:
                break;
        }
        this.drawScreen();
    },

    selectColor(event) {
        let button = this.$(event.target);
        this.$('.cw-image-map-color').removeClass('selected-color');
        button.addClass('selected-color');
        this.selected_color = button.val();

        if (this.shape_selected) {
            let shape = this.shapes[this.shape_selection_index];
            shape.data.fillStyle = this.getColor();
            shape.data.colorName = this.selected_color;
            if (this.selected_color == 'transparent') {
                shape.data.border = true;
            } else {
                shape.data.border = false;
            }
            this.drawScreen();
        }
    },

    getColor() {
        return this.colors[this.selected_color];
    },

    changeText() {
        let shape = this.shapes[this.shape_selection_index];
        if (shape) {
            shape.data.text = this.$('.shape-text').val();
            this.drawScreen();
        }
    },

    changeTarget() {
        let shape = this.shapes[this.shape_selection_index];
        let link_type = this.$('.cw-image-map-shape-link-type option:selected').val();

        if (shape) {
            shape.link_type = link_type;
            if (link_type == 'external') {
                shape.target = this.$('.cw-image-map-shape-link-protocol option:selected').val()+this.$('input.cw-image-map-shape-link-target').val();
            }
            if (link_type == 'internal') {
                shape.target = this.$('select.cw-image-map-shape-link-target option:selected').val();
            }
        }
    },

    selectLinkType() {
        let link_type = this.$('.cw-image-map-shape-link-type option:selected').val();
        if (link_type == 'internal') {
            this.$('select.cw-image-map-shape-link-target').show();
            this.$('input.cw-image-map-shape-link-target').hide();
            this.$('.cw-image-map-shape-link-protocol').hide();
        }
        if (link_type == 'external') {
            this.$('select.cw-image-map-shape-link-target').hide();
            this.$('input.cw-image-map-shape-link-target').show();
            this.$('.cw-image-map-shape-link-protocol').show();
        }
        this.changeTarget();
    },

    changeTitle() {
        let shape = this.shapes[this.shape_selection_index];
        if (shape) {
            shape.title = this.$('.cw-image-map-shape-title').val();
        }
    },

    selectFile() {
        let view = this;
        let url = this.$('.cw-image-map-file ').find(':selected').data('url');

        this.$('.cw-image-map-original-img').attr('src', url);
        let $original_img = this.$('.cw-image-map-original-img');
        this.buildCanvas($original_img);

        $original_img.on('load', function(){
            view.buildCanvas($original_img);
        });
    },

    onNavigate(event) {
        if (!$('section .block-content button[name=save]').length) {
            return;
        }
        if (event.isUserInputHandled) {
            return;
        }
        event.isUserInputHandled = true;
        Backbone.trigger('preventnavigateto', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite trotzdem verlassen?'));
    },

    onModeSwitch(toView, event) {
        if (toView != 'student') {
            return;
        }
        // the user already switched back (i.e. the is not visible)
        if (!this.$el.is(':visible')) {
            return;
        }
        // another listener already handled the user's feedback
        if (event.isUserInputHandled) {
            return;
        }
        event.isUserInputHandled = true;
        Backbone.trigger('preventviewswitch', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie trotzdem fortfahren?'));
    },

    onSave(event) {
        var view = this;
        let content = {};
        content.shapes = this.shapes;
        content.image_url = '';
        content.image_id = this.$('select.cw-image-map-file').val();
        content.image_name = this.$('select.cw-image-map-file').find(':selected').data('filename');
        if (content.image_id != '') {
            content.image = true;  
        } else {
            content.image = false;
        }
        content = JSON.stringify(content);
        helper
        .callHandler(this.model.id, 'save', {
              image_map_content : content
        })
        .then(
            // success
            function () {
                $(event.target).addClass('accept');
                view.switchBack();
            },

            // error
            function (error) {
                console.log(error);
            }
        );
    }

});
