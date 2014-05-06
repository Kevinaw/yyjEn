define(function(require, exports, module) {

    exports.run = function() {

        $('.course-publish-btn').click(function() {
            if (!confirm('您真的要提交新课程申请吗？')) {
                return ;
            }

            $.post($(this).data('url'), function() {
                window.location.reload();
            });

        });

    };

});