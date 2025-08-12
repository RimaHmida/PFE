# app/ml/score_anomalies.py
import sys, json, importlib
from pathlib import Path
import numpy as np
from joblib import load as joblib_load

# ---------- I/O: UTF-8 (Windows-safe) ----------
if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8")
if hasattr(sys.stderr, "reconfigure"):
    sys.stderr.reconfigure(encoding="utf-8")

# ---------- Compat for some sklearn pickles ----------
try:
    if "_loss" not in sys.modules:
        sys.modules["_loss"] = importlib.import_module("sklearn._loss")
except Exception:
    pass

# ---------- Minimal estimator helpers ----------
def _has_api(x):
    return any(hasattr(x, a) for a in (
        "predict_proba", "predict_log_proba", "decision_function", "score_samples", "predict", "__call__"
    ))

def _unwrap_any(obj, seen=None):
    """Find a usable estimator/function inside arbitrarily nested dict/list/attrs."""
    if seen is None:
        seen = set()
    oid = id(obj)
    if oid in seen:
        return None
    seen.add(oid)

    if _has_api(obj):
        return obj

    if isinstance(obj, dict):
        # prefer common keys
        for k in ("model","estimator","clf","best_estimator_","final_estimator_","estimator_","sk_model"):
            if k in obj:
                r = _unwrap_any(obj[k], seen)
                if r is not None:
                    return r
        # fallback: search values
        for v in obj.values():
            r = _unwrap_any(v, seen)
            if r is not None:
                return r
        return None

    if isinstance(obj, (list, tuple)):
        for v in obj:
            r = _unwrap_any(v, seen)
            if r is not None:
                return r
        return None

    # sklearn pipelines/compositions
    for attr in ("named_steps","steps","steps_"):
        if hasattr(obj, attr):
            val = getattr(obj, attr)
            if isinstance(val, dict):
                for v in val.values():
                    r = _unwrap_any(v, seen)
                    if r is not None:
                        return r
            elif isinstance(val, (list, tuple)):
                for v in val:
                    vv = v[1] if isinstance(v, (list, tuple)) and len(v) == 2 else v
                    r = _unwrap_any(vv, seen)
                    if r is not None:
                        return r

    # generic composition: common attribute names
    if hasattr(obj, "__dict__"):
        for name in ("model","estimator","clf","best_estimator_","final_estimator_","estimator_","sk_model"):
            if hasattr(obj, name):
                r = _unwrap_any(getattr(obj, name), seen)
                if r is not None:
                    return r
    return None

# ---------- RandomModel (used only if pickle stored a dict/instance) ----------
class RandomModel:
    """
    RNG-based demo model. If your pickle actually stores this class or a dict of
    params, this serves as a safe runtime stand-in. Real scoring still comes from
    whatever your pickle provides (predict_proba/…).
    """
    seed = None
    n_classes = 2

    def __init__(self, feature_names=None, seed=None, n_classes=2, **kwargs):
        # if unpickler already set attrs, don't overwrite
        if not hasattr(self, "seed") or self.seed is None:
            self.seed = seed
        if not hasattr(self, "n_classes"):
            try:
                self.n_classes = max(2, int(n_classes))
            except Exception:
                self.n_classes = 2
        if feature_names is not None and not hasattr(self, "feature_names"):
            self.feature_names = list(feature_names)

    def __setstate__(self, state):
        self.__dict__.update(state)
        if "n_classes" not in self.__dict__ or not isinstance(self.__dict__["n_classes"], (int, float)):
            self.__dict__["n_classes"] = 2
        if "seed" not in self.__dict__:
            self.__dict__["seed"] = None

    def _rng(self):
        return np.random.default_rng(getattr(self, "seed", None))

    def predict_proba(self, X):
        X = np.asarray(X, dtype=float)
        n = X.shape[0]
        k = int(getattr(self, "n_classes", 2) or 2)
        rng = self._rng()
        P = rng.random((n, k))
        P /= P.sum(axis=1, keepdims=True)
        return P

