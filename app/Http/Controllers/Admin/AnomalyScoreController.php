<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AnomalyScore;

class AnomalyScoreController extends Controller
{
    protected function authorizeAdmin(): void
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['administrateur', 'administrateur_it'])) {
            abort(403, 'Accès réservé aux administrateurs.');
        }
    }

    // GET /api/admin/anomaly-scores?limit=200&latest_per_employee=1
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 200);
        $latestPerEmployee = filter_var($request->query('latest_per_employee', '1'), FILTER_VALIDATE_BOOLEAN);

        if ($latestPerEmployee) {
            $sub = DB::table('anomaly_scores as a1')
                ->select('a1.employe_id', DB::raw('MAX(a1.week_start) as max_week'))
                ->groupBy('a1.employe_id');

            $rows = DB::table('anomaly_scores as a')
                ->joinSub($sub, 'm', function ($join) {
                    $join->on('a.employe_id', '=', 'm.employe_id')
                         ->on('a.week_start', '=', 'm.max_week');
                })
                ->leftJoin('employes as e', 'e.id', '=', 'a.employe_id')
                ->select(
                    'a.id','a.employe_id','a.week_start','a.score','a.extras',
                    'a.created_at','a.updated_at','e.nom','e.prenom','e.statut'
                )
                ->orderByDesc('a.score')
                ->limit($limit)
                ->get();

            return response()->json(['data' => $rows]);
        }

        $rows = AnomalyScore::with('employe:id,nom,prenom,statut')
            ->orderByDesc('week_start')
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $rows]);
    }

    private function cleanUtf8(?string $s): string
    {
        if ($s === null) return '';
        $s = @mb_convert_encoding($s, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252, ASCII');
        $s = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
        $s = preg_replace('/[^\P{C}\n\t]/u', '', $s) ?? '';
        return $s;
    }

    public function refresh(Request $request)
    {
        try {
            set_time_limit(180);

            $weeks  = max(1, min((int)($request->input('weeks', 8)), 52));
            $cutoff = now()->subWeeks($weeks)->toDateString();

            // 1) Pull features in PHP (Python will NOT connect to DB)
            $features = DB::select("
              SELECT
                employe_id, week_start,
                IFNULL(absence_rate_1w,0)               AS absence_rate_1w,
                IFNULL(absence_rate_4w,0)               AS absence_rate_4w,
                IFNULL(consecutive_absent_max_4w,0)     AS consecutive_absent_max_4w,
                IFNULL(rolling_absence_z_4w,0)          AS rolling_absence_z_4w,
                IFNULL(days_in_week,0)                  AS days_in_week,
                IFNULL(monday_absences_8w,0)            AS monday_absences_8w,
                IFNULL(eff_absence_rate_1w,0)           AS eff_absence_rate_1w,
                IFNULL(eff_absence_rate_4w,0)           AS eff_absence_rate_4w,
                IFNULL(consecutive_eff_absent_max_4w,0) AS consecutive_eff_absent_max_4w
              FROM ml_features_weekly
              WHERE week_start >= ?
              ORDER BY week_start DESC
            ", [$cutoff]);

            $featuresArr = json_decode(json_encode($features), true) ?: [];

            if (empty($featuresArr)) {
                return response()->json([
                    'ok' => true,
                    'upserted' => 0,
                    'stdout' => 'No features'
                ], 200);
            }

            // 2) Resolve Python + script paths
            $python = PHP_OS_FAMILY === 'Windows'
                ? base_path('venv\\Scripts\\python.exe')
                : base_path('venv/bin/python');

            if (!@file_exists($python)) {
                $python = PHP_OS_FAMILY === 'Windows' ? 'py' : 'python3';
            }

            $script = base_path('app'.DIRECTORY_SEPARATOR.'ml'.DIRECTORY_SEPARATOR.'score_anomalies.py');
            if (!file_exists($script)) {
                return response()->json([
                    'ok' => false,
                    'message' => "Python script not found",
                    'script'  => $script
                ], 500);
            }

            // 3) Ensure proper Windows env for child process
            $env = array_merge($_SERVER, $_ENV, [
                'PYTHONUTF8'       => '1',
                'PYTHONIOENCODING' => 'UTF-8',
                'SYSTEMROOT'       => getenv('SystemRoot') ?: getenv('SYSTEMROOT') ?: 'C:\\Windows',
                'WINDIR'           => getenv('WINDIR') ?: 'C:\\Windows',
                'PATH'             => getenv('PATH') ?: '',
            ]);

            // 4) Run Python and feed features via STDIN
            $proc = new Process([$python, $script]);
            $proc->setEnv($env);
            $proc->setWorkingDirectory(base_path());
            $proc->setInput(json_encode($featuresArr, JSON_UNESCAPED_UNICODE));
            $proc->setTimeout(180);
            $proc->run();

            if (!$proc->isSuccessful()) {
                $stderr = $this->cleanUtf8($proc->getErrorOutput());
                $stdout = $this->cleanUtf8($proc->getOutput());

                Log::error('anomaly-scores refresh failed', [
                    'exit_code' => $proc->getExitCode(),
                    'stderr'    => mb_substr($stderr, 0, 4000),
                    'stdout'    => mb_substr($stdout, 0, 4000),
                    'python'    => $python,
                    'script'    => $script,
                    'env_subset'=> [
                        'SYSTEMROOT' => $env['SYSTEMROOT'] ?? null,
                        'WINDIR'     => $env['WINDIR'] ?? null,
                    ],
                ]);

                return response()->json([
                    'ok'        => false,
                    'exit_code' => $proc->getExitCode(),
                    'stderr'    => $stderr,
                    'stdout'    => $stdout,
                ], 500, [], JSON_UNESCAPED_UNICODE);
            }

            // 5) Parse Python output -> upsert
            $scored = json_decode($this->cleanUtf8($proc->getOutput()), true) ?: [];
            if (empty($scored)) {
                return response()->json(['ok' => true, 'upserted' => 0, 'stdout' => 'No rows from model'], 200);
            }

            $now = now();
            $payload = [];
            foreach ($scored as $r) {
                $payload[] = [
                    'employe_id' => (int) $r['employe_id'],
                    'week_start' => $r['week_start'],
                    'score'      => (float) $r['score'],
                    'extras'     => json_encode([
                        'method' => $r['method'] ?? 'predict_proba',
                        'feature_order' => $r['feature_order'] ?? null,
                        'scoring_window_weeks' => $weeks,
                        'reasons' => $r['reasons'] ?? null, // ← persist reasons for Severe only
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('anomaly_scores')->upsert(
                $payload,
                ['employe_id','week_start'],
                ['score','extras','updated_at']
            );

            return response()->json([
                'ok'       => true,
                'upserted' => count($payload),
                'stdout'   => "Upserted ".count($payload)." rows",
            ], 200);

        } catch (\Throwable $e) {
            Log::error('anomaly-scores refresh exception', ['err' => $e->getMessage()]);
            return response($this->cleanUtf8($e->getMessage()), 500)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }
    }

    public function page(Request $request)
    {
        $limit = (int) $request->query('limit', 200);

        $sub = DB::table('anomaly_scores as a1')
            ->select('a1.employe_id', DB::raw('MAX(a1.week_start) as max_week'))
            ->groupBy('a1.employe_id');

        $rows = DB::table('anomaly_scores as a')
            ->joinSub($sub, 'm', function ($join) {
                $join->on('a.employe_id', '=', 'm.employe_id')
                     ->on('a.week_start', '=', 'm.max_week');
            })
            ->leftJoin('employes as e', 'e.id', '=', 'a.employe_id')
            ->select(
                'a.id','a.employe_id','a.week_start','a.score','a.extras','a.created_at',
                'e.nom','e.prenom','e.statut'
            )
            ->orderByDesc('a.score')
            ->limit($limit)
            ->get();

        return view('admin.anomaly_scores', ['rows' => $rows]);
    }
}
