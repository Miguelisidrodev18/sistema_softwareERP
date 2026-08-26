        </main>
        <footer class="footer">
            <p>&copy; <?= date('Y') ?> Consultorio Dental · Sistema de gestión de citas</p>
        </footer>
<?php if ($usuario): ?>
    </div>
</div>
<?php endif; ?>
<script>
(function () {
    var card = document.querySelector('.auth-box');
    if (card && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        var bounds;

        card.addEventListener('mouseenter', function () {
            bounds = card.getBoundingClientRect();
        });

        card.addEventListener('mousemove', function (e) {
            if (!bounds) bounds = card.getBoundingClientRect();
            var x = (e.clientX - bounds.left) / bounds.width - 0.5;
            var y = (e.clientY - bounds.top) / bounds.height - 0.5;
            var rotateY = x * 10;
            var rotateX = y * -10;
            card.style.transform = 'rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg) translateZ(0)';
        });

        card.addEventListener('mouseleave', function () {
            card.style.transform = 'rotateX(0deg) rotateY(0deg) translateZ(0)';
        });
    }

    var sidebar = document.getElementById('sidebar');
    var toggle = document.getElementById('sidebarToggle');
    var backdrop = document.getElementById('sidebarBackdrop');

    if (sidebar && toggle && backdrop) {
        var closeSidebar = function () {
            sidebar.classList.remove('is-open');
            backdrop.classList.remove('is-open');
        };

        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('is-open');
            backdrop.classList.toggle('is-open');
        });

        backdrop.addEventListener('click', closeSidebar);
    }
})();
</script>
</body>
</html>
