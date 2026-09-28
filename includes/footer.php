<?php if (isLoggedIn()): ?>
        </main>
    </div>
</div>
<?php else: ?>
</main>
<?php endif; ?>

<footer class="bg-white border-top text-center py-3 mt-5">
    <div class="container">
        <small class="text-muted">
            &copy; <?= date('Y') ?> Exam Distribution System
        </small>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Your custom JS -->
<script src="<?= APP_BASE_URL ?>/assets/js/script.js?v=20260927-2"></script>
</body>
</html>