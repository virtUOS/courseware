(function($) { 
    'use strict';

    STUDIP.MOOC = STUDIP.MOOC || {};

    STUDIP.MOOC.Registrations = {
        getTemplate: _.memoize(function(name) {
            return _.template(jQuery("script." + name).html());
        }),
    
        init: function() {
            $('.button[name=resend_mail]').click(function() {
                if (!$(this).attr('disabled')) {
                    $(this).attr('disabled', true);
                    STUDIP.MOOC.Registrations.resendRegistrationMail($(this).attr('data-user-id'), $(this).attr('data-mooc-id'), this);
                }
            })
        },
        
        resendRegistrationMail : function(user_id, mooc_id, button) {
            $.ajax(STUDIP.URLHelper.getURL('plugins.php/mooc/registrations/resend_mail/' + user_id + '?mooc_id=' + mooc_id), {
                success: function(response) {
                    var template = STUDIP.MOOC.Registrations.getTemplate('success_message');
                    $('#messages').append(template({
                        message: response.message
                    }));
                    $(button).attr('disabled', null);
                },
                
                error: function(xhr, type, response) {
                    var template = STUDIP.MOOC.Registrations.getTemplate('error_message');
                    $('#messages').append(template({
                        message: 'Konnte Mail nicht erneut versenden: '.toLocaleString() + response
                    }));
                    $(button).attr('disabled', null);
                }
            })
        }
    }
    
    $(document).ready(function() {
        STUDIP.MOOC.Registrations.init();
    })
})(jQuery);