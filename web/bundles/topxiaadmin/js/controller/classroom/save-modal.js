define(function(require, exports, module) {

	var Validator = require('bootstrap.validator');
    var Notify = require('common/bootstrap-notify');
	require('common/validator-rules').inject(Validator);
	exports.run = function() {
		var $form = $('#classroom-form');
		var $modal = $form.parents('.modal');
        var $table = $('#classroom-table');

		var validator = new Validator({
            element: $form,
            autoSubmit: false,
            onFormValidated: function(error, results, $form) {
                if (error) {
                    return ;
                }

                $.post($form.attr('action'), $form.serialize(), function(html){
                    var $html = $(html);

                    if ($table.find( '#' +  $html.attr('id')).length > 0) {
                        $('#' + $html.attr('id')).replaceWith($html);
                        Notify.success('教室更新成功！');
                    } else {
                        $table.find('tbody').prepend(html);
                        Notify.success('教室添加成功!');
                    }
                    $modal.modal('hide');
				});

            }
        });

        validator.addItem({
            element: '#classroom-name-field',
            required: true,
            rule: 'remote'
        });

        $modal.find('.delete-classroom').on('click', function() {
            if (!confirm('真的要删除该教室吗？')) {
                return ;
            }

            var trId = '#classroom-tr-' + $(this).data('classroomId');
            $.post($(this).data('url'), function(html) {
                $modal.modal('hide');
                $table.find(trId).remove();
            });

        });

	};




});