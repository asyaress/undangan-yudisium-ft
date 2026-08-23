<script>
    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-copy');
            var input = document.getElementById(targetId);
            if (!input) return;

            var value = typeof input.value === 'string' ? input.value : input.textContent;

            navigator.clipboard.writeText((value || '').trim()).then(function () {
                var original = button.innerHTML;
                button.textContent = 'Tersalin';
                setTimeout(function () {
                    button.innerHTML = original;
                }, 1400);
            });
        });
    });
</script>
