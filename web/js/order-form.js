(function () {
    const addButton = document.getElementById('add-order-line');
    const body = document.getElementById('order-lines');
    const template = document.getElementById('order-line-template');
    if (!addButton || !body || !template) {
        return;
    }

    addButton.addEventListener('click', function () {
        const index = body.querySelectorAll('tr').length;
        const html = template.innerHTML.replaceAll('__INDEX__', String(index));
        body.insertAdjacentHTML('beforeend', html);
    });

    body.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-line]');
        if (!button) {
            return;
        }
        const rows = body.querySelectorAll('tr');
        if (rows.length === 1) {
            return;
        }
        button.closest('tr').remove();
    });
}());
