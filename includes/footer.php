    <!-- Close the shared page and load Bootstrap and application scripts. -->
    </main>
    <footer class="site-footer">
        <div class="container">
            <div class="footer-rule"></div>
            <div class="row align-items-center gy-3 py-4">
                <div class="col-md-7">
                    <p class="footer-title mb-1">Course Collaboration Platform</p>
                    <p class="footer-copy mb-0">A clear, shared place for teaching and learning.</p>
                </div>
                <div class="col-md-5 text-md-end">
                    <p class="footer-copy mb-0">Learn · Share · Progress · <span data-current-year><?= date('Y') ?></span></p>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?= e(url('assets/js/main.js')) ?>"></script>
</body>
</html>
