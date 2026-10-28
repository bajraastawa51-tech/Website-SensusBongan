<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}
?>
        </section>
        <footer class="app-footer"><?= h(__('app.footer')) ?></footer>
    </main>
</div>
<?= render_senior_assistant() ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="assets/js/app.js?v=<?= asset_version('assets/js/app.js') ?>"></script>
</body>
</html>
