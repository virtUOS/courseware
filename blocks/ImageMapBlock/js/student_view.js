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
        var view = this;

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
        canvas.width = 868;
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
        $(outlineImage).on('load', function(){// chrome needs this!
            context.clearRect(0, 0, context.canvas.width, context.canvas.height); // Clears the canvas
            context.fillStyle = "#ffffff";
            context.fillRect(0, 0, context.canvas.width, context.canvas.height); // set background
            if (outlineImage.src != '') { 
                context.drawImage(outlineImage, 0, 0, context.canvas.width, context.canvas.height);
            }
            view.drawShapes();

            if(!(view.$('.cw-image-from-canvas').length > 0)) {
                view.$('.cw-image-map-canvas').hide().after('<img class="cw-image-from-canvas">');
                let img = view.$('.cw-image-from-canvas')[0];
                img.src = view.context.canvas.toDataURL("image/jpeg", 1.0);
                view.mapImage();
            }
        });

        if (outlineImage.src == '') {
            $(outlineImage).trigger('load');
        }
    },

    drawShapes(){
        let context = this.context;
        let view = this;
        $.each(this.shapes, function(key, value){
            let shape = value;
            context.beginPath();
            switch (shape.type) {
                case 'arc':
                    context.arc(shape.data.centerX, shape.data.centerY, shape.data.radius, 0, 2 * Math.PI); // x, y, r, startAngle, endAngle ... Angle in radians!
                    context.fillStyle = shape.data.fillStyle;
                    context.fill();
                    break;
                case 'ellipse':
                    context.ellipse(shape.data.X, shape.data.Y, shape.data.radiusX, shape.data.radiusY, 0, 0, 2 * Math.PI);
                    context.fillStyle = shape.data.fillStyle;
                    context.fill();
                    break;
                case 'rect':
                    context.rect(shape.data.X, shape.data.Y, shape.data.width, shape.data.height);
                    context.fillStyle = shape.data.fillStyle;
                    context.fill();
                    break;
                case 'text':
                    let text = shape.data.text;
                    let text_width = context.measureText(text).width;
                    if (text_width > shape.data.width) {
                        text = text.split(' ');
                        let line = "";
                        let word = " ";
                        let new_text = [];
                        do{
                            word = text.shift();
                            line = line + word + " ";
                            if (context.measureText(line).width > shape.data.width) {
                                text.unshift(word);
                                line = line.substring(0, line.lastIndexOf(word));
                                new_text.push(line.trim());
                                line = "";
                            }
                        } while (text.length > 0)
                        new_text.push(line.trim());
                        text = new_text;
                    } else {
                        text = [text];
                    }
                    context.rect(shape.data.X, shape.data.Y, shape.data.width, shape.data.height);
                    context.fillStyle = shape.data.fillStyle;
                    context.fill();
                    context.textAlign = "center"; 
                    context.font = "14px Arial"
                    if (view.darkColors.indexOf(shape.data.colorName) > -1) {
                        context.fillStyle = '#ffffff';
                    } else { 
                        context.fillStyle = '#000000';
                    }
                    let lineHeight = shape.data.height/(text.length+1);
                    $.each(text, function(key, value){
                        context.fillText(value, shape.data.X + shape.data.width/2,  shape.data.Y + lineHeight*(key+1));
                    });            
                    break;
                default:
                    return;
            }
        });
    },

    mapImage(){
        let $img = this.$('.cw-image-from-canvas');
        let image = $img[0];
        // generate map name
        let map_name = "cw-image-map-"+Math.round(Math.random()*100);
        $img.attr('usemap', "#"+map_name);
        //append map
        $img.after('<map name="'+map_name+'"></map>');
        let $map = this.$('map[name="'+map_name+'"]');
        // insert areas
        $.each(this.shapes, function(key, value){
            let shape = value;
            let target = shape.target;
            if(target) {
                switch (shape.type) {
                    case 'arc':
                        $map.append('<area id="shape-'+key+'" shape="circle" coords="'+shape.data.centerX+', '+shape.data.centerY+', '+shape.data.radius+'" href="'+target+'" target="_blank">');
                        break;
                    case 'ellipse':
                        let coords = '';
                        let x = 0, y = 0;

                        for (let theta=0; theta < 2*Math.PI; theta+=2*Math.PI/20) {
                            x = shape.data.X + Math.round(shape.data.radiusX * Math.cos(theta));
                            y = shape.data.Y + Math.round(shape.data.radiusY * Math.sin(theta));
                            coords = coords + x + ',' + y + ',';
                        }

                        $map.append('<area id="shape-'+key+'" shape="poly" coords="'+coords+'" href="'+target+'" target="_blank">');
                        break;
                    case 'rect':
                    case 'text':
                        let x2 = shape.data.X+shape.data.width;
                        let y2 = shape.data.Y+shape.data.height;
                        $map.append('<area id="shape-'+key+'" shape="rect" coords="'+shape.data.X+', '+shape.data.Y+', '+x2+', '+y2+'" href="'+target+'" target="_blank">');
                        break;
                }
            }
        });
    }
});
