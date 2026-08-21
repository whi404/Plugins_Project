document.addEventListener('DOMContentLoaded', function () {
    var deleteLinks = document.querySelectorAll('.kmm-delete-link');
    deleteLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            if (!confirm('Bạn có chắc muốn xóa chương trình khuyến mãi này không?')) {
                e.preventDefault();
            }
        });
    });
});
