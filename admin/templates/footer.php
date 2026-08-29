            </main>

            <footer class="site-footer text-center py-3 bg-white border-top text-muted small">
                &copy; <?= date('Y') ?> Sistema de Gestão — Painel administrativo
            </footer>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js"></script>
<?php if (!empty($pageScript)):
    $paths = $paths ?? app_paths();
    $scriptDiskPath = $paths['disk'] . '/assets/js/' . $pageScript;
    $scriptVersion = is_file($scriptDiskPath) ? (string) filemtime($scriptDiskPath) : (string) time();
?>
<script<?= ($pageScriptModule ?? true) ? ' type="module"' : '' ?> src="<?= htmlspecialchars($paths['assets_url']) ?>js/<?= htmlspecialchars($pageScript) ?>?v=<?= $scriptVersion ?>"></script>
<?php endif; ?>
</body>
</html>
