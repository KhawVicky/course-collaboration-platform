<?php
// Render shared Previous, page-number, and Next navigation for database lists.
if (($paginationTotalPages ?? 1) > 1):
    $pageUrl = static function (int $targetPage) use ($paginationPath, $paginationParameters): string {
        $parameters = array_merge($paginationParameters, ['page' => $targetPage]);
        return url($paginationPath . '?' . http_build_query($parameters));
    };
?>
    <nav class="discussion-pagination" aria-label="<?= e($paginationLabel) ?>">
        <?php if ($paginationPage > 1): ?>
            <a href="<?= e($pageUrl($paginationPage - 1)) ?>">Previous</a>
        <?php else: ?>
            <span class="disabled" aria-disabled="true">Previous</span>
        <?php endif; ?>

        <div class="discussion-page-numbers">
            <?php for ($pageNumber = 1; $pageNumber <= $paginationTotalPages; $pageNumber++): ?>
                <?php if ($pageNumber === $paginationPage): ?>
                    <span class="current" aria-current="page"><?= $pageNumber ?></span>
                <?php else: ?>
                    <a href="<?= e($pageUrl($pageNumber)) ?>"><?= $pageNumber ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <?php if ($paginationPage < $paginationTotalPages): ?>
            <a href="<?= e($pageUrl($paginationPage + 1)) ?>">Next</a>
        <?php else: ?>
            <span class="disabled" aria-disabled="true">Next</span>
        <?php endif; ?>
    </nav>
<?php endif; ?>