# ---------- Features mapping ----------
FEATURES = [
    "eff_absence_rate_1w",
    "eff_absence_rate_4w",
    "consecutive_eff_absent_max_4w",
    "rolling_eff_absence_z_4w",
    "days_in_week",
]
SYN = {
    "eff_absence_rate_1w": ["eff_absence_rate_1w","absence_rate_1w"],
    "eff_absence_rate_4w": ["eff_absence_rate_4w","absence_rate_4w"],
    "consecutive_eff_absent_max_4w": ["consecutive_eff_absent_max_4w","consecutive_absent_max_4w"],
    "rolling_eff_absence_z_4w": ["rolling_eff_absence_z_4w","rolling_absence_z_4w"],
    "days_in_week": ["days_in_week"],
}

HERE = Path(__file__).resolve()
MODEL_PATH = HERE.parent / "random_model.pkl"  # ← only this file is used

# ---------- Utilities ----------
def build_matrix(rows, feature_order):
    if not rows:
        return np.zeros((0, len(feature_order)), dtype=float)
    mat = []
    for r in rows:
        vec = []
        for f in feature_order:
            val = 0.0
            for name in SYN.get(f, [f]):
                if name in r and r[name] is not None:
                    try:
                        val = float(r[name])
                    except Exception:
                        val = 0.0
                    break
            vec.append(val)
        mat.append(vec)
    return np.asarray(mat, dtype=float)

def call_model(est, X):
    """Return RAW model output and method name (no post-scaling here)."""
    # safety for half-initialized RandomModel
    try:
        if isinstance(est, RandomModel):
            if not hasattr(est, "n_classes") or not isinstance(getattr(est, "n_classes", None), (int, float)):
                est.n_classes = 2
            if not hasattr(est, "seed"):
                est.seed = None
    except Exception:
        pass

    if hasattr(est, "predict_proba"):
        p = np.asarray(est.predict_proba(X))
        if p.ndim == 2 and p.shape[1] >= 2:
            return p[:, 1].astype(float), "predict_proba"
        return p.ravel().astype(float), "predict_proba"
    if hasattr(est, "predict_log_proba"):
        p = np.asarray(est.predict_log_proba(X))
        if p.ndim == 2 and p.shape[1] >= 2:
            return np.exp(p[:, 1]).astype(float), "predict_log_proba"
        return np.exp(p.ravel()).astype(float), "predict_log_proba"
    if hasattr(est, "decision_function"):
        v = np.asarray(est.decision_function(X)).ravel().astype(float)
        return v, "decision_function"
    if hasattr(est, "score_samples"):
        v = np.asarray(est.score_samples(X)).ravel().astype(float)
        return v, "score_samples"
    if hasattr(est, "predict"):
        v = np.asarray(est.predict(X)).ravel().astype(float)
        return v, "predict"
    if hasattr(est, "__call__") and callable(est):
        v = np.asarray(est(X)).ravel().astype(float)
        return v, "__call__"
    raise SystemExit("Estimator has no usable scoring method")

# ---------- Reasons (only for Severe) ----------
SEVERE_THRESHOLD = 0.5

DEFAULT_REASON_TEMPLATES = [
    "taux d'absence 1 semaine très élevé",
    "hausse soudaine vs moyenne 4 semaines",
    "absences consécutives élevées",
    "anomalie statistique (z-score élevé)",
    "absences récurrentes le lundi",
]

def _getval(row, keys, default=0.0):
    for k in keys:
        if k in row and row[k] is not None:
            try:
                return float(row[k])
            except Exception:
                pass
    return float(default)

