define(['assets/js/student_view', 'assets/js/url', 'assets/js/vendor/jspdf/jspdf.min'], function (StudentView, helper, jsPDF) {
    
    'use strict';
    
    return StudentView.extend({
        events: {
            "click button[name=download]":                  "generateDocument",
            "click .cw-selfevaluation-button-left":   "switchLeft",
            "click .cw-selfevaluation-button-right":  "switchRight"
        },
        
        initialize: function(options) {
        },

        render: function() {
            return this; 
        },
        
        postRender: function() {
            var $view =  this;
            this.buildElements();
            return;
        },
        
        switchLeft: function(event){
            var $siblings = $(event.target).siblings("input");
            $.each($siblings , function($index, $item){
                if (($item.checked ==  true) && ($index != 0)){
                    $siblings[$index-1].checked = true;
                    return false;
                }
            })
        },
        
        switchRight: function(event){
            var $siblings = $(event.target).siblings("input");
            $.each($siblings , function($index, $item){
                if (($item.checked ==  true) && ($index != 3)){
                    $siblings[$index+1].checked = true;
                    return false;
                }
            })
        },
        
        buildElements: function() {
            if (this.$(".cw-selfevaluation-content-stored").val() != "") {
                if (this.$(".cw-selfevaluation-value-stored").val() != "") {
                    var $values = $.parseJSON(this.$(".cw-selfevaluation-value-stored").val())[0];
                } else {
                    var $values = $.parseJSON('[{"good":"", "bad":""}]')[0];
                }
                var $container = this.$(".cw-selfevaluation-container");
                var $contents = $.parseJSON(this.$(".cw-selfevaluation-content-stored").val());
                var $html = "";

                $.each($contents, function($index){
                    var $element = ($(this)[0]).element;
                    $html += 
                    '<div class="cw-selfevaluation-item">'+
                        '<p class="cw-selfevaluation-item-element">'+$element+'</p>'+
                        '<span class="cw-selfevaluation-item-value-good">'+$values.good+'</span>'+
                        '<button class="cw-selfevaluation-button cw-selfevaluation-button-left"></button> '+
                        '<input class="cw-selfevaluation-radio" type="radio" name="selected-'+$element+'" value="++">'+
                        '<input class="cw-selfevaluation-radio" type="radio" name="selected-'+$element+'" value="+">'+
                        '<input class="cw-selfevaluation-radio" type="radio" name="selected-'+$element+'" value="-">'+
                        '<input class="cw-selfevaluation-radio" type="radio" name="selected-'+$element+'" value="--">'+
                        '<button  class="cw-selfevaluation-button cw-selfevaluation-button-right"></button> '+
                        '<span class="cw-selfevaluation-item-value-bad">'+$values.bad+'</span>'+
                    '</div>';
                    if ($contents.length > $index+1) {
                        $html += "<hr>";
                    } else {
                      $html += "<br>";  
                    }
                });
                
                $container.html($html);
            }
        },

        generateDocument: function() {
            var $view = this;
            var $title = $view.$(".cw-selfevaluation-title").html();
            var $description = $view.$(".cw-selfevaluation-description").html();
            var $contents = $.parseJSON(this.$(".cw-selfevaluation-content-stored").val());
            var $selection = new Array();
            $.each($contents, function(){
                var $element = ($(this)[0]).element;
                var $value = $view.$("input[name=selected-"+$element+"]:checked").val();
                if (typeof $value !== "undefined") {
                    $selection.push({"element":$element, "value": $value});
                }
            });
            
            //dfb-Logo header
            var logoData = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMAAAABCCAYAAAABpNnUAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAGHVJREFUeNrsXQtYVVW+/yMqD3kJgRgqB7V83fRIijXpeDB73BlLrJm600OONTb3TtMN5za97ldi831TM9MXdLtWU2NC2UxNL6ycrpUJmaWGevCRNqgcRBNQ8ADKS/Dc/VustVlns/d5AcI37d/37Q847MfaZ/9/a/3+j7V2iNvtpgsMq7LF6fxeLO1TTCZMXACE9DMBLMqWxQ0d2wzxj2PHjvXYOTExkcLCwsSflcrm4FsR/2nCxKAngDB6Owz+5MmTdKyqimpra+lE9Qmqrq72eYK4uDhKTk6mUaOSaezYsTRmzBhBCIwM+SYZTAxGAtiULUfZFqN3379vH1U4K8jlcvX6xOHh4WSxWOiyy6bThAkT8FEZJ0KB+QhNDDQBYPi5yjZ///79tGXL531i9N7IMGfOFZSeng65VMmvbRLBxAUnQBzvhbN9GT4kTZoljZKSkihR2WJiYtimRVtbG0EyNTQ0sJ9OZQQxkkwaIpTw0ceURiYuCAHQ6xcpUif2008/0TVSGP2cjDk0YeJEXWP3F42NjVSl+BD79u5lkkqPCNdccy1NmzYNf67iI4IJE/1GAPT69xcXF9O2bV/1+Cd6+qvmzhWOqy46OjpYL69FZGQkRUREeCXDV19+Sbsdu3Wvm7VkiRgN4IS7zMdroi8JAMlToBjh4nfffadHr+/N8GtqaujUqVNUW3+KzroafV4oImoEXRSfQAkJCSwaNHToUL+IgNHgJz/5KdoAJ9luSiITfUUAGH+xostnvP76OmptbTWSICrq6uqootJJNSdOkPu8mzroPJ3qbKZvm2uosr2JStt6EiFlaDhdEZFAqeEjKWloNEWGDGOfJ45KopSLU3qQC9EmPQl2ww03oj0NXKqZJDDRKwIYGr8kOzwMf/+Bb6jxtIsZ/bettfRh41H6wnUo4MbFRyTSffGTaEpkMo0IGU5hCtmmTJnSgwh6cswkgYm+IICh8V9xxZVks9nUv1taWmjP3j10sqaWzrrb6Z36b+gtGH1nO/v/kxmL6elDpVRXfzyohs6Nm0jZCVMpOTSKIqOiaMb06UwiCSAS9cknH3u00SSBid4SoEAx/myt8XPD8pAiZWUOOne+k7Y2VdDTJ7b3ONH2Wx6jjLFT6W97N9Otxa8StbcGTYT7EmcyeZQ2frxHOxA6NWhrGSeB6Rib8JsAOW1tbXkvvPC8V+Mv3bWTqo9/R9WdZ2h5VbFi2PpOriAA0NTWTKt3vE+P7FgfXKtDh9PK5AyaNWIcRUVH01zF+RaOsh4J7rjjTsimEk4CEyZUDDH4HIVreUXvvWdo/Ahlbv1yKzP+0rNHafnh9w2NX4vosEh6eN6/0fHl/0PjR18SeKsVWbXq+Bf0ap2DXE0N9Nnmz1hkiDnMiYl0++13eOz+9ttvIck2n7qSZSZM+CRA0VdffeWReJppnelh/CVbPqfTdfXMCGGMweDimEQ6fNsTbHSgEfEBH/9W3Td0d9Umam5toa1bt3qQAGQVAIlBZpCak9uECUMC5CqGlFpSUqx+gFj8dddfr/69U5E9LWfOMuN/q+lIUMYrA9LI/e+r6U8/vJ1oeHhAx9a3nGQkaOtop23btzFyAiArHHUBkBmOMnUl8kyY0CWABTLho7//Xf0Acf6bbrpZ/buMR3qY8Ss98PiEsUEbrxb3zF5EjfespuzJVwVFAowEX3zRPRpdeeWVjLwCiBJxKWQ3H70JYKi290d9jyx9UHAmanmQ0a1yVtL+1mpm/Frj/dn0BfTEljfp6bKPfV74qS1v0ALLdNUxlv2DXyrnKTy4NWAS/KVuDy1VFA56eowAyE8sXHgNrVv3miqFdu3aBWLkkp8VpCEhIfKsNcDpdrudOvvFeZFX7BhpH4fyt0tzPDofi/J5sY9zAS5lP4d0rI07+GhXsV77pHvBfi7tfj7axtoirinaatA2dryXe3AatW+gCYAbyt4q9aAoaEMvKnT/zp076fT5FnrYQPPDeP+4cBmtyFhE8z58jo6cKDe88MtV37AoEHr75xbexY7VhZBXZ+v98gmmhiv7H+mSbcgTIGkG/0WUTWxXZFJ6enqqQg67nySAZJqvMQiUYecoD7JIEzjYbHAOUaQn9smkntM+0Z6VOL2PcwEsosUNEedJ1bQP4TW7MGRujEU691Go7GPXtF+vbUI22jRt1YM43vAetO0bLBIoB/F8ufefN++H6u/I7p7v7KQcGD9PbvlybjfeuMKnf4CePuale9mIoIfxMQnUuOyPLJHmj8RaVb2Dmt3nqGzPnm4p9IMfeDjEGAUCjAiVKA8rhD/cJbwHfU95kFnaHbGfzpYbyEPBKCAfL25N+swmGSaMO5PvN5KTLU5jXAXc+Fdo9svWu4cA2ql3r1ryZEr3kcmvu3iwRORkAthLS7/26P1F1AdZXiF9IDX8xbWXZDD/AMYbY9TDA+2tbDQIefFe+rh8h+7IwsKm2X/w7R8o5Hzu5G5qPnNGnXcMCYdRQPVjythIPiPQiBA3zCLeE1YOAoc6jkuOYt4+SKNciSBCvizmBMqX90NwTzOK9Sv494frltEgyckIAmQpzmHswYMHDXt/1PU8HGS4E8Y7Ocnie0dF5lz3fh7N2bDacGQp+PGvWNg0IT7F8DSoO0Ji7sCBA7qjACbuHD58mIJ1hnnvigeZKvTxAAHtsHIfwAhZGhkj34djANs9qKJAWYcOeRarTZw4UdX+SHahqM2X9AEO1jpZuUOv4EPvw3E+texpevPaewxlUaHiD7QpcgeOuxgF5IjQofJy2TiCgVPqhWV9Wyxt/U0OIa02K9dyKJud630ZIEdZAHob58iVNyOHV75XPwMKccqWw+VY8aAiQHn5P9QPIRdEhSdmY7FevLrUrxM2tjXTrR+/RBP+8jjtqPrGcL8XZi/yK39wpK6KXvr6Q93/3XJZJgubMv9AZxRAUZ6zsjvgMGO6uioLHTjIRodUCj4xNuDFdbwHtwjdr2xrQUwdXR9IjysiRfIW14tmgpxubMrvp6krGflsoH5RfxIAN+whf8aMHdttgIpTjMiPv2UO6nEnymnO335L9g3/S981ntT1D/xybhX/4Befv878Az1CeSurONBczXIWIjk2gY9qwhnmPkKwWtRqYJQ2aXNcABII3W/hTqZw0C2S8VsCOGWO5h5sRmTX7GOEFbxdy/jfMP5BU5ICAti0i1TJ8gcZ3y2NwYdtEeVJKXyQSaNeObeKLAKhMLLonUuvrOK5+m/ZTzFhRiuD+OgWLAFsGinklxOoOVYGjLSht06mdG4xChRzX8UyQDbm4M4vIlElXGLFDSYCWE/W1npEf4T8weQW4J2mY727itKLQxp5C5v649yKkWXKa4+wkaVJ55xyWUV9ZxNz3sV9AKOTR6u/19RUUzASiGt79GLrg0jqVGoJwA3CFqguhtTR8TPiNLKnyMgJVo7Nv8DGCNkTS4OoKBGJMEtDY7e8kXtIFJedc3cGFPr0F5BFMHw95xY5AV+l0oVVB+i2o/uYlNLDoklzaHz5Djp2zkXR9d0EwNIscjSINEkkA1i4Myh66mweyrPrGJWetnXyHlAYYh53HIVR5vB2BBqVKuDXLOCGbuFG1iAMn2eg4SOslK4Zx685g5MuqFCowb0W6+QC1BFKOYYtYQPyDYZEGAhgrZHm1GI5QoFj1d9RHfR/PwCZ4nmxSbpZYJRIkBEBFH/hSet1dG/GjbrZY+1cg8rR8WQ50+1sJ0oEkOYS++p9YZwruWE5uNNp9AD1MqQlwlgRi+e9LgzwPWlUWGJkOD78EBjh/XwT18qR2wYfQbmmS3NNEDgziGv6ulfy8V2ivZs52fMHAwFi5Q/kNXw6znVQ3bkz/XZx+AeFR3Yyg4Yv4AvwE34379YeI4eA3myzbS11ND96AvNnMGkm0DWKfDh4Wv0d4ue+uRTA+kVSNlj7uZMbkt2Pc+R7Mzhv7dd+B/603+h8gXxPF4oAbNFagdjYbj60nW2mU+fO9m8LeBb4kf1baPuP7+1RHAcguvNXhSB6/wMQHfrRp6/ozjc+3tFFBqxDhNogLQEwgwzzB0x8PzFUhAQHHDzKA2N/8rLMrs9GxNPGq7MNdT78iDs/eYU+cwYfbcRyjAGGCU38sxFgMAFRnlvrqlh+wJssgvGnvPyffXVZkwDfUwwZbA2Czkd215dPAD8Aya8Flj6pNjCXTPk+jwCI/ffnkub+ANJnw8K7DIvm9MKm+HvTzQ8xH4AV0PkxZ0ALrv/N5VK+zwQYGTdSJQCSYmLltfCYEZR6bmT/tiAAnY/e/rVr7tLNHyD5hZqhX2x7xyMKtDCya1+xiJY26y2vaqcHnRlhHhENnmGN0yt74FWaTs3MK/G6KBf/n95xYjaVlY9OHrO0DGZbubyVXsgzzrxcz2EUm+ftdgWa+DOoVNWbdWYjHzPlfLXVj5lrPb4jSKBK+YPWLqeQITI8gmJDI/rF7i1hI5jOh+HqGT/i+UiIoYxCOLn4ib+NJs/ozSmODR1OQ4aG6u6P+c4c3h4qQoebDTbATsbhRRHvFg8IDxFT01C0hnj8bl7FaZX2sfP24Ng8/tPJqygFrDptwbmcXipQkezabDABRpzP6uN7sAfxqPW+t9P8PrX76V3fTp4zy0RbcwzamK9zrPwdueQEHgjgHJfanQw9WtnNh8T4i2jkkP4hAKSLkc5HPD9m7W+6klnaFeSkyTNGxXEoqzhw55OsrGJS5Ci2eJaAqG4FpLIIX70aSxrpbIH0hHgY6qwsHttfxkcXl+Yhoj1pfJ+ZfBTQG4VWSG1ZwvfJNej9RSlsFl14FGq+NyTr1vayPmllAOXm8ndUzI/NFxLIERMTo84VlXMC8fFdGdS0qDFUceZYv39LMOifKb27t7nEAgsSx9GYWOP4/Z6aCqpra2FriY6Wstu8/odhVFfZR5kfTXP1MmOqRprErCz+ewH1nJeMREy+kBp8yLYZOe9yu/gIE2fQizbwa9kHgABOTTtzeY9soQCKCTUQM/JsvnbUPLsi5foYDe8HCTACOMZK5c/ICSA5JOvmpbG9jBIqOt+bsULuoLgNeQBfxo9eHUVzGEH0MsKoFEXFKOYkzB0W70Fk9iScTq0DfKEiQGKCuq9CMBhqTqC9o6SNXQYEKOYEiO3NPOA+glX+ToIESDTfj+9TD+KYLIwAxciOypEgvNZUZEfjky6iKTXtwTWR1+14C2mywjfHRt+L5SrnetO2lE2CMSLRfZ++4rGcyqKYcTQkNFQlMogtJ/048f3p2eViOLmwLRCI3jePPzT0XkU6TmUO9xEqsGoDjvMy+uTzGh8xwvSQQFwmQONizoCDr2iRRUEWwBnIK3lU0SuGs0vOcByXY4W9mS+BZ8BlJeqcCgIprOMFguho4obwIagSa/2rgndPtyoYlzKWrc0PGRQI4Iiizt/I+DH5HTpeV+drAGcZzq2R8YNEWFnCYy0hxfmdHJ5EKSnd5dV8ZTgSoV9eFuEPAUS5so2CnEHGJ65Yue53cQe3QltRycmVxnVzFndcjaZXOnj7nfyceqXGdskJFj+z+7AMWv5ubKSfVHTydhbzdrIl6/tgjoJd6lwChUMNg+JLmXjJJfeLtXNQJYlSaBgIQqJYReHRUem03A8/AFJHXgnaCP+BaY4+4vZGYU+ZRNdtKtQ9zwNJMylU8fHT0rqJffBg9yT5yZOnCP3vjwZ1+CiKswTSc+GBcYPO5w6Zx6giitykqtGV/CFrSVCg0dY5fITJl3rXLO50YvK87PBnUR+8XtaHjyKPCrmaUamYj1Z2DZkowJ48l9+zLcCms3nJIhNcgBdQS2FBsXYOw6WXTmLOpD+jAIzVl/H7gi+dj9wAdD5WkNAlkdL7XxWdRiMT4tXiN6wCISf7+JIvBX3UC6YaaHKvhsNJ1WAUmZGWL3lWiuL4klmqIUnyZ74UCswbwGiQTBqHTsdhNRhhGrycK593ZH4/S4ksziHScFBpldbOcSijAS8UY71oyJAQNgoEAySo9KYx6ur8a+9hk2L0SASd/5tP17IaIG/OMnr/oUrvP3nSZPWz0q+71zzCpB/u4/SFDnZKYU4ZWbL0gDEaOJ8u2Rk0WNnBX9h0/IkGnfAt5NXiAZ6aaNGJ6uhFqLL8kKl2iej+BAvy+fdSJBfD5aanp68V79qS1tFkdfRTp04j9759tFwxrpdrd/sd1hQlCmyurg+dbzTJRZBIm+XVJ1EMq//Hi/Xk7K+84t3s2RkiNu3sg96sgA/D+dJShWKiSpkkRXK50RVKxBMzwQr4w8niDrBYjsTJjdrOZQz5cC5FuFOWP0Vap5Q7ztk6MsiuIyVkeWbTmwXmxwoP2uNs/L5zNFGdtTxEWaAx7BxfI4qY9WZg9LnSd5QlviuMsDIBihS5kD/TOjNWXkcTUgEyAqOA89hR+lf3pfSy67DXVSIgUXytDSoDRW1GOj/QOp9Xx13NIj+Xp1+ufoY3ScrObx/LH2Fo+fwBiIewXtOj2fmDzOHGJ3q9JdLKbohRLxHGIB1baGAE2ZrwKc6TwxentXGnuMjAYCp1CJCtc40SaZ/5Br2sLwJojyvh912k6Ugs/D4XS9/PMj9Xr/OWqRbPpEz4HiL6pn1FEt4NsPL551dLzuJkyspawn7HEombPttEpzuaaWnFBrZQForYsBqDkChGrz7SOsbQ8IIg7v/6qy6JAq31f2rcApoWnkyzMzJo1KhR7LPdyii28eON6j78LTfm65JMMGjLoXOV3r5y/nybFDk5KJYRZG9xnz1rNiuPeCplrseBrHzhpXuDf++XpPNZDZCi8wMx/p8mTGXGjxfnCeNHJKvk827lgFAv7/3NVyWZ0CUAMw7FF2BSQeCDD95XXz8E47LOnMmMDT3ukcY6umjtAyzzGuybH3tLIhj/0gQrJadc7PECP7zRXk58Lbj6auIRFbP+34QhAYrCwsLWL1p0g/rBlK6YuQrkBpixgQSK4dU19G7ZFOh8Ub4QKInglMP4UfBmlZY+RARr0qTJamgXo1piYmIlBTAZ3cQ/P4xek4ru36lIn1iEC41WUkB0xbF7N1s6cenRTV4dY28+QFAIHc5kGEgIMs6SnF4ZIAIW/uUjQyYNkkVZTQzeEQBAmCwLyTFh/GVlZbRgQSY9sWqVx0gAh3Pk0Eh6z/Ij1hv7q/MbvKwU5wtIyL0x/kZm/JMUJ93D+E99QO7d8ewngAkv3PhXmcZvwt8RQABhpbX5+Xm0YsWv1Q/vvusuyn/2WYqKilKjQ9tKd9BZVyNbl/93Nbt6lE+LEcBb+YJPDI+hp5JnMcMfFjacZl0+S431Mxx9ktwnH+2+uaibiSa9jV8LyXwxnokgCAAUnDlzJtuenU3vvPuu+uHNN91Eq59/Xo24CEmEuiH3eTcjAtboxzLlABJdeC9YMLInPiKRHky8jBk+gF4feQnxdnhSrkVHf0vu+j9IY9tICpm0nSjyEvE2EnPer4mgCGBIAosllda9to6umtsdEsUKbBUVFVR++BCdP9fB1ujHMuWvNjgDmlQDo785egzNi7Gos9KY4z1lKgvHqmguJ3fF3UStW0zjN9FvBFBJsPLxx+mZvDyPf/x6xQp68KGHPEYDMSIcPV5F9bWn1M/gMDd0tlBl6+keF7ho2AhKGBbFCu8EIhWZlWaxsNp9tccXvX7Na+Q+8UvPk4TPo5C0Nabxm+hzAqg+wSuvrKG77/65xz8wGjz22GN0yy23qr6BDCxRXl9fT/Wn66mp5Sy1N7fS+Y5OT9uNGUHDQ4exKYxwvqHvPYxednS/e4Do3D88byZ2ueIhP0MUGmVqfhP+AQQIcMMbQVwOh8OdmWkDezw2hQjuvLxn3NXV1e4+RUeT2129zn2+7FL3+VLy3HaNZP/jyAninszte7oFeyDWwSlqampyr1nz5x4kwAZyMLttrHO3fvt1UDZ/7rtD7HiGIw/2NHxlc5cvd7vbTrh5fb3VfKjmFsgWqATSglVB1tTUpP7pxRdpZW6u+o//++gjuu7666lh/YtUedujFJoUQRFz0ihy+r9QaEwsRUxLpyHRnotund22qStP8MVWaq+opvZ99ZTwq2sp5fdvELVXk3vvaM8Q5+j/JoqZiSpIVALmmuO5if72AYwA48tRiBC7YcOH9OXWL+nPa9awf5Rfb6WWLUeDPjGIM3nPXgqNjmdxfupUfNpRP4eTK+L7Oaaja2KgCSA7yeLVO9TZVE/lCzNYT94bAkzcuJ7CLp0lPkI9TwHfnOYjNDGYCCBg5WSARErtOHGYWg+WUsv+XdS8b6/SiXfVDLVsr6DO2q5XMEXd0F0nFD33Kho+No3Cp8wShi/eeSU2EyYGNQE8IqTUvVyG+OntxXRiSp+DupfTMMuXTfQL/l+AAQA1MG0Jx3T6hQAAAABJRU5ErkJggg==';
            var doc = new jsPDF("p", "mm",[297, 210]);
            doc.addImage(logoData, 'png', 10, 10, 40, 15);
            var $head = 35;
            var $top = $head;
            var $lastpoint = {"x": 0, "y":0};
            //for each self evl 
            $.each($selection, function() {
                var $element = ($(this)[0]).element;
                var $value = ($(this)[0]).value;
                
                if ($top > 297-$head) {
                    doc.addPage();
                    $top = $head;
                }
                if ($top == $head) {
                    doc.setFontSize(10); doc.setFontType("bold"); doc.setTextColor(0,0,0);
                    var $height = 8; 
                    doc.text(15, $top , $title);
                    $top += $height;

                    doc.setFontType("normal");
                    var $dim = doc.getTextDimensions($description);
                    $height = Math.round($dim.h)+10;
                    doc.text(15, $top, doc.splitTextToSize($description, 160));
                    $top += $height;
                    
                    $height = 10;
                    var $width = 30;
                    doc.setFontType("bold");
                    doc.setFillColor(32,174,128); doc.rect(15, $top, 180, $height, 'F');
                    doc.setFillColor(255,255,255); doc.rect(166, $top+1, $width-2, $height-2, 'F');
                    doc.text(179,$top+6,"--"); 
                    doc.setFillColor(255,255,255); doc.rect(137, $top+1, $width-2, $height-2, 'F');
                    doc.text(151,$top+6,"-");
                    doc.setFillColor(255,255,255); doc.rect(108, $top+1, $width-2, $height-2, 'F');
                    doc.text(122,$top+6,"+");
                    doc.setFillColor(255,255,255); doc.rect(79, $top+1, $width-2, $height-2, 'F');
                    doc.text(91,$top+6,"++");
                    $top += $height-1;
                    
                }
                //boxes
                doc.setFontSize(10);doc.setFontType("normal");doc.setTextColor(255,255,255);
                $height = 30; $width = 30;
                doc.setFillColor(32,174,128); doc.rect(15, $top, 180, $height, 'F');
                doc.setFillColor(255,255,255); doc.rect(16, $top, 62, 1, 'F');
                doc.text(17,$top+5,$element);
                doc.setFillColor(255,255,255); doc.rect(166, $top+1, $width-2, $height-2, 'F');
                doc.setFillColor(255,255,255); doc.rect(137, $top+1, $width-2, $height-2, 'F');
                doc.setFillColor(255,255,255); doc.rect(108, $top+1, $width-2, $height-2, 'F');
                doc.setFillColor(255,255,255); doc.rect(79, $top+1, $width-2, $height-2, 'F');
                var $pos = -10;
                switch($value){
                    case "++":
                        $pos = 93;
                        break;
                    case "+":
                        $pos = 122;
                        break;
                    case "-":
                        $pos = 151;
                        break;
                    case "--":
                        $pos = 180;
                        break;
                    
                    }
                doc.setLineWidth(1.5);
                doc.setDrawColor(204,204,204);
                doc.setFillColor(204,204,204);
                doc.circle($pos, $top+15, 3, 'FD');
                var $thispoint = {"x": $pos, "y": $top+15};
                if (($lastpoint.x != 0 ) && ($lastpoint.y != 0)) {
                    doc.setDrawColor(204,204,204);
                    doc.setLineWidth(1);
                    doc.line($lastpoint.x, $lastpoint.y, $thispoint.x, $thispoint.y);
                    
                }
                $lastpoint = $thispoint;
                $top += $height-1;
            });
            //end for each
            
            doc.save($title+'.pdf');
        }
    });
});


