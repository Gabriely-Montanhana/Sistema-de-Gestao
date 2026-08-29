<footer class="public-footer py-4 mt-auto">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-2 small">
        <span>&copy; <?= date('Y') ?> Sistema de Gestão - Tornearia Sátelite</span>
        <a href="<?= htmlspecialchars(($adminPath ?? app_paths()['admin_url']) . 'login.php') ?>" class="text-decoration-none text-secondary">
            Acessar admin
        </a>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
