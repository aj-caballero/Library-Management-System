<?php

declare(strict_types=1);

/**
 * Shared archived-accounts helpers for admin and superadmin pages.
 */
function archivedAccountsFilterSql(string $search, string $roleFilter, string $gradeFilter): array
{
    $where = " WHERE is_archived = 1";
    $countWhere = " WHERE is_archived = 1";
    $params = [];

    if ($search !== '') {
        $where .= " AND (fullname LIKE :search_fullname OR email LIKE :search_email OR COALESCE(archived_reason, '') LIKE :search_reason)";
        $countWhere .= " AND (fullname LIKE :search_fullname OR email LIKE :search_email OR COALESCE(archived_reason, '') LIKE :search_reason)";
        $params[':search_fullname'] = '%' . $search . '%';
        $params[':search_email'] = '%' . $search . '%';
        $params[':search_reason'] = '%' . $search . '%';
    }

    if ($roleFilter !== '') {
        $where .= " AND role = :role";
        $countWhere .= " AND role = :role";
        $params[':role'] = $roleFilter;
    }

    if ($gradeFilter !== '') {
        $where .= " AND grade_level = :grade_level";
        $countWhere .= " AND grade_level = :grade_level";
        $params[':grade_level'] = $gradeFilter;
    }

    return [
        'where' => $where,
        'countWhere' => $countWhere,
        'params' => $params,
    ];
}

function archivedAccountsSummaryCount(PDO $pdo, string $roleFilter = '', string $gradeFilter = ''): array
{
    $sql = [
        'total' => "SELECT COUNT(*) FROM users WHERE is_archived = 1",
        'admins' => "SELECT COUNT(*) FROM users WHERE is_archived = 1 AND role = 'admin'",
        'students' => "SELECT COUNT(*) FROM users WHERE is_archived = 1 AND role = 'student'",
    ];

    $params = [];
    if ($roleFilter !== '' && in_array($roleFilter, ['admin', 'student'], true)) {
        $sql['total'] .= " AND role = :role";
        $sql['admins'] .= " AND role = :role";
        $sql['students'] .= " AND role = :role";
        $params[':role'] = $roleFilter;
    }
    if ($gradeFilter !== '') {
        $sql['total'] .= " AND grade_level = :grade_level";
        $sql['admins'] .= " AND grade_level = :grade_level";
        $sql['students'] .= " AND grade_level = :grade_level";
        $params[':grade_level'] = $gradeFilter;
    }

    $result = [];
    foreach ($sql as $key => $query) {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $result[$key] = (int) $stmt->fetchColumn();
    }

    return $result;
}
