import $ from 'jquery'
import StudentView from 'js/student_view'

export default StudentView.extend({
    events: {},

    initialize() {
        this.darkColors = ['black', 'darkgrey', 'purple'];
    },

    render() {
        return this;
    },

    postRender() {
        let view = this;
        let content = this.$('.cw-image-map-stored-content').val();
        if (content != '') {
            content = JSON.parse(content);
            this.shapes = content.shapes;
        } else {
            this.shapes = [];
        }

        let $original_img = this.$('.cw-image-map-original-img');
        this.buildCanvas($original_img);

        $original_img.on('load', function(){
            view.buildCanvas($original_img);
        });

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

    drawScreen() {
        let context = this.context;
        let view = this;
        let outlineImage = new Image();
        outlineImage.src = this.$('.cw-image-map-original-img').attr('src');
        $(outlineImage).on('load', function() {// chrome needs this!
            context.clearRect(0, 0, context.canvas.width, context.canvas.height); // Clears the canvas
            context.fillStyle = "#ffffff";
            context.fillRect(0, 0, context.canvas.width, context.canvas.height); // set background
            if (outlineImage.src != '') { 
                context.drawImage(outlineImage, 0, 0, context.canvas.width, context.canvas.height);
            }
            view.drawShapes();

            if (!(view.$('.cw-image-from-canvas').length > 0)) {
                let src = view.context.canvas.toDataURL("image/jpeg", 1.0);
                view.$('.cw-image-map-canvas').hide().after('<img class="cw-image-from-canvas" src="'+src+'">');
                view.mapImage();
            }
        });

        if (outlineImage.src == '') {
            $(outlineImage).trigger('load');
        }
    },

    drawShapes() {
        let context = this.context;
        let view = this;
        $.each(this.shapes, function(key, value) {
            let shape = value;
            let text = shape.data.text;
            let shape_width = 0, shape_height = 0, text_X = 0, text_Y = 0;

            context.beginPath();
            switch (shape.type) {
                case 'arc':
                    shape_width =  Math.round((2*shape.data.radius)/Math.sqrt(2))*0.85;
                    shape_height =  shape_width/0.85;
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
                $.each(text, function(key, value) {
                    context.fillText(value, text_X, text_Y + lineHeight*(key+1));
                });
            }

            context.closePath();
        });
    },

    fitTextToShape(context, text, shape_width) {
        let text_width = context.measureText(text).width;
        if (text_width > shape_width) {
            text = text.split(' ');
            let line = "";
            let word = " ";
            let new_text = [];
            do{
                word = text.shift();
                if (context.measureText(word).width >= shape_width) {
                    return [''];
                }
                line = line + word + " ";
                if (context.measureText(line).width > shape_width) {
                    text.unshift(word);
                    line = line.substring(0, line.lastIndexOf(word));
                    new_text.push(line.trim());
                    line = "";
                }
            } while (text.length > 0)
            new_text.push(line.trim());
            return new_text;
        } else {
            return [text];
        }
    },

    mapImage() {
        let $img = this.$('.cw-image-from-canvas');
        let image = $img[0];
        // generate map name
        let map_name = "cw-image-map-"+Math.round(Math.random()*100);
        $img.attr('usemap', "#"+map_name);
        //append map
        $img.after('<map name="'+map_name+'"></map>');
        let $map = this.$('map[name="'+map_name+'"]');
        // insert areas
        $.each(this.shapes, function(key, value) {
            let shape = value;

            switch (shape.type) {
                case 'arc':
                    $map.append('<area id="shape-'+key+'" shape="circle" coords="'+shape.data.centerX+', '+shape.data.centerY+', '+shape.data.radius+'">');
                    break;
                case 'ellipse':
                    let coords = '';
                    let x = 0, y = 0;
                    for (let theta=0; theta < 2*Math.PI; theta+=2*Math.PI/20) {
                        x = shape.data.X + Math.round(shape.data.radiusX * Math.cos(theta));
                        y = shape.data.Y + Math.round(shape.data.radiusY * Math.sin(theta));
                        coords = coords + x + ',' + y + ',';
                    }
                    $map.append('<area id="shape-'+key+'" shape="poly" coords="'+coords+'">');
                    break;
                case 'rect':
                case 'text':
                    let x2 = shape.data.X+shape.data.width;
                    let y2 = shape.data.Y+shape.data.height;
                    $map.append('<area id="shape-'+key+'" shape="rect" coords="'+shape.data.X+', '+shape.data.Y+', '+x2+', '+y2+'">');
                    break;
            }

            let area = $map.find('#shape-'+key);
            shape.title ? area.attr('title',shape.title) : area.attr('title', '');
            shape.target ? area.attr('href', shape.target) : area.attr('href', '#');
            shape.link_type == 'external' ? area.attr('target', '_blank') : area.attr('target', '_self');
        });
    }
});
