define(function(require, exports, module) {
    var Validator = require('bootstrap.validator');
    require('common/validator-rules').inject(Validator);
    var Notify = require('common/bootstrap-notify');

    exports.run = function() {

        var $modal = $('#student-remainingNum-form').parents('.modal');

        var validator = new Validator({
            element: '#student-remainingNum-form',
            autoSubmit: false,
            onFormValidated: function(error, results, $form) {
                if (error) {
                    return false;
                }
                
                $.post($form.attr('action'), $form.serialize(), function(html) {
                    var $html = $(html);
                    $('#'+$html.attr('id')).replaceWith($html);
                    $modal.modal('hide');
                    Notify.success('剩余课时数调整成功');
                }).error(function(){
                    Notify.danger('剩余课时数调整失败，请重试！');
                });
            }

        });

        validator.addItem({
            element: '#student-remainingNum',
            required: true,
            rule: 'integer',
            display: '剩余课时数'
        });

    };

});
