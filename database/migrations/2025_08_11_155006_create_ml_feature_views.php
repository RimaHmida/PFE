<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $db = DB::getDatabaseName();
        $table = 'presence_journalieres'; // change here if your table name is different

        // Read existing columns from information_schema
        $rows = DB::select(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$db, $table]
        );
        $cols = array_map(fn($r) => strtolower($r->COLUMN_NAME), $rows);

        // Candidate column names (adapted to common variants)
        $dateCandidates   = ['date_jour','date_presence','date','jour','day'];
        $boolCandidates   = ['present','is_present','presence']; // 1/0 where 0 = absent
        $statusCandidates = ['statut','etat','status'];          // string where 'absent' means absent

        // Pick the first matching column that exists
        $dateCol   = null;
        foreach ($dateCandidates as $c) { if (in_array($c, $cols, true)) { $dateCol = $c; break; } }

        $presentCol = null;
        foreach ($boolCandidates as $c) { if (in_array($c, $cols, true)) { $presentCol = $c; break; } }

        $statusCol = null;
        foreach ($statusCandidates as $c) { if (in_array($c, $cols, true)) { $statusCol = $c; break; } }

        if (!$dateCol) {
            throw new \RuntimeException("Could not detect date column in `$table`. Tried: ".implode(', ', $dateCandidates));
        }
        if (!$presentCol && !$statusCol) {
            throw new \RuntimeException("Could not detect presence or status column in `$table`. Tried presence: [".implode(', ', $boolCandidates)."], status: [".implode(', ', $statusCandidates)."]");
        }

        // Build the expression to compute is_absent
        if ($presentCol) {
            // boolean-style column (0/1): 0 = absent, 1 = present
            $isAbsentExpr = "CASE WHEN p.`$presentCol` = 0 THEN 1 ELSE 0 END";
        } else {
            // status-style column (text): treat these as absent
            $isAbsentExpr = "CASE WHEN p.`$statusCol` IN ('absent','non_justifie','non justifie','non_justifié') THEN 1 ELSE 0 END";
        }

        // 1) presence_daily_slim
        $sql1 = "
            CREATE OR REPLACE VIEW presence_daily_slim AS
            SELECT
                p.employe_id,
                DATE(p.`$dateCol`) AS day,
                $isAbsentExpr AS is_absent
            FROM `$table` p;
        ";
        DB::statement($sql1);

        // 2) ml_features_weekly (weekly aggregates used by the Python script)
        $sql2 = <<<SQL
CREATE OR REPLACE VIEW ml_features_weekly AS
SELECT
    w.employe_id,
    w.week_start,
    w.absence_rate_1w,
    COALESCE(f.absence_rate_4w, 0) AS absence_rate_4w,
    0 AS consecutive_absent_max_4w,
    0 AS rolling_absence_z_4w,
    w.days_in_week,
    COALESCE(f.monday_absences_8w, 0) AS monday_absences_8w,
    -- effective variants (same for now)
    w.absence_rate_1w AS eff_absence_rate_1w,
    COALESCE(f.absence_rate_4w, 0) AS eff_absence_rate_4w,
    0 AS consecutive_eff_absent_max_4w
FROM
(
    -- Weekly (Monday-based) aggregation
    SELECT
        d.employe_id,
        DATE_SUB(d.day, INTERVAL WEEKDAY(d.day) DAY) AS week_start,
        COUNT(*) AS days_in_week,
        AVG(d.is_absent) AS absence_rate_1w
    FROM presence_daily_slim d
    GROUP BY d.employe_id, DATE_SUB(d.day, INTERVAL WEEKDAY(d.day) DAY)
) AS w
LEFT JOIN
(
    -- 4-week rolling stats anchored at week_start
    SELECT
        x.employe_id,
        x.week_start,
        (
            SELECT AVG(d2.is_absent)
            FROM presence_daily_slim d2
            WHERE d2.employe_id = x.employe_id
              AND d2.day >= x.week_start
              AND d2.day <  DATE_ADD(x.week_start, INTERVAL 28 DAY)
        ) AS absence_rate_4w,
        (
            SELECT SUM(CASE WHEN DAYOFWEEK(d2.day) = 2 AND d2.is_absent = 1 THEN 1 ELSE 0 END)
            FROM presence_daily_slim d2
            WHERE d2.employe_id = x.employe_id
              AND d2.day >= DATE_SUB(x.week_start, INTERVAL 56 DAY)
              AND d2.day <  x.week_start
        ) AS monday_absences_8w
    FROM (
        SELECT DISTINCT
            d.employe_id,
            DATE_SUB(d.day, INTERVAL WEEKDAY(d.day) DAY) AS week_start
        FROM presence_daily_slim d
    ) AS x
) AS f
  ON f.employe_id = w.employe_id
 AND f.week_start = w.week_start;
SQL;
        DB::statement($sql2);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ml_features_weekly');
        DB::statement('DROP VIEW IF EXISTS presence_daily_slim');
    }
};
