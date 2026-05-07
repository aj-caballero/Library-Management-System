<?php

declare(strict_types=1);

function renderPaginationLinks(string $basePath, array $queryParams, int $page, int $totalPages, int $totalRows, int $perPage): void
{
    if ($totalPages <= 1) {
        return;
    }

    $label = 'Showing ' . (($page - 1) * $perPage + 1) . ' to ' . min($page * $perPage, $totalRows) . ' of ' . $totalRows . ' entries';
    $pageQuery = $queryParams;

    echo '<div class="pagination">';
    echo '<span class="text-sm text-muted" style="margin-right:auto;">' . e($label) . '</span>';

    if ($page > 1) {
        $pageQuery['page'] = $page - 1;
        echo '<a href="' . e($basePath . '?' . http_build_query($pageQuery)) . '" class="page-btn">Prev</a>';
    } else {
        echo '<span class="page-btn disabled">Prev</span>';
    }

    $startPage = max(1, $page - 2);
    $endPage = min($totalPages, $page + 2);
    for ($p = $startPage; $p <= $endPage; $p++) {
        $pageQuery['page'] = $p;
        $activeClass = $p === $page ? ' active' : '';
        echo '<a href="' . e($basePath . '?' . http_build_query($pageQuery)) . '" class="page-btn' . $activeClass . '">' . $p . '</a>';
    }

    if ($page < $totalPages) {
        $pageQuery['page'] = $page + 1;
        echo '<a href="' . e($basePath . '?' . http_build_query($pageQuery)) . '" class="page-btn">Next</a>';
    } else {
        echo '<span class="page-btn disabled">Next</span>';
    }

    echo '</div>';
}