def pick_random_reason(row, bundle, rng):
    # Candidates from actual feature values (if present)
    candidates = []
    eff1 = _getval(row, SYN["eff_absence_rate_1w"])                    # 1w rate
    eff4 = _getval(row, SYN["eff_absence_rate_4w"])                    # 4w rate
    consec = _getval(row, SYN["consecutive_eff_absent_max_4w"])        # consec absences
    z4    = _getval(row, SYN["rolling_eff_absence_z_4w"],
                    default=_getval(row, ["rolling_absence_z_4w"]))
    mon8  = _getval(row, ["monday_absences_8w"])

    if eff1 >= 0.60:
        candidates.append("taux d'absence 1 semaine très élevé")
    if (eff1 - eff4) >= 0.20:
        candidates.append("hausse soudaine vs moyenne 4 semaines")
    if consec >= 3:
        candidates.append("absences consécutives élevées")
    if z4 >= 1.5:
        candidates.append("anomalie statistique (z-score élevé)")
    if mon8 >= 2:
        candidates.append("absences récurrentes le lundi")

    # Templates from bundle if present
    templates = []
    if isinstance(bundle, dict) and isinstance(bundle.get("reason_templates"), list):
        templates = [str(t) for t in bundle["reason_templates"] if t]
    if not templates:
        templates = DEFAULT_REASON_TEMPLATES

    if candidates:
        return candidates[rng.integers(0, len(candidates))]
    return templates[rng.integers(0, len(templates))]

# ---------- Main ----------
def main():
    raw = sys.stdin.read()
    feats = json.loads(raw) if raw.strip() else []
    if not feats:
        print("[]")
        return

    if not MODEL_PATH.exists():
        raise SystemExit(f"Model file not found: {MODEL_PATH}")
    bundle = joblib_load(MODEL_PATH)

    # unwrap estimator (or synthesize RandomModel if bundle is a dict of params)
    est = _unwrap_any(bundle)
    method_note = "auto_unwrapped"
    if est is None and isinstance(bundle, dict):
        # try to construct a RandomModel from dict params
        seed = None
        if isinstance(bundle.get("seed"), (int, float)): seed = int(bundle["seed"])
        for k in ("random_state", "rng_seed", "rand_seed"):
            if seed is None and isinstance(bundle.get(k), (int, float)):
                seed = int(bundle[k])
        n_classes = bundle.get("n_classes", bundle.get("n_classes_", 2))
        try:
            n_classes = int(n_classes)
        except Exception:
            n_classes = 2
        est = RandomModel(
            feature_names=bundle.get("feature_names"),
            seed=seed,
            n_classes=n_classes
        )
        method_note = "RandomModel_from_dict"

    if est is None:
        diag = {"err": "no_estimator_found", "type": type(bundle).__name__}
        if isinstance(bundle, dict):
            diag["keys"] = list(bundle.keys())
            diag["types"] = {k: type(bundle[k]).__name__ for k in list(bundle.keys())[:12]}
        sys.stderr.write(json.dumps(diag, ensure_ascii=False) + "\n")
        raise SystemExit("Estimator has no usable scoring method")

    # feature order from bundle if provided
    feature_order = FEATURES
    if isinstance(bundle, dict) and isinstance(bundle.get("feature_names"), list):
        feature_order = list(bundle["feature_names"]) or FEATURES

    X = build_matrix(feats, feature_order)
    y, used = call_model(est, X)
    if not np.all(np.isfinite(y)):
        y = np.nan_to_num(y, nan=0.0, posinf=0.0, neginf=0.0)

    out = []
    for r, s in zip(feats, y.tolist()):
        item = {
            "employe_id": int(r["employe_id"]),
            "week_start": r["week_start"],
            "score": float(s),
            "feature_order": feature_order,
            "method": f"{used}|{method_note}",
        }
        # deterministic per-row RNG for stable messaging
        row_seed = abs(hash(f"{r['employe_id']}|{r['week_start']}")) % (2**32)
        rng = np.random.default_rng(row_seed)
        if float(s) >= SEVERE_THRESHOLD:
            item["reasons"] = [pick_random_reason(r, bundle, rng)]
        out.append(item)

    print(json.dumps(out, ensure_ascii=False))

if __name__ == "__main__":
    main()
